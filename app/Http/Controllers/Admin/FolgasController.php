<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolgaDia;
use App\Models\FolgaDomingoSaldo;
use App\Models\FolgaLog;
use App\Models\FolgaMovimento;
use App\Models\FolgaProgramacao;
use App\Models\FolgaSaldoMensal;
use App\Models\FolgaSetting;
use App\Models\User;
use App\Models\UserVacation;
use App\Services\FolgaBankService;
use App\Services\FolgaRulesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FolgasController extends Controller
{
    private FolgaRulesService $rules;

    private FolgaBankService $bank;

    public function __construct(FolgaRulesService $rules, FolgaBankService $bank)
    {
        FolgaRulesService::ensureTablesExist();
        $this->rules = $rules;
        $this->bank = $bank;
        $this->middleware('permission:folgas,view')->only(['index', 'motoristaDados', 'auditoria']);
        $this->middleware('permission:folgas,edit')->except(['index', 'motoristaDados', 'auditoria']);
    }

    public function index(Request $request)
    {
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);
        $busca = $request->input('busca', '');

        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->when($busca, fn ($q, $b) => $q->where(function ($q2) use ($b) {
                $q2->where('nome', 'like', "%{$b}%")
                    ->orWhere('cpf', 'like', "%{$b}%");
            }))
            ->orderBy('nome')
            ->get();

        $dados = [];
        $inicioControle = $this->rules->dataInicioControle();
        $filtroInicio = fn ($q) => $inicioControle
            ? $q->whereDate('data', '>=', $inicioControle->format('Y-m-d'))
            : $q;

        foreach ($motoristas as $motorista) {
            // Auto-corrigir: se tem ultima_folga_data mas não tem registro no folga_dias, criar
            if ($motorista->ultima_folga_data) {
                $ufDate = $motorista->ultima_folga_data->format('Y-m-d');
                $temRegistro = FolgaDia::where('user_id', $motorista->id)
                    ->where('data', $ufDate)
                    ->where('tipo', 'folga')
                    ->exists();
                if (! $temRegistro) {
                    FolgaDia::create([
                        'user_id' => $motorista->id,
                        'data' => $ufDate,
                        'tipo' => 'folga',
                        'motivo_folga' => 'Última folga registrada',
                        'origem' => 'sistema',
                        'lancado_por' => auth()->id(),
                        'lancado_em' => now(),
                    ]);

                    $this->bank->recalcularMes(
                        $motorista,
                        (int) $motorista->ultima_folga_data->month,
                        (int) $motorista->ultima_folga_data->year,
                        auth()->id()
                    );
                }
            }

            $snapshot = $this->rules->computeSnapshot($motorista, $mes, $ano);

            // Última folga do motorista (qualquer mês — âncora dos dias contínuos)
            $ultimaFolgaReal = FolgaDia::where('user_id', $motorista->id)
                ->where('tipo', 'folga')
                ->orderByDesc('data')
                ->value('data');

            // Contagens do mês (respeitando o marco zero do controle)
            $tiradasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'folga')->when($inicioControle, $filtroInicio)->count();
            $atestadosMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'atestado')->when($inicioControle, $filtroInicio)->count();
            $licencasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'licenca')->when($inicioControle, $filtroInicio)->count();

            $dados[$motorista->id] = array_merge($snapshot, [
                'ultima_folga_real' => $ultimaFolgaReal,
                'tiradas_mes' => $tiradasMes,
                'atestados_mes' => $atestadosMes,
                'licencas_mes' => $licencasMes,
            ]);
        }

        $settings = FolgaSetting::firstOrCreateDefault();

        $hoje = now()->startOfDay();
        $limiteProximas = $hoje->copy()->addDays(7);

        $programacoes = FolgaProgramacao::with('user')
            ->where('status', 'ativa')
            ->whereDate('data_fim', '>=', $hoje->format('Y-m-d'))
            ->orderBy('data_inicio')
            ->get();

        // A coluna Programação da tabela mostra só o que cai no mês exibido
        $inicioMes = Carbon::createFromDate($ano, $mes, 1)->startOfDay();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $programacoesPorMotorista = $programacoes
            ->filter(fn ($p) => $p->data_inicio->lte($fimMes) && $p->data_fim->gte($inicioMes))
            ->groupBy('user_id');

        $totalProgramacoesProximas = $programacoes->filter(
            fn ($p) => $p->data_inicio->lte($limiteProximas) && $p->data_fim->gte($hoje)
        )->count();

        // Férias (atuais/próximas) por motorista — selo na tabela e alerta no lançamento
        $feriasPorMotorista = $this->feriasPorMotorista($motoristas, $hoje);

        $previsaoMes = $this->rules->previsaoMes($mes, $ano);

        $totalMotoristas = $motoristas->count();
        $totalPrevistasMes = collect($dados)->sum('previstas_mes');
        $totalPrevistas = collect($dados)->sum('previstas');
        $totalPrevistasGanhas = collect($dados)->sum('previstas_ganhas');
        $totalTiradasMes = collect($dados)->sum('tiradas_mes');
        $totalSaldo = collect($dados)->sum('saldo_acumulado');
        $totalAjustes = collect($dados)->sum('ajustes');
        $totalDomingoPendente = collect($dados)->where('domingo_cumprido', false)->count();

        return view('admin.folgas.index', compact(
            'motoristas', 'dados', 'mes', 'ano', 'busca', 'settings',
            'totalMotoristas', 'totalPrevistasMes', 'totalPrevistas', 'totalPrevistasGanhas', 'totalTiradasMes', 'totalSaldo', 'totalAjustes', 'totalDomingoPendente',
            'programacoes', 'programacoesPorMotorista', 'totalProgramacoesProximas',
            'feriasPorMotorista', 'previsaoMes'
        ));
    }

    /**
     * Férias vigentes/futuras por motorista (para selo na tabela e alerta de lançamento).
     *
     * @param  Collection<int, User>  $motoristas
     * @return array<int, array<int, array{inicio: string, fim: string}>>
     */
    private function feriasPorMotorista($motoristas, Carbon $hoje): array
    {
        $ferias = UserVacation::whereIn('user_id', $motoristas->pluck('id'))->get();
        $hojeStr = $hoje->format('Y-m-d');
        $resultado = [];

        foreach ($motoristas as $motorista) {
            $periodos = [];

            if ($motorista->ferias_inicio && $motorista->ferias_fim && $motorista->ferias_fim->format('Y-m-d') >= $hojeStr) {
                $periodos[] = [
                    'inicio' => $motorista->ferias_inicio->format('Y-m-d'),
                    'fim' => $motorista->ferias_fim->format('Y-m-d'),
                ];
            }

            foreach ($ferias->where('user_id', $motorista->id) as $periodo) {
                if ($periodo->data_fim->format('Y-m-d') >= $hojeStr) {
                    $periodos[] = [
                        'inicio' => $periodo->data_inicio->format('Y-m-d'),
                        'fim' => $periodo->data_fim->format('Y-m-d'),
                    ];
                }
            }

            $resultado[$motorista->id] = collect($periodos)
                ->unique(fn ($p) => $p['inicio'].$p['fim'])
                ->sortBy('inicio')
                ->values()
                ->all();
        }

        return $resultado;
    }

    public function motoristaDados(Request $request)
    {
        $userId = (int) $request->input('user_id');
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);

        $user = User::findOrFail($userId);
        $snapshot = $this->rules->computeSnapshot($user, $mes, $ano);

        $dias = FolgaDia::where('user_id', $userId)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->orderBy('data')
            ->get()
            ->keyBy(fn ($d) => $d->data->format('Y-m-d'));

        $movimentos = FolgaMovimento::where('user_id', $userId)
            ->where('referencia_mes', $mes)
            ->where('referencia_ano', $ano)
            ->orderByDesc('created_at')
            ->get();

        $inicioMes = Carbon::createFromDate($ano, $mes, 1);
        $programacoes = FolgaProgramacao::where('user_id', $userId)
            ->where('status', 'ativa')
            ->whereDate('data_inicio', '<=', $inicioMes->copy()->endOfMonth()->format('Y-m-d'))
            ->whereDate('data_fim', '>=', $inicioMes->format('Y-m-d'))
            ->orderBy('data_inicio')
            ->get(['id', 'data_inicio', 'data_fim', 'tipo', 'motivo']);

        $ferias = collect($this->rules->periodosFerias($user))
            ->map(fn ($periodo) => [
                'inicio' => $periodo[0]->format('Y-m-d'),
                'fim' => $periodo[1]->format('Y-m-d'),
            ])
            ->values();

        return response()->json([
            'snapshot' => $snapshot,
            'dias' => $dias->values(),
            'movimentos' => $movimentos,
            'programacoes' => $programacoes,
            'ferias' => $ferias,
            'ultima_folga_data' => $user->ultima_folga_data?->format('Y-m-d'),
        ]);
    }

    public function storeDia(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data' => 'required|date',
            'tipo' => 'required|in:trabalho,folga,atestado,licenca',
            'motivo_folga' => 'nullable|string|max:255',
            'domingo_ref' => 'nullable|date',
            'observacao' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);
        $data = $request->data;
        $mes = (int) date('m', strtotime($data));
        $ano = (int) date('Y', strtotime($data));

        $antes = FolgaDia::where('user_id', $user->id)->where('data', $data)->first();
        $antesData = $antes?->toArray();

        $dia = FolgaDia::updateOrCreate(
            ['user_id' => $user->id, 'data' => $data],
            [
                'tipo' => $request->tipo,
                'motivo_folga' => $request->motivo_folga,
                'domingo_ref' => $request->domingo_ref,
                'observacao' => $request->observacao,
                'origem' => 'manual',
                'lancado_por' => auth()->id(),
                'lancado_em' => now(),
            ]
        );

        $this->bank->log(
            $antes ? 'editar_dia' : 'criar_dia',
            $user->id,
            'folga_dias',
            $dia->id,
            ['antes' => $antesData, 'depois' => $dia->toArray()],
            auth()->id()
        );

        // Se for folga, registrar movimento de débito
        if ($request->tipo === 'folga' && (! $antes || $antes->tipo !== 'folga')) {
            $this->bank->registrarMovimento($user, $data, 'debito_folga', 1, $mes, $ano, $request->motivo_folga ?? 'Folga registrada', auth()->id());
        }

        // Se mudou de folga para outro tipo, estornar o débito
        if ($antes && $antes->tipo === 'folga' && $request->tipo !== 'folga') {
            $ultimoDebito = FolgaMovimento::where('user_id', $user->id)
                ->where('data', $data)
                ->where('tipo', 'debito_folga')
                ->first();
            if ($ultimoDebito) {
                $this->bank->estornarMovimento($ultimoDebito, auth()->id());
            }
        }

        $this->bank->recalcularMes($user, $mes, $ano, auth()->id());

        return redirect()->back()->with('success', 'Lançamento salvo com sucesso!');
    }

    public function storePeriodo(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'tipo' => 'required|in:trabalho,folga,atestado,licenca',
            'motivo_folga' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);

        $inicio = Carbon::parse($request->data_inicio)->startOfDay();
        $fim = Carbon::parse($request->data_fim)->startOfDay();

        $totalDias = $inicio->diffInDays($fim) + 1;
        if ($totalDias > 60) {
            return redirect()->back()->with('error', "Período muito longo ({$totalDias} dias). Máximo de 60 dias por lançamento.");
        }

        $mesRecalculo = (int) $inicio->month;
        $anoRecalculo = (int) $inicio->year;
        $registros = 0;
        $cursor = $inicio->copy();

        while ($cursor->lte($fim)) {
            $data = $cursor->format('Y-m-d');
            $mes = (int) $cursor->month;
            $ano = (int) $cursor->year;

            $antes = FolgaDia::where('user_id', $user->id)->whereDate('data', $data)->first();
            $antesData = $antes?->toArray();
            $eraFolga = $antes && $antes->tipo === 'folga';

            if ($antes) {
                $antes->update([
                    'tipo' => $request->tipo,
                    'motivo_folga' => $request->motivo_folga,
                    'observacao' => $request->observacao,
                    'origem' => 'manual',
                    'lancado_por' => auth()->id(),
                    'lancado_em' => now(),
                ]);
                $dia = $antes;
            } else {
                $dia = FolgaDia::create([
                    'user_id' => $user->id,
                    'data' => $data,
                    'tipo' => $request->tipo,
                    'motivo_folga' => $request->motivo_folga,
                    'observacao' => $request->observacao,
                    'origem' => 'manual',
                    'lancado_por' => auth()->id(),
                    'lancado_em' => now(),
                ]);
            }

            $this->bank->log(
                $antes ? 'editar_dia' : 'criar_dia',
                $user->id,
                'folga_dias',
                $dia->id,
                [
                    'antes' => $antesData,
                    'depois' => $dia->toArray(),
                    'periodo' => "{$request->data_inicio} a {$request->data_fim}",
                ],
                auth()->id()
            );

            // Ajustar movimentos quando o tipo muda (sem recalcular por dia)
            if ($request->tipo === 'folga' && ! $eraFolga) {
                $this->bank->registrarMovimento($user, $data, 'debito_folga', 1, $mes, $ano, $request->motivo_folga ?? 'Folga em período', auth()->id());
            } elseif ($eraFolga && $request->tipo !== 'folga') {
                $ultimoDebito = FolgaMovimento::where('user_id', $user->id)
                    ->whereDate('data', $data)
                    ->where('tipo', 'debito_folga')
                    ->first();
                if ($ultimoDebito) {
                    $this->bank->estornarMovimento($ultimoDebito, auth()->id(), false);
                }
            }

            $registros++;
            $cursor->addDay();
        }

        // Recálculo único a partir do mês mais antigo do período (a cascata cobre os demais)
        $this->bank->recalcularMes($user, $mesRecalculo, $anoRecalculo, auth()->id());

        return redirect()->back()->with('success', "Período lançado: {$registros} dia(s) como '{$request->tipo}'.");
    }

    public function destroyPeriodo(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        $user = User::findOrFail($request->user_id);

        $inicio = Carbon::parse($request->data_inicio)->startOfDay();
        $fim = Carbon::parse($request->data_fim)->startOfDay();

        $totalDias = $inicio->diffInDays($fim) + 1;
        if ($totalDias > 60) {
            return redirect()->back()->with('error', "Período muito longo ({$totalDias} dias). Máximo de 60 dias por operação.");
        }

        $dias = FolgaDia::where('user_id', $user->id)
            ->whereDate('data', '>=', $inicio->format('Y-m-d'))
            ->whereDate('data', '<=', $fim->format('Y-m-d'))
            ->get();

        $removidos = 0;

        foreach ($dias as $dia) {
            $this->bank->log(
                'excluir_dia',
                $user->id,
                'folga_dias',
                $dia->id,
                ['dados' => $dia->toArray(), 'periodo' => "{$inicio->format('Y-m-d')} a {$fim->format('Y-m-d')}"],
                auth()->id()
            );

            if ($dia->tipo === 'folga') {
                $ultimoDebito = FolgaMovimento::where('user_id', $user->id)
                    ->whereDate('data', $dia->data->format('Y-m-d'))
                    ->where('tipo', 'debito_folga')
                    ->first();
                if ($ultimoDebito) {
                    $this->bank->estornarMovimento($ultimoDebito, auth()->id(), false);
                }
            }

            $dia->delete();
            $removidos++;
        }

        if ($removidos > 0) {
            $this->bank->recalcularMes($user, (int) $inicio->month, (int) $inicio->year, auth()->id());

            return redirect()->back()->with('success', "{$removidos} lançamento(s) desfeito(s) no período.");
        }

        return redirect()->back()->with('error', 'Nenhum lançamento encontrado no período informado.');
    }

    public function storeProgramacao(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'tipo' => 'required|in:folga,atestado,licenca',
            'motivo' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);

        $inicio = Carbon::parse($request->data_inicio)->startOfDay();
        $fim = Carbon::parse($request->data_fim)->startOfDay();

        $totalDias = $inicio->diffInDays($fim) + 1;
        if ($totalDias > 90) {
            return redirect()->back()->with('error', "Período muito longo ({$totalDias} dias). Máximo de 90 dias por programação.");
        }

        $programacao = FolgaProgramacao::create([
            'user_id' => $user->id,
            'data_inicio' => $inicio->format('Y-m-d'),
            'data_fim' => $fim->format('Y-m-d'),
            'tipo' => $request->tipo,
            'motivo' => $request->motivo,
            'observacao' => $request->observacao,
            'status' => 'ativa',
            'created_by' => auth()->id(),
        ]);

        $this->bank->log('criar_programacao', $user->id, 'folga_programacoes', $programacao->id, $programacao->toArray(), auth()->id());

        // O débito é automático conforme a data chega; recalcula o mês inicial
        $this->bank->recalcularMes($user, (int) $inicio->month, (int) $inicio->year, auth()->id());

        return redirect()->back()->with(
            'success',
            "Programação criada para {$user->nome}: {$inicio->format('d/m')} a {$fim->format('d/m')}."
        );
    }

    public function cancelarProgramacao($id)
    {
        $programacao = FolgaProgramacao::findOrFail($id);

        if ($programacao->isCancelada()) {
            return redirect()->back()->with('error', 'Esta programação já está anulada.');
        }

        $programacao->update([
            'status' => 'cancelada',
            'cancelada_em' => now()->startOfDay()->format('Y-m-d'),
            'cancelada_por' => auth()->id(),
        ]);

        $this->bank->log('cancelar_programacao', $programacao->user_id, 'folga_programacoes', $programacao->id, $programacao->toArray(), auth()->id());

        $this->bank->recalcularMes(
            $programacao->user,
            (int) $programacao->data_inicio->month,
            (int) $programacao->data_inicio->year,
            auth()->id()
        );

        return redirect()->back()->with('success', 'Programação anulada. Dias anteriores a hoje continuam debitados.');
    }

    public function updateDia(Request $request, $id)
    {
        $dia = FolgaDia::findOrFail($id);
        $antes = $dia->toArray();

        $request->validate([
            'tipo' => 'required|in:trabalho,folga,atestado,licenca',
            'motivo_folga' => 'nullable|string|max:255',
            'domingo_ref' => 'nullable|date',
            'observacao' => 'nullable|string',
        ]);

        $mes = (int) $dia->data->format('m');
        $ano = (int) $dia->data->format('Y');
        $eraFolga = $dia->tipo === 'folga';

        $dia->update([
            'tipo' => $request->tipo,
            'motivo_folga' => $request->motivo_folga,
            'domingo_ref' => $request->domingo_ref,
            'observacao' => $request->observacao,
            'lancado_por' => auth()->id(),
            'lancado_em' => now(),
        ]);

        $this->bank->log('editar_dia', $dia->user_id, 'folga_dias', $dia->id, ['antes' => $antes, 'depois' => $dia->toArray()], auth()->id());

        // Ajustar movimentos se tipo mudou
        if ($eraFolga && $request->tipo !== 'folga') {
            $ultimoDebito = FolgaMovimento::where('user_id', $dia->user_id)
                ->where('data', $dia->data)
                ->where('tipo', 'debito_folga')
                ->first();
            if ($ultimoDebito) {
                $this->bank->estornarMovimento($ultimoDebito, auth()->id());
            }
        } elseif (! $eraFolga && $request->tipo === 'folga') {
            $this->bank->registrarMovimento($dia->user, (string) $dia->data->format('Y-m-d'), 'debito_folga', 1, $mes, $ano, $request->motivo_folga ?? 'Folga registrada', auth()->id());
        }

        $this->bank->recalcularMes($dia->user, $mes, $ano, auth()->id());

        return redirect()->back()->with('success', 'Lançamento atualizado!');
    }

    public function destroyDia($id)
    {
        $dia = FolgaDia::findOrFail($id);
        $mes = (int) $dia->data->format('m');
        $ano = (int) $dia->data->format('Y');

        $this->bank->log('excluir_dia', $dia->user_id, 'folga_dias', $dia->id, ['dados' => $dia->toArray()], auth()->id());

        if ($dia->tipo === 'folga') {
            $ultimoDebito = FolgaMovimento::where('user_id', $dia->user_id)
                ->where('data', $dia->data)
                ->where('tipo', 'debito_folga')
                ->first();
            if ($ultimoDebito) {
                $this->bank->estornarMovimento($ultimoDebito, auth()->id());
            }
        }

        $dia->delete();
        $this->bank->recalcularMes($dia->user, $mes, $ano, auth()->id());

        return redirect()->back()->with('success', 'Lançamento removido!');
    }

    public function ajuste(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo_ajuste' => 'nullable|in:credito,debito',
            'quantidade' => 'required|integer',
            'justificativa' => 'required|string|min:3',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $user = User::findOrFail($request->user_id);

        // Aplica o sinal conforme o tipo de ajuste (crédito + / débito −)
        $quantidade = (int) $request->quantidade;
        if ($request->tipo_ajuste === 'debito') {
            $quantidade = -abs($quantidade);
        } else {
            $quantidade = abs($quantidade);
        }

        $this->bank->ajustarSaldo($user, $quantidade, $request->month, $request->year, $request->justificativa, auth()->id());

        $tipoLabel = $quantidade > 0 ? 'crédito' : 'débito';

        return redirect()->back()->with('success', "Ajuste de {$tipoLabel} registrado com sucesso!");
    }

    public function registrarUltimaFolga(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'ultima_folga_data' => 'required|date',
        ]);

        $user = User::findOrFail($request->user_id);
        $antes = $user->ultima_folga_data?->format('Y-m-d');
        $novaData = $request->ultima_folga_data;
        $mes = (int) date('m', strtotime($novaData));
        $ano = (int) date('Y', strtotime($novaData));

        // Remover registro automático anterior (origem sistema) em outra data
        FolgaDia::where('user_id', $user->id)
            ->where('origem', 'sistema')
            ->where('tipo', 'folga')
            ->where('data', '!=', $novaData)
            ->delete();

        // Criar/atualizar o registro de folga na data informada
        FolgaDia::updateOrCreate(
            ['user_id' => $user->id, 'data' => $novaData],
            [
                'tipo' => 'folga',
                'motivo_folga' => 'Última folga registrada',
                'origem' => 'sistema',
                'lancado_por' => auth()->id(),
                'lancado_em' => now(),
            ]
        );

        // Garantir movimento de débito correspondente (sem duplicar)
        $existeMovimento = FolgaMovimento::where('user_id', $user->id)
            ->where('data', $novaData)
            ->where('tipo', 'debito_folga')
            ->exists();
        if (! $existeMovimento) {
            $this->bank->registrarMovimento($user, $novaData, 'debito_folga', 1, $mes, $ano, 'Última folga registrada', auth()->id());
        }

        $user->update(['ultima_folga_data' => $novaData]);

        $this->bank->log(
            'registrar_ultima_folga',
            $user->id,
            'users',
            $user->id,
            ['antes' => $antes, 'depois' => $novaData],
            auth()->id()
        );

        // Recalcular a partir do mês mais antigo afetado (o registro removido
        // pode estar em um mês anterior); a cascata corrige os meses seguintes.
        $mesRecalculo = $mes;
        $anoRecalculo = $ano;
        if ($antes) {
            $mesAntes = (int) date('m', strtotime($antes));
            $anoAntes = (int) date('Y', strtotime($antes));
            if ($anoAntes < $anoRecalculo || ($anoAntes === $anoRecalculo && $mesAntes < $mesRecalculo)) {
                $mesRecalculo = $mesAntes;
                $anoRecalculo = $anoAntes;
            }
        }

        $this->bank->recalcularMes($user, $mesRecalculo, $anoRecalculo, auth()->id());

        return redirect()->back()->with('success', "Última folga registrada para {$user->nome}!");
    }

    public function recalcular(Request $request)
    {
        $mes = (int) $request->input('month', now()->month);
        $ano = (int) $request->input('year', now()->year);

        $motoristas = User::where('tipo_usuario', 'motorista')->where('status', 'ativo')->get();

        foreach ($motoristas as $motorista) {
            $this->bank->recalcularMes($motorista, $mes, $ano, auth()->id());
        }

        return redirect()->back()->with('success', "Recálculo do mês {$mes}/{$ano} concluído para {$motoristas->count()} motoristas!");
    }

    public function importar(Request $request)
    {
        $request->validate([
            'arquivo_csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('arquivo_csv');
        $content = file_get_contents($file->getRealPath());
        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines));

        $importados = 0;
        $erros = [];
        $recalcular = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line);
            $row = array_combine(array_slice($headers, 0, count($data)), $data);

            $cpf = preg_replace('/\D/', '', $row['cpf'] ?? '');
            $dataDia = $row['data'] ?? '';
            $tipo = $row['tipo'] ?? 'trabalho';
            $motivo = $row['motivo'] ?? null;
            $obs = $row['observacao'] ?? null;

            if (strlen($cpf) !== 11 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDia)) {
                $erros[] = 'Linha '.($i + 2).": CPF ou data inválido (CPF: {$cpf}, Data: {$dataDia})";

                continue;
            }

            if (! in_array($tipo, ['trabalho', 'folga', 'atestado', 'licenca'])) {
                $erros[] = 'Linha '.($i + 2).": Tipo inválido '{$tipo}'";

                continue;
            }

            $user = User::where('cpf', $cpf)->where('tipo_usuario', 'motorista')->first();
            if (! $user) {
                $erros[] = 'Linha '.($i + 2).": Motorista com CPF {$cpf} não encontrado";

                continue;
            }

            $mes = (int) date('m', strtotime($dataDia));
            $ano = (int) date('Y', strtotime($dataDia));

            $antes = FolgaDia::where('user_id', $user->id)->where('data', $dataDia)->first();

            FolgaDia::updateOrCreate(
                ['user_id' => $user->id, 'data' => $dataDia],
                [
                    'tipo' => $tipo,
                    'motivo_folga' => $motivo,
                    'observacao' => $obs,
                    'origem' => 'csv',
                    'lancado_por' => auth()->id(),
                    'lancado_em' => now(),
                ]
            );

            // Movimentos de banco
            if ($tipo === 'folga' && (! $antes || $antes->tipo !== 'folga')) {
                $this->bank->registrarMovimento($user, $dataDia, 'debito_folga', 1, $mes, $ano, $motivo ?? 'Importação CSV', auth()->id());
            } elseif ($antes && $antes->tipo === 'folga' && $tipo !== 'folga') {
                $ultimoDebito = FolgaMovimento::where('user_id', $user->id)
                    ->where('data', $dataDia)
                    ->where('tipo', 'debito_folga')
                    ->first();
                if ($ultimoDebito) {
                    $this->bank->estornarMovimento($ultimoDebito, auth()->id());
                }
            }

            $this->bank->log('importar_csv', $user->id, 'folga_dias', null, ['antes' => $antes?->toArray(), 'depois' => ['data' => $dataDia, 'tipo' => $tipo]], auth()->id());

            // Recalcular uma única vez por motorista (mês mais antigo afetado);
            // a cascata corrige os meses seguintes.
            if (! isset($recalcular[$user->id])
                || $ano < $recalcular[$user->id]['ano']
                || ($ano === $recalcular[$user->id]['ano'] && $mes < $recalcular[$user->id]['mes'])) {
                $recalcular[$user->id] = ['user' => $user, 'mes' => $mes, 'ano' => $ano];
            }

            $importados++;
        }

        foreach ($recalcular as $item) {
            $this->bank->recalcularMes($item['user'], $item['mes'], $item['ano'], auth()->id());
        }

        $msg = "Importação concluída: {$importados} registro(s) processado(s).";
        if (count($erros) > 0) {
            $msg .= ' '.count($erros).' erro(s) encontrado(s).';
        }

        return redirect()->back()->with($erros ? 'error' : 'success', $msg)->with('erros_importacao', $erros);
    }

    public function configuracoes()
    {
        $settings = FolgaSetting::firstOrCreateDefault();
        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        return view('admin.folgas.configuracoes', compact('settings', 'motoristas'));
    }

    public function updateConfiguracoes(Request $request)
    {
        $request->validate([
            'dias_para_folga' => 'required|integer|min:1|max:30',
            'exige_domingo' => 'nullable',
            'bloquear_sem_domingo' => 'nullable',
        ]);

        $settings = FolgaSetting::firstOrCreateDefault();
        $settings->update([
            'dias_para_folga' => $request->dias_para_folga,
            'exige_domingo' => $request->has('exige_domingo'),
            'bloquear_sem_domingo' => $request->has('bloquear_sem_domingo'),
        ]);

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    /**
     * Marco zero do banco: data de início do controle + saldo inicial de cada
     * motorista, salvos em lote de uma única vez.
     */
    public function updateSaldosIniciais(Request $request)
    {
        $request->validate([
            'data_inicio_controle' => 'required|date',
            'saldos' => 'nullable|array',
            'saldos.*' => 'nullable|integer|min:0|max:365',
        ]);

        $settings = FolgaSetting::firstOrCreateDefault();
        $novaData = Carbon::parse($request->data_inicio_controle)->startOfDay();

        $settings->update(['data_inicio_controle' => $novaData->format('Y-m-d')]);

        $saldos = (array) $request->input('saldos', []);

        $motoristas = User::where('tipo_usuario', 'motorista')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        $atualizados = 0;

        foreach ($motoristas as $motorista) {
            $novoSaldo = max(0, (int) ($saldos[$motorista->id] ?? 0));
            $saldoAntigo = (int) $motorista->saldo_inicial_folgas;

            if ($saldoAntigo === $novoSaldo) {
                continue;
            }

            $motorista->update(['saldo_inicial_folgas' => $novoSaldo]);

            $this->bank->log('configurar_saldo_inicial', $motorista->id, 'users', $motorista->id, [
                'antes' => $saldoAntigo,
                'depois' => $novoSaldo,
                'data_inicio_controle' => $novaData->format('Y-m-d'),
            ], auth()->id());

            $atualizados++;
        }

        // Zera o banco para trás: remove snapshots anteriores ao marco zero
        $mesInicio = (int) $novaData->month;
        $anoInicio = (int) $novaData->year;

        $antesDoInicio = function ($query) use ($mesInicio, $anoInicio) {
            $query->where('ano', '<', $anoInicio)
                ->orWhere(function ($q2) use ($mesInicio, $anoInicio) {
                    $q2->where('ano', $anoInicio)->where('mes', '<', $mesInicio);
                });
        };

        foreach ($motoristas as $motorista) {
            FolgaSaldoMensal::where('user_id', $motorista->id)->where($antesDoInicio)->delete();
            FolgaDomingoSaldo::where('user_id', $motorista->id)->where($antesDoInicio)->delete();
        }

        // Reconstrói do marco zero para frente
        foreach ($motoristas as $motorista) {
            $this->bank->recalcularMes($motorista, $mesInicio, $anoInicio, auth()->id());
        }

        return redirect()->back()->with(
            'success',
            "Controle iniciado em {$novaData->format('d/m/Y')}. {$atualizados} saldo(s) inicial(is) atualizado(s)."
        );
    }

    public function auditoria(Request $request)
    {
        $logs = FolgaLog::with(['user', 'creator'])
            ->when($request->input('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->input('acao'), fn ($q, $a) => $q->where('acao', $a))
            ->when($request->input('de'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('ate'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $motoristas = User::where('tipo_usuario', 'motorista')->where('status', 'ativo')->orderBy('nome')->get();

        return view('admin.folgas.auditoria', compact('logs', 'motoristas'));
    }
}
