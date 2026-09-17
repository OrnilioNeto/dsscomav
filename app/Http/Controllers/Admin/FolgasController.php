<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FolgaDia;
use App\Models\FolgaLog;
use App\Models\FolgaMovimento;
use App\Models\FolgaSetting;
use App\Models\User;
use App\Services\FolgaBankService;
use App\Services\FolgaRulesService;
use Illuminate\Http\Request;

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
                }
            }

            $snapshot = $this->rules->computeSnapshot($motorista, $mes, $ano);

            // Última folga do motorista (qualquer mês — âncora dos dias contínuos)
            $ultimaFolgaReal = FolgaDia::where('user_id', $motorista->id)
                ->where('tipo', 'folga')
                ->orderByDesc('data')
                ->value('data');

            // Contagens do mês
            $tiradasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'folga')->count();
            $atestadosMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'atestado')->count();
            $licencasMes = FolgaDia::where('user_id', $motorista->id)
                ->whereMonth('data', $mes)->whereYear('data', $ano)
                ->where('tipo', 'licenca')->count();

            $dados[$motorista->id] = array_merge($snapshot, [
                'ultima_folga_real' => $ultimaFolgaReal,
                'tiradas_mes' => $tiradasMes,
                'atestados_mes' => $atestadosMes,
                'licencas_mes' => $licencasMes,
            ]);
        }

        $settings = FolgaSetting::firstOrCreateDefault();

        $totalMotoristas = $motoristas->count();
        $totalPrevistasMes = collect($dados)->sum('previstas_mes');
        $totalPrevistas = collect($dados)->sum('previstas');
        $totalTiradasMes = collect($dados)->sum('tiradas_mes');
        $totalSaldo = collect($dados)->sum('saldo_acumulado');
        $totalAjustes = collect($dados)->sum('ajustes');
        $totalDomingoPendente = collect($dados)->where('domingo_cumprido', false)->count();

        return view('admin.folgas.index', compact(
            'motoristas', 'dados', 'mes', 'ano', 'busca', 'settings',
            'totalMotoristas', 'totalPrevistasMes', 'totalPrevistas', 'totalTiradasMes', 'totalSaldo', 'totalAjustes', 'totalDomingoPendente'
        ));
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

        return response()->json([
            'snapshot' => $snapshot,
            'dias' => $dias->values(),
            'movimentos' => $movimentos,
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

        // Recalcular o mês da data informada
        $this->bank->recalcularMes($user, $mes, $ano, auth()->id());

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

            $this->bank->recalcularMes($user, $mes, $ano, auth()->id());

            $importados++;
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

        return view('admin.folgas.configuracoes', compact('settings'));
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
