<?php

namespace App\Modules\Frota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Models\FrotaJornada;
use App\Modules\Frota\Models\FrotaLiberacao;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Modules\Frota\Models\FrotaVeiculo;
use App\Modules\Frota\Services\AutotracClient;
use App\Modules\Frota\Services\JornadaSyncService;
use App\Support\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FrotaController extends Controller
{
    public function index(Request $request)
    {
        $query = FrotaMotorista::query()->with(['user', 'veiculoAtual']);

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $digitos = preg_replace('/\D/', '', $busca) ?? '';

            $query->where(function ($q) use ($busca, $digitos) {
                $q->where('name', 'like', '%'.$busca.'%');

                if ($digitos !== '') {
                    $q->orWhere('cpf', 'like', '%'.$digitos.'%');
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $motoristas = $query
            ->orderByRaw("CASE WHEN status = 'online' THEN 0 WHEN status = 'desconhecido' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $liberacoes = FrotaLiberacao::query()
            ->vigente()
            ->whereIn('user_id', $motoristas->getCollection()->pluck('user_id')->filter())
            ->orderByDesc('fim')
            ->get()
            ->keyBy('user_id');

        $resumo = [
            'total' => FrotaMotorista::count(),
            'online' => FrotaMotorista::where('status', FrotaMotorista::STATUS_ONLINE)->count(),
            'offline' => FrotaMotorista::where('status', FrotaMotorista::STATUS_OFFLINE)->count(),
            'sem_vinculo' => FrotaMotorista::whereNull('user_id')->count(),
            'liberados_manual' => FrotaLiberacao::vigente()->count(),
        ];

        $config = FrotaConfiguracao::atual();
        $configurada = $config->estaConfigurada();
        $gateEnabled = (bool) config('autotrac.gate_enabled', false);
        $ultimoSync = Cache::get($this->chaveUltimoSync());

        return view('frota::admin.index', compact(
            'motoristas',
            'liberacoes',
            'resumo',
            'config',
            'configurada',
            'gateEnabled',
            'ultimoSync'
        ));
    }

    public function veiculos(Request $request)
    {
        $query = FrotaVeiculo::query();

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));

            $query->where(function ($q) use ($busca) {
                $q->where('name', 'like', '%'.$busca.'%')
                    ->orWhere('license_plate', 'like', '%'.$busca.'%')
                    ->orWhere('code', $busca);
            });
        }

        $veiculos = $query->orderBy('name')->paginate(30)->withQueryString();

        return view('frota::admin.veiculos', compact('veiculos'));
    }

    public function historico(Request $request)
    {
        $query = FrotaJornada::query()->with(['veiculo', 'motorista']);

        if ($request->filled('de') && $request->date('de')) {
            $query->where('login_time', '>=', $request->date('de')->startOfDay());
        }

        if ($request->filled('ate') && $request->date('ate')) {
            $query->where('login_time', '<=', $request->date('ate')->endOfDay());
        }

        if ($request->filled('motorista_id')) {
            $query->where('frota_motorista_id', $request->integer('motorista_id'));
        }

        if ($request->filled('veiculo_id')) {
            $query->where('frota_veiculo_id', $request->integer('veiculo_id'));
        }

        if ($request->filled('situacao')) {
            $query->where('aberta', $request->input('situacao') === 'aberta');
        }

        $jornadas = $query->orderByDesc('login_time')->paginate(30)->withQueryString();

        $motoristas = FrotaMotorista::orderBy('name')->get(['id', 'name']);
        $veiculos = FrotaVeiculo::orderBy('name')->get(['id', 'name', 'license_plate']);

        return view('frota::admin.historico', compact('jornadas', 'motoristas', 'veiculos'));
    }

    public function configuracao()
    {
        $config = FrotaConfiguracao::atual();
        $gateEnabled = (bool) config('autotrac.gate_enabled', false);
        $tipos = [
            'motorista' => 'Motorista',
            'motorista_monitor' => 'Motorista Monitor',
        ];

        return view('frota::admin.configuracao', compact('config', 'tipos', 'gateEnabled'));
    }

    public function salvarConfiguracao(Request $request)
    {
        $data = $request->validate([
            'ativo' => ['nullable', 'boolean'],
            'base_url' => ['required', 'url', 'max:255'],
            'account_code' => ['nullable', 'string', 'max:50'],
            'subscription_key' => ['nullable', 'string', 'max:500'],
            'usuario' => ['nullable', 'string', 'max:255'],
            'senha' => ['nullable', 'string', 'max:500'],
            'ttl_segundos' => ['required', 'integer', 'min:10', 'max:3600'],
            'grace_minutos' => ['required', 'integer', 'min:0', 'max:1440'],
            'jornada_maxima_horas' => ['required', 'integer', 'min:1', 'max:24'],
            'fail_mode' => ['required', 'in:grace,closed,open'],
            'tipos_motorista' => ['nullable', 'array'],
            'tipos_motorista.*' => ['in:motorista,motorista_monitor'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        if (blank($data['subscription_key'] ?? null)) {
            unset($data['subscription_key']);
        }

        if (blank($data['senha'] ?? null)) {
            unset($data['senha']);
        }

        if (empty($data['tipos_motorista'])) {
            $data['tipos_motorista'] = ['motorista', 'motorista_monitor'];
        }

        $config = FrotaConfiguracao::query()->orderBy('id')->first() ?? new FrotaConfiguracao;
        $config->fill($data);
        $config->save();

        return redirect()
            ->route('admin.frota.configuracao')
            ->with('success', 'Configuração salva.');
    }

    public function testar(AutotracClient $client)
    {
        $resposta = $client->contas();

        if (! $resposta['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $resposta['error'] ?? 'Falha ao consultar a Autotrac.',
            ], 422);
        }

        $contas = $resposta['body']['Data'] ?? [];

        return response()->json([
            'ok' => true,
            'message' => 'Conexão realizada com sucesso.',
            'contas' => is_array($contas) ? array_map(static function ($conta) {
                return [
                    'code' => $conta['Code'] ?? null,
                    'name' => $conta['Name'] ?? null,
                ];
            }, $contas) : [],
        ]);
    }

    public function sincronizar(JornadaSyncService $sync)
    {
        $resultado = $sync->sincronizarTudo();

        Cache::put($this->chaveUltimoSync(), [
            'em' => now()->toIso8601String(),
            'resultado' => $resultado,
        ], now()->addDay());

        $mensagem = sprintf(
            'Sincronização concluída: %d motorista(s), %d veículo(s), %d status.',
            $resultado['motoristas'],
            $resultado['veiculos'],
            $resultado['status']
        );

        if ($resultado['erros'] !== []) {
            return back()->with('error', $mensagem.' Falhas: '.implode(' | ', $resultado['erros']));
        }

        return back()->with('success', $mensagem);
    }

    private function chaveUltimoSync(): string
    {
        return 'frota:ultimo_sync:'.(app(TenantManager::class)->id() ?? 'single');
    }
}
