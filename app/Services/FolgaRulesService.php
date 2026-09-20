<?php

namespace App\Services;

use App\Models\FolgaDia;
use App\Models\FolgaDomingoSaldo;
use App\Models\FolgaMovimento;
use App\Models\FolgaProgramacao;
use App\Models\FolgaSaldoMensal;
use App\Models\FolgaSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FolgaRulesService
{
    private FolgaSetting $settings;

    private int $diasParaFolga;

    /** @var array<int, array<int, array{0: Carbon, 1: Carbon}>> */
    private array $feriasCache = [];

    public function __construct()
    {
        self::ensureTablesExist();
        $this->settings = FolgaSetting::firstOrCreateDefault();
        $this->diasParaFolga = $this->settings->dias_para_folga;
    }

    /**
     * DDL consolidado em migrations (ver database/migrations/2026_08_28_*).
     * Mantido como no-op para compatibilidade com os controllers que o chamam.
     */
    public static function ensureTablesExist(): void
    {
        //
    }

    /**
     * Data em que o controle do banco de folgas passou a valer (marco zero).
     * Antes dela nada é contado; no mês dela, só os dias a partir da data.
     */
    public function dataInicioControle(): ?Carbon
    {
        $data = FolgaSetting::firstOrCreateDefault()->data_inicio_controle;

        return $data ? Carbon::parse($data)->startOfDay() : null;
    }

    /**
     * Previsão mensal de folgas: ciclo 6 dias trabalhados + 1 folga (que não
     * conta para o próximo crédito). É a mesma para todos os motoristas e não
     * considera lançamentos. No mês do marco zero conta só a partir da data.
     */
    public function previsaoMes(int $mes, int $ano): int
    {
        $inicioMes = Carbon::createFromDate($ano, $mes, 1)->startOfDay();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $inicioControle = $this->dataInicioControle();

        if ($inicioControle && $fimMes->lt($inicioControle)) {
            return 0;
        }

        $inicio = ($inicioControle && $inicioControle->gt($inicioMes))
            ? $inicioControle->copy()
            : $inicioMes->copy();

        $dias = $inicio->diffInDays($fimMes) + 1;

        return intdiv($dias + 1, 7);
    }

    /**
     * Períodos de férias do motorista (histórico + período atual dos campos).
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function periodosFerias(User $user): array
    {
        if (isset($this->feriasCache[$user->id])) {
            return $this->feriasCache[$user->id];
        }

        $periodos = [];

        if ($user->ferias_inicio && $user->ferias_fim) {
            $periodos[] = [
                $user->ferias_inicio->copy()->startOfDay(),
                $user->ferias_fim->copy()->startOfDay(),
            ];
        }

        foreach ($user->vacations()->get() as $ferias) {
            $periodos[] = [
                $ferias->data_inicio->copy()->startOfDay(),
                $ferias->data_fim->copy()->startOfDay(),
            ];
        }

        return $this->feriasCache[$user->id] = $periodos;
    }

    /**
     * O dia informado cai em algum período de férias?
     *
     * @param  array<int, array{0: Carbon, 1: Carbon}>  $periodos
     */
    private function dataEmFerias(array $periodos, string $data): bool
    {
        $dia = Carbon::parse($data)->startOfDay();

        foreach ($periodos as [$inicio, $fim]) {
            if ($dia->betweenIncluded($inicio, $fim)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Quantos dias já foram contados no bloco atual de 6 (0 a 5) no primeiro
     * dia do mês. A contagem é contínua e baseada no marco zero; sem ele,
     * reinicia em cada mês.
     */
    private function sequenciaEfetivaInicial(User $user, int $mes, int $ano): int
    {
        $inicioControle = $this->dataInicioControle();

        if (! $inicioControle) {
            return 0;
        }

        $inicioMes = Carbon::createFromDate($ano, $mes, 1)->startOfDay();

        if (! $inicioMes->gt($inicioControle)) {
            return 0;
        }

        // Último evento que zerou o ciclo antes do mês
        $candidatos = [$inicioControle->copy()->subDay()];

        $ultimoLancamento = FolgaDia::where('user_id', $user->id)
            ->where('tipo', '!=', 'trabalho')
            ->whereDate('data', '<', $inicioMes->format('Y-m-d'))
            ->orderByDesc('data')
            ->value('data');
        if ($ultimoLancamento) {
            $candidatos[] = Carbon::parse($ultimoLancamento)->startOfDay();
        }

        if ($user->ultima_folga_data && $user->ultima_folga_data->lt($inicioMes)) {
            $candidatos[] = $user->ultima_folga_data->copy()->startOfDay();
        }

        foreach ($this->periodosFerias($user) as [$inicio, $fim]) {
            if ($fim->lt($inicioMes)) {
                $candidatos[] = $fim->copy();
            }
        }

        $programacoes = FolgaProgramacao::where('user_id', $user->id)
            ->whereDate('data_inicio', '<', $inicioMes->format('Y-m-d'))
            ->get();
        foreach ($programacoes as $programacao) {
            $fim = $programacao->dataFimEfetiva()->startOfDay();
            if ($fim->lt($inicioMes)) {
                $candidatos[] = $fim;
            }
        }

        $reset = collect($candidatos)
            ->sortByDesc(fn (Carbon $data) => $data->timestamp)
            ->first();

        if (! $reset || $reset->gte($inicioMes)) {
            return 0;
        }

        $diasContados = $reset->diffInDays($inicioMes) - 1;

        return $diasContados > 0 ? $diasContados % $this->diasParaFolga : 0;
    }

    /**
     * Calcula créditos e contagens para um motorista no mês.
     *
     * - "previstas": previsão mensal (ciclo 6 trabalhados + 1 folga que não
     *   conta), igual para todos e sem considerar lançamentos.
     * - "previstas_ganhas": créditos efetivos — contagem contínua entre meses,
     *   baseada só em lançamentos; folga/atestado/licença/férias zeram o ciclo
     *   e ele recomeça no dia seguinte. Sem marco zero, reinicia no mês.
     */
    public function computeMonthData(User $user, int $mes, int $ano): array
    {
        $records = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get()
            ->keyBy(fn ($r) => $r->data->format('Y-m-d'));

        $diasMes = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $streak = $this->sequenciaEfetivaInicial($user, $mes, $ano);
        $previstasGanhas = 0;
        $diasTrabalhados = 0;
        $diasAtestado = 0;
        $diasLicenca = 0;
        $diasFolga = 0;

        // Se tem "última folga" registrada e cai neste mês, marcar como folga automática
        $ultimaFolga = $user->ultima_folga_data;
        if ($ultimaFolga && (int) $ultimaFolga->month === $mes && (int) $ultimaFolga->year === $ano) {
            $dataKey = $ultimaFolga->format('Y-m-d');
            if (! isset($records[$dataKey])) {
                $records[$dataKey] = (object) ['tipo' => 'folga'];
            }
        }

        // Programações só contam quando a data chega (débito na data)
        $programados = $this->diasProgramadosNoMes($user, $mes, $ano, $records);

        // Marco zero: antes da data de início do controle nada é contado
        $inicioControle = $this->dataInicioControle();

        if ($inicioControle) {
            $programados = array_filter(
                $programados,
                fn ($chave) => $chave >= $inicioControle->format('Y-m-d'),
                ARRAY_FILTER_USE_KEY
            );
        }

        $ferias = $this->periodosFerias($user);

        if ($ferias) {
            // Programações dentro de férias não debitam
            $programados = array_filter(
                $programados,
                fn ($chave) => ! $this->dataEmFerias($ferias, $chave),
                ARRAY_FILTER_USE_KEY
            );
        }

        $hoje = now()->startOfDay()->format('Y-m-d');

        for ($d = 1; $d <= $diasMes; $d++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $d);

            if ($inicioControle && $data < $inicioControle->format('Y-m-d')) {
                continue;
            }

            $record = $records->get($data);

            if ($record) {
                $tipo = $record->tipo;
            } elseif ($this->dataEmFerias($ferias, $data)) {
                $tipo = 'ferias';
            } else {
                $tipo = $programados[$data] ?? 'trabalho';
            }

            $efetivado = $data <= $hoje;

            if ($tipo === 'trabalho') {
                if ($efetivado) {
                    $diasTrabalhados++;
                }
                $streak++;
                if ($streak >= $this->diasParaFolga) {
                    if ($efetivado) {
                        $previstasGanhas++;
                    }
                    $streak = 0;
                }
            } elseif ($tipo === 'atestado') {
                if ($efetivado) {
                    $diasAtestado++;
                }
                $streak = 0;
            } elseif ($tipo === 'licenca') {
                if ($efetivado) {
                    $diasLicenca++;
                }
                $streak = 0;
            } elseif ($tipo === 'folga') {
                $diasFolga++;
                $streak = 0;
            } elseif ($tipo === 'ferias') {
                $streak = 0;
            }
        }

        $diasFolgaMes = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->where('tipo', 'folga')
            ->when($inicioControle, fn ($q) => $q->whereDate('data', '>=', $inicioControle->format('Y-m-d')))
            ->count()
            + count(array_filter($programados, fn ($tipo) => $tipo === 'folga'));

        $domingoCumprido = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->where('tipo', 'folga')
            ->when($inicioControle, fn ($q) => $q->whereDate('data', '>=', $inicioControle->format('Y-m-d')))
            ->whereRaw(DB::connection()->getDriverName() === 'sqlite'
                ? "strftime('%w', data) = '0'"
                : 'DAYOFWEEK(data) = 1')
            ->exists();

        // Banco de domingos: quantos domingos no mês
        $domingosNoMes = 0;
        $dataCheck = Carbon::createFromDate($ano, $mes, 1);
        $fimMes = $dataCheck->copy()->endOfMonth();
        while ($dataCheck->lte($fimMes)) {
            if ($dataCheck->dayOfWeek === Carbon::SUNDAY) {
                $domingosNoMes++;
            }
            $dataCheck->addDay();
        }

        // Folgas tiradas em domingos (com domingo_ref)
        $folgasDomingo = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->where('tipo', 'folga')
            ->when($inicioControle, fn ($q) => $q->whereDate('data', '>=', $inicioControle->format('Y-m-d')))
            ->whereNotNull('domingo_ref')
            ->count();

        return [
            'previstas' => $this->previsaoMes($mes, $ano),
            'previstas_ganhas' => $previstasGanhas,
            'tiradas' => $diasFolgaMes,
            'dias_trabalhados' => $diasTrabalhados,
            'dias_atestado' => $diasAtestado,
            'dias_licenca' => $diasLicenca,
            'domingo_cumprido' => $domingoCumprido,
            'dias_continuos' => $this->diasContinuos($user, $mes, $ano),
            'domingos_no_mes' => $domingosNoMes,
            'folgas_domingo_tiradas' => $folgasDomingo,
        ];
    }

    /**
     * Dias de programações já efetivados (a data chegou) e sem lançamento
     * manual no dia. Dias futuros não debitam e lançamentos manuais vencem.
     *
     * @param  Collection<string, object>  $records
     * @return array<string, string> data (Y-m-d) => tipo
     */
    private function diasProgramadosNoMes(User $user, int $mes, int $ano, $records): array
    {
        $inicioMes = Carbon::createFromDate($ano, $mes, 1)->startOfDay();
        $fimMes = $inicioMes->copy()->endOfMonth();
        $hoje = now()->startOfDay();

        $programacoes = FolgaProgramacao::where('user_id', $user->id)
            ->whereDate('data_inicio', '<=', $fimMes->format('Y-m-d'))
            ->whereDate('data_fim', '>=', $inicioMes->format('Y-m-d'))
            ->get();

        $dias = [];

        foreach ($programacoes as $programacao) {
            $fim = $programacao->dataFimDebitavel($hoje);

            if ($fim->lt($programacao->data_inicio)) {
                continue;
            }

            $cursor = $programacao->data_inicio->copy();
            if ($cursor->lt($inicioMes)) {
                $cursor = $inicioMes->copy();
            }

            while ($cursor->lte($fim) && $cursor->lte($fimMes)) {
                $chave = $cursor->format('Y-m-d');
                if (! isset($records[$chave])) {
                    $dias[$chave] = $programacao->tipo;
                }
                $cursor->addDay();
            }
        }

        return $dias;
    }

    /**
     * Conta dias trabalhados contínuos desde a última folga/atestado/licença/
     * férias até o mês selecionado (dinâmico por mês).
     * - Mês atual: conta até hoje.
     * - Mês futuro/passado: conta até o fim do mês selecionado.
     * Dias sem registro contam como trabalho e a contagem atravessa meses
     * (a partir do marco zero). Férias zeram e o contador reinicia no dia
     * seguinte ao fim delas.
     */
    public function diasContinuos(User $user, int $mes, int $ano): int
    {
        $inicioMes = Carbon::createFromDate($ano, $mes, 1);
        $fimMes = $inicioMes->copy()->endOfMonth();
        $hoje = now()->startOfDay();

        $inicioControle = $this->dataInicioControle();
        if ($inicioControle && $inicioControle->gt($fimMes)) {
            return 0;
        }

        // Mês atual: conta até hoje; outros meses: conta até o fim do mês
        $dataFim = ($mes === (int) $hoje->month && $ano === (int) $hoje->year) ? $hoje->copy() : $fimMes->copy();

        $ferias = $this->periodosFerias($user);

        // Em férias no fim do período consultado: sem dias contínuos
        if ($this->dataEmFerias($ferias, $dataFim->format('Y-m-d'))) {
            return 0;
        }

        $candidatos = [];

        $ultimoLancamento = FolgaDia::where('user_id', $user->id)
            ->where('tipo', '!=', 'trabalho')
            ->where('data', '<=', $dataFim->format('Y-m-d'))
            ->orderByDesc('data')
            ->value('data');
        if ($ultimoLancamento) {
            $candidatos[] = Carbon::parse($ultimoLancamento)->startOfDay();
        }

        foreach ($ferias as [$inicio, $fim]) {
            if ($fim->lte($dataFim)) {
                $candidatos[] = $fim->copy();
            }
        }

        if ($user->ultima_folga_data && $user->ultima_folga_data->lte($dataFim)) {
            $candidatos[] = $user->ultima_folga_data->copy()->startOfDay();
        }

        if ($inicioControle) {
            $candidatos[] = $inicioControle->copy()->subDay();
        }

        $ultimaInterrupcao = collect($candidatos)
            ->sortByDesc(fn (Carbon $data) => $data->timestamp)
            ->first();

        // Sem interrupção conhecida: contar do início do mês (ou do marco zero)
        if (! $ultimaInterrupcao) {
            $inicioContagem = $inicioMes->copy();

            if ($inicioContagem->gt($dataFim)) {
                return 0;
            }

            return $inicioContagem->diffInDays($dataFim) + 1;
        }

        // Começa a contar no dia seguinte à última interrupção (ou no marco zero)
        $dataAtual = $ultimaInterrupcao->copy()->addDay();
        if ($inicioControle && $inicioControle->gte($dataAtual)) {
            $dataAtual = $inicioControle->copy();
        }
        if ($dataAtual->gt($dataFim)) {
            return 0;
        }

        $registros = FolgaDia::where('user_id', $user->id)
            ->whereDate('data', '>=', $dataAtual->format('Y-m-d'))
            ->whereDate('data', '<=', $dataFim->format('Y-m-d'))
            ->get()
            ->keyBy(fn ($r) => $r->data->format('Y-m-d'));

        $count = 0;
        while ($dataAtual->lte($dataFim)) {
            $chave = $dataAtual->format('Y-m-d');
            $tipo = $registros->get($chave)?->tipo ?? 'trabalho';
            $emFerias = $this->dataEmFerias($ferias, $chave);

            if ($tipo === 'trabalho' && ! $emFerias) {
                $count++;
            } else {
                $count = 0;
            }

            $dataAtual->addDay();
        }

        return $count;
    }

    /**
     * Computa snapshot mensal completo.
     *
     * - "previstas"/"previstas_mes": previsão do mês inteiro (regra dos 6 dias).
     * - "saldo_acumulado": saldo REAL acumulado — só créditos já ganhos
     *   (até hoje), mais saldo anterior e ajustes, menos folgas tiradas, com piso 0.
     */
    public function computeSnapshot(User $user, int $mes, int $ano): array
    {
        $data = $this->computeMonthData($user, $mes, $ano);

        $saldoAnterior = $this->getSaldoAnterior($user, $mes, $ano);

        $ajustes = $this->somaAjustes($user, $mes, $ano);

        // Competência (mês atual)
        $previstasMes = $data['previstas'];
        $previstasGanhas = $data['previstas_ganhas'];
        $tiradasMes = $data['tiradas'];

        // Saldo real: saldo positivo acumula; excesso de folgas zera (não gera dívida)
        $saldoAcumulado = max(0, $saldoAnterior + $previstasGanhas + $ajustes - $tiradasMes);

        // Banco de domingos
        $domingoSaldo = $this->computeDomingoSaldo($user, $mes, $ano);

        return array_merge($data, [
            // Competência (este mês)
            'previstas_mes' => $previstasMes,
            'previstas_ganhas' => $previstasGanhas,
            'tiradas_mes' => $tiradasMes,
            'saldo_anterior' => $saldoAnterior,
            'ajustes' => (int) $ajustes,
            // Acumulado (saldo real disponível)
            'previstas' => $previstasMes,
            'tiradas' => $tiradasMes,
            'saldo_acumulado' => $saldoAcumulado,
            // Banco de domingos
            'domingo_saldo' => $domingoSaldo['saldo_acumulado'],
            'domingo_saldo_anterior' => $domingoSaldo['saldo_anterior'],
            'domingo_creditos_ganhos' => $domingoSaldo['creditos_ganhos'],
            'domingo_creditos_usados' => $domingoSaldo['creditos_usados'],
        ]);
    }

    /**
     * Soma dos ajustes do mês, desconsiderando tudo antes do marco zero.
     */
    private function somaAjustes(User $user, int $mes, int $ano): int
    {
        $inicioControle = $this->dataInicioControle();
        $mesAlvo = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();

        if ($inicioControle && $mesAlvo->lt($inicioControle->copy()->startOfMonth())) {
            return 0;
        }

        return (int) FolgaMovimento::where('user_id', $user->id)
            ->where('referencia_mes', $mes)
            ->where('referencia_ano', $ano)
            ->where('tipo', 'ajuste')
            ->when($inicioControle, fn ($q) => $q->whereDate('data', '>=', $inicioControle->format('Y-m-d')))
            ->sum('quantidade');
    }

    /**
     * Computa o saldo do banco de domingos para o mês.
     * Regra: se nenhum domingo foi folgado no mês, ganha +1 crédito.
     * Folgas em domingo consomem 1 crédito do banco de domingos.
     * O banco também zera no marco zero do controle.
     */
    public function computeDomingoSaldo(User $user, int $mes, int $ano): array
    {
        $mesAtual = $this->computeMonthData($user, $mes, $ano);

        $inicioControle = $this->dataInicioControle();
        $mesAlvo = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();

        // Antes do marco zero: banco zerado
        if ($inicioControle && $mesAlvo->lt($inicioControle->copy()->startOfMonth())) {
            FolgaDomingoSaldo::updateOrCreate(
                ['user_id' => $user->id, 'mes' => $mes, 'ano' => $ano],
                ['creditos_ganhos' => 0, 'creditos_usados' => 0, 'saldo_mes' => 0, 'saldo_acumulado' => 0]
            );

            return [
                'creditos_ganhos' => 0,
                'creditos_usados' => 0,
                'saldo_anterior' => 0,
                'saldo_acumulado' => 0,
            ];
        }

        // Saldo acumulado do mês anterior (no mês de início do controle, parte do zero)
        if ($inicioControle && $mesAlvo->equalTo($inicioControle->copy()->startOfMonth())) {
            $saldoAnterior = 0;
        } else {
            $prevMes = $mes === 1 ? 12 : $mes - 1;
            $prevAno = $mes === 1 ? $ano - 1 : $ano;
            $saldoAnterior = FolgaDomingoSaldo::where('user_id', $user->id)
                ->where('mes', $prevMes)
                ->where('ano', $prevAno)
                ->value('saldo_acumulado') ?? 0;
        }

        // Créditos ganhos no mês: +1 se NENHUM domingo foi folgado
        $creditosGanhos = $mesAtual['folgas_domingo_tiradas'] === 0 ? 1 : 0;

        // Créditos usados no mês: folgas tiradas em domingos
        $creditosUsados = $mesAtual['folgas_domingo_tiradas'];

        $saldoMes = $creditosGanhos - $creditosUsados;
        $saldoAcumulado = $saldoAnterior + $saldoMes;

        // Gravar/atualizar snapshot
        FolgaDomingoSaldo::updateOrCreate(
            ['user_id' => $user->id, 'mes' => $mes, 'ano' => $ano],
            [
                'creditos_ganhos' => $creditosGanhos,
                'creditos_usados' => $creditosUsados,
                'saldo_mes' => $saldoMes,
                'saldo_acumulado' => $saldoAcumulado,
            ]
        );

        return [
            'creditos_ganhos' => $creditosGanhos,
            'creditos_usados' => $creditosUsados,
            'saldo_anterior' => $saldoAnterior,
            'saldo_acumulado' => $saldoAcumulado,
        ];
    }

    /**
     * Saldo acumulado do mês anterior.
     *
     * Usa o snapshot gravado quando existe e está confiável; caso contrário
     * recalcula a cadeia sob demanda para não perder folgas pendentes nem
     * deixar de debitar programações cuja data já chegou.
     * Snapshots antigos com saldo negativo são tratados como zero.
     */
    public function getSaldoAnterior(User $user, int $mes, int $ano): int
    {
        [$prevMes, $prevAno] = $this->mesAnterior($mes, $ano);

        // Marco zero: no próprio mês inicial o saldo anterior é o saldo inicial
        // informado; em meses anteriores não há histórico (saldo 0).
        $inicioControle = $this->dataInicioControle();
        if ($inicioControle) {
            $mesInicio = $inicioControle->copy()->startOfMonth();
            $alvo = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();

            if ($alvo->equalTo($mesInicio)) {
                return max(0, (int) $user->saldo_inicial_folgas);
            }

            if ($alvo->lt($mesInicio)) {
                return 0;
            }
        }

        $snapshot = FolgaSaldoMensal::where('user_id', $user->id)
            ->where('mes', $prevMes)
            ->where('ano', $prevAno)
            ->first();

        if ($snapshot
            && ! $this->mesTemProgramacao($user, $prevMes, $prevAno)
            && $this->snapshotConfiavel($snapshot, $prevMes, $prevAno)) {
            return max(0, (int) $snapshot->saldo_acumulado);
        }

        return $this->computeSaldoHistorico($user, $prevMes, $prevAno);
    }

    /**
     * Snapshot de mês fechado é confiável; mês em curso (créditos entram dia a
     * dia) ou com recálculo anterior ao fim do mês é recalculado ao vivo.
     */
    private function snapshotConfiavel(FolgaSaldoMensal $snapshot, int $mes, int $ano): bool
    {
        $fimMes = Carbon::createFromDate($ano, $mes, 1)->endOfMonth()->startOfDay();

        // Mês ainda em curso/futuro: o saldo real muda a cada dia
        if ($fimMes->isFuture()) {
            return false;
        }

        return $snapshot->updated_at !== null && $snapshot->updated_at->gte($fimMes);
    }

    /**
     * O mês tem alguma programação que pode alterar o saldo? Nesse caso o
     * snapshot do mês não é confiável até o fim do período programado.
     */
    private function mesTemProgramacao(User $user, int $mes, int $ano): bool
    {
        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfDay();
        $fim = $inicio->copy()->endOfMonth();

        return FolgaProgramacao::where('user_id', $user->id)
            ->whereDate('data_inicio', '<=', $fim->format('Y-m-d'))
            ->whereDate('data_fim', '>=', $inicio->format('Y-m-d'))
            ->exists();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function mesAnterior(int $mes, int $ano): array
    {
        return $mes === 1 ? [12, $ano - 1] : [$mes - 1, $ano];
    }

    /**
     * Calcula (sem gravar) o saldo acumulado de um mês percorrendo a cadeia
     * de meses anteriores. Meses sem lançamento contam como trabalho presumido,
     * então a recursão só para quando não há nenhum histórico até o mês.
     * Excesso de folgas em um mês zera o banco e o mês seguinte recomeça do zero.
     */
    private function computeSaldoHistorico(User $user, int $mes, int $ano): int
    {
        $fimMes = Carbon::createFromDate($ano, $mes, 1)->endOfMonth();

        if (! $this->temHistoricoAte($user, $fimMes)) {
            return 0;
        }

        $competencia = $this->computeMonthData($user, $mes, $ano);

        $ajustes = $this->somaAjustes($user, $mes, $ano);

        return max(0, $this->getSaldoAnterior($user, $mes, $ano)
            + $competencia['previstas_ganhas']
            + $ajustes
            - $competencia['tiradas']);
    }

    /**
     * Existe histórico do motorista até o fim do mês informado?
     *
     * Com marco zero definido, o controle começa na data informada (meses
     * anteriores não contam e meses a partir dela contam, mesmo sem lançamentos,
     * por causa do trabalho presumido e do saldo inicial).
     */
    private function temHistoricoAte(User $user, Carbon $fimMes): bool
    {
        $inicioControle = $this->dataInicioControle();

        if ($inicioControle) {
            return $fimMes->gte($inicioControle);
        }

        if ($user->ultima_folga_data && $user->ultima_folga_data->lte($fimMes)) {
            return true;
        }

        if (FolgaDia::where('user_id', $user->id)->where('data', '<=', $fimMes->format('Y-m-d'))->exists()) {
            return true;
        }

        if (FolgaProgramacao::where('user_id', $user->id)
            ->whereDate('data_inicio', '<=', $fimMes->format('Y-m-d'))
            ->exists()) {
            return true;
        }

        return FolgaMovimento::where('user_id', $user->id)
            ->where(function ($q) use ($fimMes) {
                $q->where('referencia_ano', '<', (int) $fimMes->year)
                    ->orWhere(function ($q2) use ($fimMes) {
                        $q2->where('referencia_ano', (int) $fimMes->year)
                            ->where('referencia_mes', '<=', (int) $fimMes->month);
                    });
            })
            ->exists();
    }
}
