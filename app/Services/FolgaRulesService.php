<?php

namespace App\Services;

use App\Models\FolgaDia;
use App\Models\FolgaDomingoSaldo;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\FolgaSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FolgaRulesService
{
    private FolgaSetting $settings;

    private int $diasParaFolga;

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
     * Calcula streak, créditos previstos e contagens para um motorista no mês.
     */
    public function computeMonthData(User $user, int $mes, int $ano): array
    {
        $records = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get()
            ->keyBy(fn ($r) => $r->data->format('Y-m-d'));

        $diasMes = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $streak = 0;
        $previstas = 0;
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

        for ($d = 1; $d <= $diasMes; $d++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
            $record = $records->get($data);
            $tipo = $record?->tipo ?? 'trabalho';

            if ($tipo === 'trabalho') {
                $diasTrabalhados++;
                $streak++;
                if ($streak >= $this->diasParaFolga) {
                    $previstas++;
                    $streak = 0;
                }
            } elseif ($tipo === 'atestado') {
                $diasAtestado++;
                $streak = 0;
            } elseif ($tipo === 'licenca') {
                $diasLicenca++;
                $streak = 0;
            } elseif ($tipo === 'folga') {
                $diasFolga++;
                $streak = 0;
            }
        }

        $diasFolgaMes = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->where('tipo', 'folga')
            ->count();

        $domingoCumprido = FolgaDia::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->where('tipo', 'folga')
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
            ->whereNotNull('domingo_ref')
            ->count();

        return [
            'previstas' => $previstas,
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
     * Conta dias trabalhados contínuos DESDE a última folga/atestado/licença
     * até o mês selecionado (dinâmico por mês).
     * - Mês atual: conta até hoje.
     * - Mês futuro/passado: conta até o fim do mês selecionado.
     * Dias sem registro contam como trabalho. Todo dia calendário conta (6x1).
     */
    public function diasContinuos(User $user, int $mes, int $ano): int
    {
        $inicioMes = Carbon::createFromDate($ano, $mes, 1);
        $fimMes = $inicioMes->copy()->endOfMonth();
        $hoje = now()->startOfDay();

        // Última interrupção (folga/atestado/licença) registrada até o fim do mês selecionado
        $ultimaInterrupcao = FolgaDia::where('user_id', $user->id)
            ->where('tipo', '!=', 'trabalho')
            ->where('data', '<=', $fimMes->format('Y-m-d'))
            ->orderByDesc('data')
            ->value('data');

        // Considerar também a última folga registrada manualmente (users.ultima_folga_data)
        $ultimaFolgaUser = $user->ultima_folga_data;
        if ($ultimaFolgaUser) {
            $uf = Carbon::parse($ultimaFolgaUser);
            if ($uf->lte($fimMes) && (! $ultimaInterrupcao || $uf->gt(Carbon::parse($ultimaInterrupcao)))) {
                $ultimaInterrupcao = $uf;
            }
        }

        // Sem interrupção conhecida: contar todos os dias do mês selecionado
        if (! $ultimaInterrupcao) {
            return $inicioMes->diffInDays($fimMes) + 1;
        }

        // Começa a contar no dia seguinte à última interrupção
        $dataAtual = Carbon::parse($ultimaInterrupcao)->addDay();
        if ($dataAtual->gt($fimMes)) {
            return 0;
        }

        // Mês atual: conta até hoje; outros meses: conta até o fim do mês
        $dataFim = ($mes === (int) $hoje->month && $ano === (int) $hoje->year) ? $hoje : $fimMes;

        $count = 0;
        while ($dataAtual->lte($dataFim)) {
            $registro = FolgaDia::where('user_id', $user->id)
                ->where('data', $dataAtual->format('Y-m-d'))
                ->first();

            $tipo = $registro?->tipo ?? 'trabalho';

            if ($tipo === 'trabalho') {
                $count++;
            } else {
                $count = 0;
            }

            $dataAtual->addDay();
        }

        return $count;
    }

    /**
     * Computa snapshot mensal completo com saldo acumulado.
     * Retorna dados da competência (mês atual) E totais acumulados (com saldo anterior).
     */
    public function computeSnapshot(User $user, int $mes, int $ano): array
    {
        $data = $this->computeMonthData($user, $mes, $ano);

        $saldoAnterior = $this->getSaldoAnterior($user, $mes, $ano);

        $ajustes = FolgaMovimento::where('user_id', $user->id)
            ->where('referencia_mes', $mes)
            ->where('referencia_ano', $ano)
            ->where('tipo', 'ajuste')
            ->sum('quantidade');

        // Competência (mês atual)
        $previstasMes = $data['previstas'];
        $tiradasMes = $data['tiradas'];

        // Acumulado: previstas = saldo anterior + ganhas no mês + ajustes (crédito/débito)
        // Regra de consistência: Prévistas − Tiradas = Saldo acumulado
        $previstasTotal = $saldoAnterior + $previstasMes + $ajustes;
        $tiradasTotal = $tiradasMes; // tiradas são sempre do mês
        $saldoAcumulado = $previstasTotal - $tiradasMes;

        // Banco de domingos
        $domingoSaldo = $this->computeDomingoSaldo($user, $mes, $ano);

        return array_merge($data, [
            // Competência (este mês)
            'previstas_mes' => $previstasMes,
            'tiradas_mes' => $tiradasMes,
            'saldo_anterior' => $saldoAnterior,
            'ajustes' => (int) $ajustes,
            // Acumulado (total disponível)
            'previstas' => $previstasTotal,
            'tiradas' => $tiradasTotal,
            'saldo_acumulado' => $saldoAcumulado,
            // Banco de domingos
            'domingo_saldo' => $domingoSaldo['saldo_acumulado'],
            'domingo_saldo_anterior' => $domingoSaldo['saldo_anterior'],
            'domingo_creditos_ganhos' => $domingoSaldo['creditos_ganhos'],
            'domingo_creditos_usados' => $domingoSaldo['creditos_usados'],
        ]);
    }

    /**
     * Computa o saldo do banco de domingos para o mês.
     * Regra: se nenhum domingo foi folgado no mês, ganha +1 crédito.
     * Folgas em domingo consomem 1 crédito do banco de domingos.
     */
    public function computeDomingoSaldo(User $user, int $mes, int $ano): array
    {
        $mesAtual = $this->computeMonthData($user, $mes, $ano);

        // Saldo acumulado do mês anterior
        $prevMes = $mes === 1 ? 12 : $mes - 1;
        $prevAno = $mes === 1 ? $ano - 1 : $ano;
        $saldoAnterior = FolgaDomingoSaldo::where('user_id', $user->id)
            ->where('mes', $prevMes)
            ->where('ano', $prevAno)
            ->value('saldo_acumulado') ?? 0;

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
     * Saldo acumulado do mês anterior (ou 0 se não existir snapshot).
     */
    private function getSaldoAnterior(User $user, int $mes, int $ano): int
    {
        $prevMes = $mes === 1 ? 12 : $mes - 1;
        $prevAno = $mes === 1 ? $ano - 1 : $ano;

        $snapshot = FolgaSaldoMensal::where('user_id', $user->id)
            ->where('mes', $prevMes)
            ->where('ano', $prevAno)
            ->first();

        return $snapshot?->saldo_acumulado ?? 0;
    }
}
