<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\UserProgress;
use App\Services\YoutubeStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class TrainingController extends Controller
{
    private function serializeTraining(Training $training, ?UserProgress $progress = null): array
    {
        $progressData = null;
        if ($progress) {
            $progressData = [
                'tempo_assistido' => (int) $progress->tempo_assistido,
                'porcentagem_assistida' => (int) $progress->porcentagem_assistida,
                'concluido' => (bool) $progress->concluido,
                'avaliacao_aprovada' => (bool) $progress->avaliacao_aprovada,
                'avaliacao_tentativas' => (int) ($progress->avaliacao_tentativas ?? 0),
                'data_inicio' => $progress->data_inicio?->toISOString(),
                'data_conclusao' => $progress->data_conclusao?->toISOString(),
            ];
        }

        $assessment = null;
        if ($training->hasAssessment() && $progress && $progress->porcentagem_assistida >= 99 && ! $progress->avaliacao_aprovada
            && ! $training->prazoExpirado($progress)) {
            $assessment = [
                'pergunta' => $training->avaliacao_pergunta,
                'opcoes' => array_values(array_filter($training->avaliacao_opcoes ?? [])),
            ];
        }

        return [
            'id' => $training->id,
            'titulo' => $training->titulo,
            'descricao' => $training->descricao,
            'tipo' => $training->tipo,
            'tipo_usuario_permitido' => $training->tipo_usuario_permitido,
            'url_video' => $training->url_video,
            'video_embed' => $training->getVideoEmbed(),
            'tipo_video' => $training->tipo_video,
            'carga_horaria' => (int) $training->carga_horaria,
            'carga_horaria_segundos' => $training->carga_horaria_segundos !== null ? (int) $training->carga_horaria_segundos : null,
            'duracao_segundos' => max(1, $training->duracaoSegundos()),
            'thumbnail' => $training->thumbnail,
            'data_publicacao' => $training->data_publicacao?->toISOString(),
            'data_liberacao' => $training->data_liberacao?->toISOString(),
            'prazo_conclusao' => $training->prazoConclusao($progress)?->toISOString(),
            'prazo_estendido' => $progress?->prazo_estendido_ate?->toISOString(),
            'prazo_expirado' => $training->prazoExpirado($progress),
            'status' => $training->status,
            'obrigatorio' => (bool) $training->obrigatorio,
            'liberado' => $training->isReleased(),
            'tem_avaliacao' => $training->hasAssessment(),
            'avaliacao' => $assessment,
            'materiais' => $training->materials->map(fn ($m) => [
                'id' => $m->id,
                'nome' => $m->nome ?: $m->arquivo,
                'descricao' => $m->descricao,
                'arquivo' => $m->arquivo,
                'tipo_arquivo' => $m->tipo_arquivo,
                'tamanho' => (int) ($m->tamanho ?? 0),
                'ordem' => (int) ($m->ordem ?? 0),
                'url_download' => url("/api/v1/materials/{$m->id}/download"),
            ])->values(),
            'progress' => $progressData,
            'certificado_emitido' => Certificate::where('user_id', auth()->id())
                ->where('training_id', $training->id)
                ->where('valido', true)
                ->exists(),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $trainings = Training::with('materials')
            ->where('status', 'ativo')
            ->orderByReleaseDate('desc')
            ->get()
            ->filter(fn ($t) => $user->canAccessTraining($t));

        $progressList = UserProgress::where('user_id', $user->id)->get()->keyBy('training_id');

        $disponiveis = [];
        $bloqueados = [];
        $direcionados = [];
        $concluidos = [];

        foreach ($trainings as $training) {
            $progress = $progressList->get($training->id);
            $item = $this->serializeTraining($training, $progress);

            if ($progress && $progress->concluido) {
                $concluidos[] = $item;
            } elseif ($training->tipo === 'dss' && ! $training->isReleased()) {
                $item['progress'] = null;
                $bloqueados[] = $item;
            } elseif ($training->tipo === 'treinamento' && ! $training->isReleased()) {
                // Mesma regra do web: direcionados não liberados ficam ocultos
                continue;
            } elseif (! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
                // Prazo expirado: continua visível, porém bloqueado
                $bloqueados[] = $item;
            } elseif ($training->tipo === 'treinamento') {
                $direcionados[] = $item;
            } else {
                $disponiveis[] = $item;
            }
        }

        // Bloqueados: próxima liberação (segunda-feira) primeiro
        usort($bloqueados, function (array $a, array $b) {
            return strcmp(
                (string) ($a['data_liberacao'] ?? $a['data_publicacao'] ?? ''),
                (string) ($b['data_liberacao'] ?? $b['data_publicacao'] ?? '')
            );
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'disponiveis' => $disponiveis,
                'bloqueados' => $bloqueados,
                'direcionados' => $direcionados,
                'concluidos' => $concluidos,
            ],
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        $training = Training::with('materials')->findOrFail($id);

        if (! $user->canAccessTraining($training)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Acesso negado a este treinamento.',
            ], 403);
        }

        if (! $training->isReleased()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Treinamento ainda não liberado.',
            ], 403);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        $progress = $progress ?: UserProgress::create(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'data_inicio' => now(config('app.timezone')),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        if (! $progress->data_inicio) {
            $progress->update(['data_inicio' => now(config('app.timezone'))]);
        }

        $data = $this->serializeTraining($training, $progress->fresh());
        $data['stream_url'] = $this->resolveStreamUrl($training);

        // Streaming online via proxy (o servidor não armazena os vídeos)
        $data['stream_proxy_url'] = $training->tipo_video === 'youtube' && $data['stream_url']
            ? url('/api/v1/trainings/'.$training->id.'/stream-proxy')
            : null;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Resolve a URL de stream direto (MP4/HLS) para vídeos do YouTube.
     * Usado pelo app como alternativa ao embed (WebView pode ser bloqueado).
     */
    public function stream($id)
    {
        $user = request()->user();
        $training = Training::findOrFail($id);

        if (! $user->canAccessTraining($training)) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado'], 403);
        }

        return response()->json([
            'status' => 'success',
            'stream_url' => $this->resolveStreamUrl($training),
        ]);
    }

    /**
     * Proxy de vídeo: transmite o stream do YouTube através do servidor.
     * Necessário porque as URLs do googlevideo são vinculadas ao IP do
     * servidor (param `ip`/`ipbits`) — o celular não conseguiria baixar direto.
     * Suporta Range (necessário para seek/streaming no ExoPlayer).
     * Autenticação: header Authorization OU query `?token=` (alguns players
     * nativos não enviam headers personalizados).
     */
    public function streamProxy($id)
    {
        $user = request()->user();

        if (! $user) {
            $token = request()->query('token') ?: request()->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Não autenticado.'], 401);
        }

        $training = Training::findOrFail($id);

        if (! $user->canAccessTraining($training)) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado'], 403);
        }

        $streamUrl = $this->resolveStreamUrl($training);
        if (! $streamUrl) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stream indisponível no momento.',
            ], 422);
        }

        $range = request()->header('Range');
        $origin = $this->preflightOrigin($streamUrl, $range);

        $status = $origin['status'] ?? 200;
        $responseHeaders = [
            'Content-Type' => $origin['content-type'] ?? 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'X-Accel-Buffering' => 'no',
        ];
        if ($range && ($status === 206 || $status === 200)) {
            $responseHeaders['Content-Range'] = $origin['content-range'] ?? null;
        }

        return response()->stream(function () use ($streamUrl, $range) {
            $ch = curl_init($streamUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => false,
                CURLOPT_BUFFERSIZE => 65536,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_HTTPHEADER => $range ? ['Range: '.$range] : [],
                CURLOPT_WRITEFUNCTION => function ($curl, $data) {
                    if (connection_aborted()) {
                        return 0;
                    }
                    echo $data;
                    flush();

                    return strlen($data);
                },
            ]);
            curl_exec($ch);
            curl_close($ch);
        }, $status, array_filter($responseHeaders));
    }

    /**
     * Consulta o origin para descobrir status/headers reais (o download real
     * vem depois). Usa o MESMO range do cliente para o Content-Range bater.
     */
    private function preflightOrigin(string $url, ?string $range): array
    {
        $result = [
            'status' => 200,
            'content-type' => 'video/mp4',
            'content-range' => null,
        ];

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Range: '.($range ?? 'bytes=0-0')],
            ]);

            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($status === 200 || $status === 206) {
                $headerText = substr($response, 0, strpos($response, "\r\n\r\n") ?: 0);
                foreach (explode("\r\n", $headerText) as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $key = strtolower(trim($parts[0]));
                        if ($key === 'content-type') {
                            $result['content-type'] = trim($parts[1]);
                        } elseif ($key === 'content-range') {
                            $result['content-range'] = trim($parts[1]);
                        }
                    }
                }
                $result['status'] = $status;
            }
        } catch (\Throwable $e) {
            Log::warning('Preflight do stream falhou: '.$e->getMessage());
        }

        return $result;
    }

    private function resolveStreamUrl(Training $training): ?string
    {
        if ($training->tipo_video !== 'youtube') {
            return null;
        }

        try {
            $resolver = app(YoutubeStreamResolver::class);
            $videoId = $resolver->extractVideoId($training->url_video);

            return $videoId ? $resolver->resolve($videoId) : null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao resolver stream: '.$e->getMessage());

            return null;
        }
    }

    public function updateProgress(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = $request->user();
        $isTestUser = $user->isTestUser();

        if (! $user->canAccessTraining($training)) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado'], 403);
        }

        $tempoCliente = $request->input('tempo_assistido');
        $porcentagem = $request->input('porcentagem_assistida');

        if ($tempoCliente === null || $porcentagem === null) {
            return response()->json(['status' => 'error', 'message' => 'Campos obrigatórios faltando'], 400);
        }

        $tempoCliente = (int) $tempoCliente;
        if ($tempoCliente < 0) {
            return response()->json(['status' => 'error', 'message' => 'Tempo não pode ser negativo'], 400);
        }

        $duracao = max(1, $training->duracaoSegundos());
        $tempoCliente = min($tempoCliente, $duracao);

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $isTestUser && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        $agora = now(config('app.timezone'));

        $progress = $progress ?: UserProgress::create(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'data_inicio' => $agora,
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        if (! $progress->data_inicio) {
            $progress->update(['data_inicio' => $agora]);
            $progress->refresh();
        }

        $tempoAnterior = (int) $progress->tempo_assistido;
        $deltaDesejado = $tempoCliente - $tempoAnterior;

        if ($deltaDesejado > 0) {
            if ($isTestUser) {
                $tempoAssistido = $tempoCliente;
            } else {
                $cacheKey = "api_last_beat:{$user->id}:{$training->id}";
                $lastBeat = Cache::get($cacheKey);

                if ($lastBeat) {
                    $segundosDecorrido = max(0, $agora->timestamp - (int) $lastBeat);
                } else {
                    $segundosDecorrido = $progress->updated_at ? max(0, $agora->diffInSeconds($progress->updated_at)) : 10;
                }

                $segundosDesdeInicio = max(0, $agora->diffInSeconds($progress->data_inicio));

                if ($segundosDecorrido > 600) {
                    $maxAvancoPermitido = 15;
                } else {
                    $maxAvancoPermitido = max(10, $segundosDecorrido + 15);
                }

                $avancoEfetivo = min($deltaDesejado, $maxAvancoPermitido);
                $tempoCandidato = $tempoAnterior + $avancoEfetivo;
                $tetoAbsoluto = $segundosDesdeInicio + 30;
                $tempoAssistido = min($duracao, $tempoCandidato, $tetoAbsoluto);
            }
        } else {
            $tempoAssistido = $tempoAnterior;
        }

        Cache::put("api_last_beat:{$user->id}:{$training->id}", $agora->timestamp, now()->addHours(6));

        $porcentagemAssistida = min(100, (int) round(($tempoAssistido / $duracao) * 100));

        $updateData = [
            'tempo_assistido' => $tempoAssistido,
            'porcentagem_assistida' => $porcentagemAssistida,
        ];

        $progress->update($updateData);

        $fresh = $progress->fresh();
        $minimoTempoExigido = (int) round($duracao * 0.95);
        $videoConcluido = ($fresh->porcentagem_assistida >= 99 && $fresh->tempo_assistido >= $minimoTempoExigido);

        $showAssessment = $training->hasAssessment() && ($isTestUser || $videoConcluido) && ! $fresh->avaliacao_aprovada;

        if (($isTestUser || $videoConcluido) && $fresh->avaliacao_aprovada && ! $fresh->concluido) {
            $fresh->update([
                'concluido' => true,
                'data_conclusao' => now(config('app.timezone')),
            ]);

            $this->issueCertificateIfReady($training, $fresh->fresh());
        }

        $final = $fresh->fresh();

        $response = [
            'status' => 'success',
            'progress' => [
                'tempo_assistido' => (int) $final->tempo_assistido,
                'porcentagem_assistida' => (int) $final->porcentagem_assistida,
                'concluido' => (bool) $final->concluido,
                'avaliacao_aprovada' => (bool) $final->avaliacao_aprovada,
                'avaliacao_tentativas' => (int) ($final->avaliacao_tentativas ?? 0),
                'data_inicio' => $final->data_inicio?->toISOString(),
                'data_conclusao' => $final->data_conclusao?->toISOString(),
            ],
            'show_assessment' => $showAssessment,
            'tempo' => $tempoAssistido,
            'duracao' => $duracao,
        ];

        // Envia o conteúdo da avaliação junto quando ela é liberada
        if ($showAssessment && $training->hasAssessment()) {
            $response['assessment'] = [
                'pergunta' => $training->avaliacao_pergunta,
                'opcoes' => array_values(array_filter($training->avaliacao_opcoes ?? [])),
            ];
        }

        return response()->json($response);
    }

    public function submitAssessment(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = $request->user();
        $isTestUser = $user->isTestUser();

        if (! $user->canAccessTraining($training)) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado'], 403);
        }

        if (! $training->hasAssessment()) {
            return response()->json(['status' => 'error', 'message' => 'Treinamento sem avaliação cadastrada'], 422);
        }

        $validator = validator($request->all(), [
            'answer' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Resposta inválida'], 422);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $isTestUser && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        $progress = $progress ?: UserProgress::create(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'data_inicio' => now(config('app.timezone')),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        if (! $isTestUser && (int) $progress->porcentagem_assistida < 99) {
            return response()->json([
                'status' => 'error',
                'message' => 'A avaliação será liberada após você concluir 100% do vídeo.',
            ], 422);
        }

        $isCorrect = (int) $request->answer === (int) $training->avaliacao_resposta_correta;

        if (! $isCorrect) {
            $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

            if (! $isTestUser && $tentativas >= 2) {
                $resetPayload = [
                    'avaliacao_tentativas' => 0,
                    'avaliacao_aprovada' => false,
                    'concluido' => false,
                    'porcentagem_assistida' => 0,
                    'tempo_assistido' => 0,
                    'data_conclusao' => null,
                    'avaliacao_resposta_usuario' => null,
                ];
                if (! $progress->data_inicio) {
                    $resetPayload['data_inicio'] = now(config('app.timezone'));
                }
                $progress->update($this->filterUserProgressColumns($resetPayload));

                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'reset_required' => true,
                    'message' => 'Resposta incorreta nas duas tentativas. Assista o vídeo novamente para liberar uma nova avaliação.',
                ], 422);
            }

            $progress->update($this->filterUserProgressColumns([
                'avaliacao_tentativas' => $tentativas,
            ]));

            return response()->json([
                'status' => 'error',
                'success' => false,
                'reset_required' => false,
                'message' => $isTestUser
                    ? 'Resposta incorreta (modo teste). Pode tentar novamente sem reassistir o vídeo.'
                    : 'Resposta incorreta. Você ainda tem mais uma chance.',
            ], 422);
        }

        $progress->update($this->filterUserProgressColumns([
            'avaliacao_aprovada' => true,
            'avaliacao_tentativas' => 0,
            'avaliacao_resposta_usuario' => (int) $request->answer,
        ]));

        if (($isTestUser || $progress->porcentagem_assistida >= 99) && ! $progress->concluido) {
            $progress->update([
                'concluido' => true,
                'data_conclusao' => now(config('app.timezone')),
            ]);

            $this->issueCertificateIfReady($training, $progress->fresh());
        }

        $final = $progress->fresh();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Avaliação aprovada com sucesso!',
            'progress' => [
                'tempo_assistido' => (int) $final->tempo_assistido,
                'porcentagem_assistida' => (int) $final->porcentagem_assistida,
                'concluido' => (bool) $final->concluido,
                'avaliacao_aprovada' => (bool) $final->avaliacao_aprovada,
                'data_inicio' => $final->data_inicio?->toISOString(),
                'data_conclusao' => $final->data_conclusao?->toISOString(),
            ],
        ]);
    }

    public function complete($id)
    {
        $training = Training::findOrFail($id);
        $user = request()->user();

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $progress?->concluido && ! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        $progress = $progress ?: UserProgress::create(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
                'data_inicio' => now(config('app.timezone')),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        $duracao = max(1, $training->duracaoSegundos());
        $minimoTempoExigido = (int) round($duracao * 0.95);

        if (! $user->isTestUser()) {
            if ((int) $progress->tempo_assistido < $minimoTempoExigido || (int) $progress->porcentagem_assistida < 99) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você precisa assistir a pelo menos 95% do treinamento antes de concluir.',
                ], 422);
            }

            if ($training->hasAssessment() && ! $progress->avaliacao_aprovada) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'É necessário ser aprovado na avaliação antes de concluir o treinamento.',
                ], 422);
            }
        }

        if (! $progress->concluido) {
            $progress->update([
                'concluido' => true,
                'porcentagem_assistida' => 100,
                'data_conclusao' => now(config('app.timezone')),
            ]);
        }

        $this->issueCertificateIfReady($training, $progress->fresh());

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento concluído!',
        ]);
    }

    private function issueCertificateIfReady(Training $training, UserProgress $progress): void
    {
        if (! $progress->concluido || ! $progress->avaliacao_aprovada) {
            return;
        }

        try {
            app(CertificateController::class)->generateCertificate($training, $progress);
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar certificado via API', [
                'training_id' => $training->id,
                'user_id' => $progress->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function filterUserProgressColumns(array $payload): array
    {
        $filtered = [];

        foreach ($payload as $column => $value) {
            if (Schema::hasColumn('user_progress', $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}
