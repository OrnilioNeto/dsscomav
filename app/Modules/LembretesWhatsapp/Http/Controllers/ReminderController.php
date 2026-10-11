<?php

namespace App\Modules\LembretesWhatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\UserProgress;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Modules\LembretesWhatsapp\Services\PendingTrainingService;
use App\Modules\LembretesWhatsapp\Services\ReminderDispatcher;
use App\Modules\LembretesWhatsapp\Services\WhatsappGatewayClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class ReminderController extends Controller
{
    public function __construct(
        private PendingTrainingService $pendentes,
        private ReminderDispatcher $dispatcher,
        private WhatsappGatewayClient $gateway
    ) {}

    /**
     * Histórico de lembretes enviados/enfileirados.
     */
    public function index(Request $request)
    {
        $query = TrainingReminder::query()->with(['user', 'training', 'creator']);

        if ($request->filled('status') && array_key_exists($request->input('status'), $this->statusValidos())) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', $request->integer('training_id'));
        }

        if ($request->filled('busca')) {
            $busca = $request->input('busca');
            $query->whereHas('user', function ($q) use ($busca) {
                $q->where('nome', 'like', '%'.$busca.'%')->orWhere('cpf', 'like', '%'.preg_replace('/\D/', '', $busca).'%');
            });
        }

        if ($request->filled('de')) {
            $query->whereDate('created_at', '>=', $request->input('de'));
        }

        if ($request->filled('ate')) {
            $query->whereDate('created_at', '<=', $request->input('ate'));
        }

        $reminders = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $hoje = Carbon::now(config('app.timezone'))->toDateString();
        $resumo = [
            'fila' => TrainingReminder::where('status', TrainingReminder::STATUS_FILA)->count(),
            'enviados_hoje' => TrainingReminder::where('status', TrainingReminder::STATUS_ENVIADO)->whereDate('enviado_em', $hoje)->count(),
            'falhas' => TrainingReminder::where('status', TrainingReminder::STATUS_FALHOU)->count(),
            'pulados' => TrainingReminder::where('status', TrainingReminder::STATUS_PULADO)->count(),
        ];

        $treinamentos = Training::orderBy('titulo')->get(['id', 'titulo']);

        return view('lembretes_whatsapp::index', [
            'reminders' => $reminders,
            'resumo' => $resumo,
            'treinamentos' => $treinamentos,
            'statusValidos' => $this->statusValidos(),
            'configuracao' => [
                'enabled' => (bool) config('whatsapp.enabled'),
                'gateway_url' => (string) config('whatsapp.gateway_url'),
                'session_id' => (string) config('whatsapp.session_id'),
                'daily_cap' => (int) config('whatsapp.daily_cap'),
                'batch_limit' => (int) config('whatsapp.batch_limit'),
                'delay_min' => (int) config('whatsapp.delay_min'),
                'delay_max' => (int) config('whatsapp.delay_max'),
                'window_start' => (string) config('whatsapp.window_start'),
                'window_end' => (string) config('whatsapp.window_end'),
                'allow_weekends' => (bool) config('whatsapp.allow_weekends'),
            ],
        ]);
    }

    /**
     * Tela de disparo: panorama de todos os treinamentos ou os pendentes
     * de um treinamento selecionado.
     */
    public function disparo(Request $request)
    {
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;
        $gestor = $request->user();

        $training = null;
        $panorama = null;
        $pendentes = collect();
        $contagens = null;
        $tiposUsuario = collect();

        $filtros = [
            'tipo_usuario' => (string) $request->input('tipo_usuario', ''),
            'busca' => trim((string) $request->input('busca', '')),
        ];

        if ($trainingId) {
            $training = Training::findOrFail($trainingId);

            $todosPendentes = $this->pendentes->pendentes($training, $gestor);
            $contagens = $this->pendentes->contagensDoTreinamento($training, $gestor);
            $contagens['sem_telefone'] = $todosPendentes
                ->filter(fn ($user) => phone_to_jid($user->telefone) === null)
                ->count();

            $tiposUsuario = $todosPendentes->pluck('tipo_usuario')->filter()->unique()->sort()->values();

            $filtrados = $todosPendentes
                ->when($filtros['tipo_usuario'] !== '', fn ($lista) => $lista->where('tipo_usuario', $filtros['tipo_usuario']))
                ->when($filtros['busca'] !== '', function ($lista) use ($filtros) {
                    $termo = mb_strtolower($filtros['busca']);
                    $digitos = preg_replace('/\D/', '', $filtros['busca']);

                    return $lista->filter(function ($user) use ($termo, $digitos) {
                        if (mb_stripos((string) $user->nome, $termo) !== false) {
                            return true;
                        }

                        return $digitos !== '' && str_contains((string) $user->cpf, $digitos);
                    });
                })
                ->values();

            $userIds = $filtrados->pluck('id');

            $enviosHoje = $this->pendentes->enviosDeHoje($training, $userIds);

            $ultimos = TrainingReminder::query()
                ->where('training_id', $training->id)
                ->whereIn('user_id', $userIds)
                ->orderByDesc('created_at')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');

            $comProgresso = UserProgress::query()
                ->where('training_id', $training->id)
                ->whereIn('user_id', $userIds)
                ->pluck('concluido', 'user_id');

            $pendentes = $filtrados->map(function ($user) use ($enviosHoje, $ultimos, $comProgresso) {
                $ultimo = $ultimos->get($user->id);
                $envioHoje = $enviosHoje->get($user->id);

                return (object) [
                    'user' => $user,
                    'telefone' => $this->formatarTelefone($user->telefone),
                    'telefone_valido' => phone_to_jid($user->telefone) !== null,
                    'situacao' => $comProgresso->has($user->id) ? 'pendente' : 'nao_iniciado',
                    'enviado_hoje' => $envioHoje !== null,
                    'enviado_hoje_hora' => $envioHoje?->enviado_em?->format('H:i'),
                    'ultimo_status' => $ultimo?->status,
                    'ultimo_em' => $ultimo?->created_at?->format('d/m/Y H:i'),
                ];
            });
        } else {
            $panorama = $this->pendentes->panorama($gestor, $request->boolean('refresh'));
        }

        return view('lembretes_whatsapp::disparo', [
            'training' => $training,
            'panorama' => $panorama,
            'pendentes' => $pendentes,
            'contagens' => $contagens,
            'tiposUsuario' => $tiposUsuario,
            'filtros' => $filtros,
            'mensagemPadrao' => (string) config('whatsapp.default_message'),
            'proximoEnvio' => ReminderDispatcher::proximoInicioJanela(Carbon::now())->format('d/m/Y H:i'),
            'batchLimit' => (int) config('whatsapp.batch_limit', 30),
            'gatewayEnabled' => (bool) config('whatsapp.enabled'),
        ]);
    }

    /**
     * Enfileira os lembretes selecionados pelo gestor.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'training_id' => ['required', 'integer'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'mensagem' => ['required', 'string', 'min:10', 'max:2000'],
            'enviar_agora' => ['sometimes', 'boolean'],
        ]);

        $training = Training::findOrFail($data['training_id']);

        if ($training->status !== 'ativo') {
            throw ValidationException::withMessages([
                'training_id' => 'Este treinamento está inativo e não pode receber lembretes.',
            ]);
        }

        if (! $training->isReleased()) {
            throw ValidationException::withMessages([
                'training_id' => 'Este treinamento ainda não foi liberado.',
            ]);
        }

        $imediato = $request->boolean('enviar_agora');

        if ($imediato) {
            if (! config('whatsapp.enabled')) {
                throw ValidationException::withMessages([
                    'enviar_agora' => 'Integração WhatsApp desativada (WA_REMINDERS_ENABLED=false): não é possível enviar agora.',
                ]);
            }

            if (! $this->gateway->configurado()) {
                throw ValidationException::withMessages([
                    'enviar_agora' => 'Gateway WhatsApp não configurado (WA_GATEWAY_URL/API_KEY/SESSION).',
                ]);
            }

            $sessao = $this->gateway->sessionStatus();

            if (! $sessao['connected']) {
                throw ValidationException::withMessages([
                    'enviar_agora' => "Sessão WhatsApp não conectada ({$sessao['status']}). ".($sessao['error'] ?? ''),
                ]);
            }
        }

        $userIds = array_values(array_unique(array_map('intval', $data['user_ids'])));

        $batchLimit = (int) config('whatsapp.batch_limit', 30);
        if (count($userIds) > $batchLimit) {
            throw ValidationException::withMessages([
                'user_ids' => "Selecione no máximo {$batchLimit} colaboradores por disparo.",
            ]);
        }

        $users = $this->pendentes->pendentes($training, $request->user(), $userIds);

        if ($users->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Nenhum colaborador pendente válido foi selecionado.',
            ]);
        }

        $resultado = $this->dispatcher->enfileirar($training, $users, $data['mensagem'], $request->user(), $imediato);

        $enviadosAgora = null;

        if ($imediato && $resultado['enfileirados'] > 0) {
            Artisan::call('lembretes:processar', [
                '--force' => true,
                '--limit' => $resultado['enfileirados'],
            ]);

            $statuses = TrainingReminder::query()
                ->whereIn('id', $resultado['reminder_ids'])
                ->pluck('status');

            $enviadosAgora = [
                'enviado' => $statuses->filter(fn ($status) => $status === TrainingReminder::STATUS_ENVIADO)->count(),
                'falhou' => $statuses->filter(fn ($status) => $status === TrainingReminder::STATUS_FALHOU)->count(),
                'fila' => $statuses->filter(fn ($status) => $status === TrainingReminder::STATUS_FILA)->count(),
            ];
        }

        if ($imediato) {
            $mensagem = "Envio imediato: {$enviadosAgora['enviado']} enviado(s), "
                ."{$enviadosAgora['falhou']} falha(s) e {$enviadosAgora['fila']} na fila"
                .($resultado['pulados'] > 0 ? " · {$resultado['pulados']} não enfileirado(s)" : '').'.';
        } else {
            $mensagem = $resultado['enfileirados'] > 0
                ? "{$resultado['enfileirados']} lembrete(s) na fila"
                    .($resultado['pulados'] > 0 ? " · {$resultado['pulados']} não enfileirado(s) (sem telefone/não encontrado no WhatsApp)" : '')
                    .($resultado['primeiro_envio'] ? '. Primeiro envio previsto: '.$resultado['primeiro_envio']->format('d/m/Y H:i').'.' : '.')
                : 'Nenhum lembrete pôde ser enfileirado.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $resultado['enfileirados'] > 0,
                'enfileirados' => $resultado['enfileirados'],
                'pulados' => $resultado['pulados'],
                'primeiro_envio' => $resultado['primeiro_envio']?->format('d/m/Y H:i'),
                'ultimo_envio' => $resultado['ultimo_envio']?->format('d/m/Y H:i'),
                'enviados_agora' => $enviadosAgora,
                'gateway_ativo' => (bool) config('whatsapp.enabled'),
                'mensagem' => $mensagem,
            ]);
        }

        $sucesso = $resultado['enfileirados'] > 0
            && (! $imediato || $enviadosAgora['enviado'] > 0);

        return redirect()
            ->route('admin.lembretes.disparo', ['training_id' => $training->id])
            ->with($sucesso ? 'success' : 'error', $mensagem);
    }

    /**
     * Cancela um lembrete que ainda está na fila.
     */
    public function cancelar(Request $request, TrainingReminder $reminder)
    {
        if ($reminder->status !== TrainingReminder::STATUS_FILA) {
            $erro = 'Somente lembretes na fila podem ser cancelados.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $erro], 422);
            }

            return back()->with('error', $erro);
        }

        $reminder->update([
            'status' => TrainingReminder::STATUS_CANCELADO,
            'erro' => 'Cancelado pelo gestor.',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Lembrete cancelado.']);
        }

        return back()->with('success', 'Lembrete cancelado.');
    }

    /**
     * Status da sessão no gateway (indicador da tela).
     */
    public function sessao()
    {
        if (! config('whatsapp.enabled')) {
            return response()->json([
                'enabled' => false,
                'connected' => false,
                'status' => 'DESATIVADO',
                'error' => 'Integração WhatsApp desativada (WA_REMINDERS_ENABLED=false).',
            ]);
        }

        return response()->json(array_merge(['enabled' => true], $this->gateway->sessionStatus()));
    }

    /**
     * @return array<string, string>
     */
    private function statusValidos(): array
    {
        return [
            TrainingReminder::STATUS_FILA => 'Na fila',
            TrainingReminder::STATUS_ENVIADO => 'Enviado',
            TrainingReminder::STATUS_FALHOU => 'Falhou',
            TrainingReminder::STATUS_PULADO => 'Pulado (sem telefone)',
            TrainingReminder::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    private function formatarTelefone(?string $telefone): string
    {
        $digits = preg_replace('/\D/', '', (string) $telefone) ?? '';

        if ($digits === '') {
            return 'Não informado';
        }

        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        }

        if (strlen($digits) === 10) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 4).'-'.substr($digits, 6);
        }

        return (string) $telefone;
    }
}
