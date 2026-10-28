<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingLog;
use App\Models\TrainingQuestion;
use App\Models\TrainingVacationExemption;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TrainingPlayerController extends Controller
{
    public function show($id)
    {
        $training = Training::with('materials', 'questions')->findOrFail($id);
        $user = auth()->user();

        // Verificar acesso
        if (! $user->canAccessTraining($training)) {
            abort(403, 'Acesso negado a este treinamento.');
        }

        // Verificar se o treinamento está isento por férias
        $isExempt = TrainingVacationExemption::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->exists();
        if ($isExempt) {
            abort(403, 'Este treinamento está isento no período de férias.');
        }

        // Verificar se o treinamento já está liberado pela data/hora configurada
        if (! $training->isReleased()) {
            abort(403, 'Treinamento ainda não liberado.');
        }

        // Prazo de conclusão: 23:00 do domingo subsequente à liberação,
        // prorrogável pelo gestor (prazo_estendido_ate).
        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            abort(403, 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.');
        }

        // Obter ou criar progresso.
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

        session()->put("player_session_{$training->id}", now()->timestamp);
        session()->put("player_last_beat_{$training->id}", now()->timestamp);

        TrainingLog::registrar($training->id, $user->id, 'curso_iniciado', 'Acesso ao player do treinamento.');

        // Questões exibidas abaixo do vídeo; liberadas apenas após a conclusão (100%).
        $assessment = null;
        $assessmentUnlocked = false;

        if ($training->hasAssessment() && ! $progress->avaliacao_aprovada) {
            $assessment = $this->buildAssessmentPayload($training);
            $assessmentUnlocked = $user->isTestUser() || (int) $progress->porcentagem_assistida >= 99;
        }

        return view('treinamentos.player', compact('training', 'progress', 'assessment', 'assessmentUnlocked'));
    }

    public function updateProgress(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();
        $isTestUser = $user->isTestUser();

        if (! $user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        // Obter progresso anterior
        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        if (! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'error' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        if (! $progress) {
            return response()->json(['error' => 'Progresso não encontrado'], 404);
        }

        // Validar payload
        $tempoCliente = $request->input('tempo_assistido');
        $porcentagem = $request->input('porcentagem_assistida');

        if ($tempoCliente === null || $porcentagem === null) {
            return response()->json(['error' => 'Campos obrigatórios faltando'], 400);
        }

        $tempoCliente = (int) $tempoCliente;
        if ($tempoCliente < 0) {
            return response()->json(['error' => 'Tempo não pode ser negativo'], 400);
        }

        // Obter duração do treinamento (em segundos)
        $duracao = max(1, $training->duracaoSegundos());

        // Capear tempo à duração máxima
        $tempoCliente = min($tempoCliente, $duracao);

        $agora = now(config('app.timezone'));

        // Garantir que data_inicio existe
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
                // Rastreamento de tempo físico decorrido (wall-clock):
                // 1. Tempo decorrido desde o último heartbeat registrado nesta sessão web
                $lastBeat = session()->get("player_last_beat_{$training->id}");

                // Fallback: se a sessão não tiver o timestamp, utiliza updated_at do banco
                if ($lastBeat) {
                    $segundosDecorrido = max(0, $agora->timestamp - (int) $lastBeat);
                } else {
                    $segundosDecorrido = $progress->updated_at ? max(0, $agora->diffInSeconds($progress->updated_at)) : 10;
                }

                // 2. Teto acumulado desde o início do treinamento
                $segundosDesdeInicio = max(0, $agora->diffInSeconds($progress->data_inicio));

                if ($segundosDecorrido > 600) {
                    // Retomada após longa ausência/inatividade: avanço máximo inicial de 15s
                    $maxAvancoPermitido = 15;
                } else {
                    // Reprodução contínua ou com throttling em mobile/background:
                    // Tolera o tempo real decorrido no relógio + 15s de tolerância de rede
                    $maxAvancoPermitido = max(10, $segundosDecorrido + 15);
                }

                $avancoEfetivo = min($deltaDesejado, $maxAvancoPermitido);
                $tempoCandidato = $tempoAnterior + $avancoEfetivo;

                // Teto absoluto: tempo acumulado não pode superar o tempo decorrido desde o início + 30s
                $tetoAbsoluto = $segundosDesdeInicio + 30;
                $tempoAssistido = min($duracao, $tempoCandidato, $tetoAbsoluto);
            }
        } else {
            // Tempo reportado é menor ou igual ao já salvo (ex.: seek para trás ou pausa)
            $tempoAssistido = $tempoAnterior;
        }

        // Atualiza a marcação do último heartbeat na sessão
        session()->put("player_last_beat_{$training->id}", $agora->timestamp);

        // O percentual assistido é SEMPRE recalculado no servidor a partir do tempo real e da duração
        $porcentagemAssistida = min(100, (int) round(($tempoAssistido / $duracao) * 100));

        $updateData = [
            'tempo_assistido' => $tempoAssistido,
            'porcentagem_assistida' => $porcentagemAssistida,
        ];

        $porcentagemAnterior = (int) $progress->porcentagem_assistida;

        $progress->update($updateData);

        $fresh = $progress->fresh();

        $minimoTempoExigido = (int) round($duracao * 0.95);
        $videoConcluido = ($fresh->porcentagem_assistida >= 99 && $fresh->tempo_assistido >= $minimoTempoExigido);

        // Registra a liberação da avaliação quando o vídeo atinge a conclusão real.
        if ($training->hasAssessment() && ! $fresh->avaliacao_aprovada
            && $porcentagemAnterior < 99 && ($isTestUser || $videoConcluido)) {
            TrainingLog::registrar($training->id, $user->id, 'avaliacao_iniciada', 'Avaliação liberada após conclusão do vídeo.');
        }

        $showAssessment = $training->hasAssessment() && ($isTestUser || $videoConcluido) && ! $fresh->avaliacao_aprovada;

        if (($isTestUser || $videoConcluido) && $fresh->avaliacao_aprovada && ! $fresh->concluido) {
            $conclusionData = ['concluido' => true];
            $conclusionData['data_conclusao'] = now(config('app.timezone'));

            $progress->update($conclusionData);

            $this->issueCertificateIfReady($training, $progress->fresh());
        }

        return response()->json([
            'progress' => $progress->fresh(),
            'show_assessment' => $showAssessment,
            'status' => 'sucesso',
            'tempo' => $tempoAssistido,
            'duracao' => $duracao,
        ]);
    }

    /**
     * Monta as questões exibidas no player abaixo do vídeo. Quando há banco de
     * questões, sorteia e embaralha as opções, guardando o mapa de validação na
     * sessão (NR-01 Anexo II 4.6.2).
     */
    private function buildAssessmentPayload(Training $training): array
    {
        if ($training->hasQuestionBank()) {
            $questoes = $training->questions;
            $quantidade = (int) ($training->quantidade_questoes_prova ?: $questoes->count());
            $quantidade = max(1, min($quantidade, $questoes->count()));

            $selecionadas = $questoes->shuffle()->take($quantidade)->values();

            $payload = $selecionadas->map(function (TrainingQuestion $q) {
                $opcoes = array_values($q->opcoes ?? []);
                $indices = array_keys($opcoes);
                shuffle($indices);

                return [
                    'id' => $q->id,
                    'pergunta' => $q->pergunta,
                    'opcoes' => array_map(fn ($i) => $opcoes[$i], $indices),
                    'mapa' => $indices,
                ];
            });

            // Guarda o mapeamento da ordem embaralhada para validar no servidor (4.6.2)
            session()->put("avaliacao_{$training->id}_map", $payload->mapWithKeys(fn ($q) => [$q['id'] => $q['mapa']])->toArray());

            $totalQuestoes = $payload->count();
            $notaMinima = (int) ($training->nota_minima_aprovacao ?? 70);
            $minimoAcertos = $training->minimoAcertosAprovacao($totalQuestoes);

            return [
                'modo' => 'banco',
                'questoes' => $payload->map(fn ($q) => [
                    'id' => $q['id'],
                    'pergunta' => $q['pergunta'],
                    'opcoes' => $q['opcoes'],
                ])->values()->all(),
                'total_questoes' => $totalQuestoes,
                'nota_minima' => $notaMinima,
                'minimo_acertos' => $minimoAcertos,
            ];
        }

        // Pergunta única legada (DSS / treinamento sem banco)
        $notaMinima = (int) ($training->nota_minima_aprovacao ?? 70);

        return [
            'modo' => 'legado',
            'pergunta' => $training->avaliacao_pergunta,
            'opcoes' => array_values(array_filter($training->avaliacao_opcoes ?? [])),
            'total_questoes' => 1,
            'nota_minima' => $notaMinima,
            'minimo_acertos' => 1,
        ];
    }

    public function submitAssessment(Request $request, $id)
    {
        $training = Training::with('questions')->findOrFail($id);
        $user = auth()->user();
        $isTestUser = $user->isTestUser();

        if (! $user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        if (! $training->hasAssessment()) {
            return response()->json(['error' => 'Treinamento sem avaliação cadastrada'], 422);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        // Prazo de conclusão: avaliação não pode ser enviada após o vencimento.
        if (! $isTestUser && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'error' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        if (! $progress) {
            return response()->json(['error' => 'Progresso não encontrado'], 404);
        }

        // Re-identificação por senha (NR-01 Anexo II 4.6.1/4.6.2): exigida no
        // envio para o tipo "Treinamento" (DSS dispensa a confirmação).
        if ($training->tipo === 'treinamento') {
            $senha = (string) $request->input('password', '');

            if ($senha === '' || ! Hash::check($senha, $user->password)) {
                TrainingLog::registrar($training->id, $user->id, 'avaliacao_senha_invalida', 'Tentativa de responder avaliação com senha incorreta.');

                return response()->json(['error' => 'Senha incorreta. Confirme sua senha de acesso e tente novamente.'], 422);
            }
        }

        // Bloqueio no servidor: a avaliação só é corrigida após a conclusão do
        // vídeo (>= 99%), mesmo que o usuário reabilite o painel pelo navegador.
        if (! $isTestUser && (int) $progress->porcentagem_assistida < 99) {
            return response()->json(['error' => 'A avaliação será liberada após você concluir 100% do vídeo.'], 422);
        }

        // ===== Banco de questões =====
        if ($training->hasQuestionBank()) {
            $mapa = session()->get("avaliacao_{$training->id}_map", []);

            if (empty($mapa)) {
                return response()->json(['error' => 'Sessão da avaliação expirada. Recarregue a página e responda novamente.'], 422);
            }

            $respostas = $request->input('respostas', []);
            if (! is_array($respostas)) {
                return response()->json(['error' => 'Respostas inválidas'], 422);
            }

            $corretas = 0;
            $total = count($mapa);
            $respostasSalvas = [];

            foreach ($mapa as $questaoId => $indicesOriginais) {
                $questao = $training->questions->firstWhere('id', $questaoId);
                if (! $questao) {
                    continue;
                }

                $resposta = isset($respostas[$questaoId]) ? (int) $respostas[$questaoId] : -1;
                $indiceOriginal = isset($indicesOriginais[$resposta]) ? (int) $indicesOriginais[$resposta] : -1;
                $respostasSalvas[$questaoId] = $indiceOriginal;

                if ($indiceOriginal === (int) $questao->resposta_correta) {
                    $corretas++;
                }
            }

            $nota = $total > 0 ? (int) round(($corretas / $total) * 100) : 0;
            $notaMinima = (int) ($training->nota_minima_aprovacao ?? 70);
            $minimoAcertos = $training->minimoAcertosAprovacao($total);
            $aprovado = $corretas >= $minimoAcertos;

            TrainingLog::registrar($training->id, $user->id, 'avaliacao_submetida', "Acertos: {$corretas}/{$total} ({$nota}%). Mínimo exigido: {$notaMinima}% ({$minimoAcertos} acertos).");

            if (! $aprovado) {
                $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

                if (! $isTestUser && $tentativas >= 2) {
                    $resetPayload = [
                        'avaliacao_tentativas' => 0,
                        'avaliacao_aprovada' => false,
                        'avaliacao_nota' => null,
                        'avaliacao_respostas_json' => null,
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
                    session()->forget("avaliacao_{$training->id}_map");

                    TrainingLog::registrar($training->id, $user->id, 'avaliacao_reset', 'Duas tentativas reprovadas; conteúdo reassistido para liberar nova avaliação.');

                    return response()->json([
                        'success' => false,
                        'reset_required' => true,
                        'message' => "Nota insatisfatória: você acertou {$corretas} de {$total} questão(ões) ({$nota}%). O critério mínimo é {$notaMinima}% (pelo menos {$minimoAcertos} acerto(s)). Limite de tentativas atingido; assista o vídeo novamente para liberar uma nova avaliação.",
                        'corretas' => $corretas,
                        'total' => $total,
                        'nota' => $nota,
                        'nota_minima' => $notaMinima,
                        'minimo_acertos' => $minimoAcertos,
                    ], 422);
                }

                $progress->update($this->filterUserProgressColumns([
                    'avaliacao_tentativas' => $tentativas,
                    'avaliacao_nota' => $nota,
                    'avaliacao_respostas_json' => $respostasSalvas,
                    'avaliacao_aprovada' => false,
                ]));

                return response()->json([
                    'success' => false,
                    'reset_required' => false,
                    'message' => $isTestUser
                        ? "Nota insatisfatória: você acertou {$corretas} de {$total} questão(ões) ({$nota}%). Mínimo exigido: {$notaMinima}% (pelo menos {$minimoAcertos} acerto(s)). Modo teste: pode tentar novamente sem reassistir o vídeo."
                        : "Nota insatisfatória: você acertou {$corretas} de {$total} questão(ões) ({$nota}%). Mínimo para aprovação: {$notaMinima}% (pelo menos {$minimoAcertos} acerto(s)). Você ainda tem mais uma chance.",
                    'corretas' => $corretas,
                    'total' => $total,
                    'nota' => $nota,
                    'nota_minima' => $notaMinima,
                    'minimo_acertos' => $minimoAcertos,
                ], 422);
            }

            $progress->update($this->filterUserProgressColumns([
                'avaliacao_aprovada' => true,
                'avaliacao_tentativas' => 0,
                'avaliacao_nota' => $nota,
                'avaliacao_respostas_json' => $respostasSalvas,
            ]));
            session()->forget("avaliacao_{$training->id}_map");

            if (($isTestUser || $progress->porcentagem_assistida >= 99) && ! $progress->concluido) {
                $progress->update([
                    'concluido' => true,
                    'data_conclusao' => now(config('app.timezone')),
                ]);

                $this->issueCertificateIfReady($training, $progress->fresh());
            }

            return response()->json([
                'success' => true,
                'message' => "Avaliação aprovada com sucesso! Você acertou {$corretas} de {$total} questão(ões) ({$nota}%). Critério atingido (mínimo exigido: {$notaMinima}% ou {$minimoAcertos} acerto(s)).",
                'progress' => $progress->fresh(),
                'corretas' => $corretas,
                'total' => $total,
                'nota' => $nota,
                'nota_minima' => $notaMinima,
                'minimo_acertos' => $minimoAcertos,
            ]);
        }

        // ===== Pergunta única legada =====
        $validator = validator($request->all(), [
            'answer' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Resposta inválida'], 422);
        }

        $isCorrect = (int) $request->answer === (int) $training->avaliacao_resposta_correta;
        $nota = $isCorrect ? 100 : 0;

        TrainingLog::registrar($training->id, $user->id, 'avaliacao_submetida', $isCorrect ? 'Resposta correta.' : 'Resposta incorreta.');

        if (! $isCorrect) {
            $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

            if (! $isTestUser && $tentativas >= 2) {
                $resetPayload = [
                    'avaliacao_tentativas' => 0,
                    'avaliacao_aprovada' => false,
                    'avaliacao_nota' => null,
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

                TrainingLog::registrar($training->id, $user->id, 'avaliacao_reset', 'Duas tentativas reprovadas; conteúdo reassistido para liberar nova avaliação.');

                return response()->json([
                    'success' => false,
                    'reset_required' => true,
                    'message' => 'Resposta incorreta nas duas tentativas. Assista o vídeo novamente para liberar uma nova avaliação.',
                ], 422);
            }

            $progress->update($this->filterUserProgressColumns([
                'avaliacao_tentativas' => $tentativas,
                'avaliacao_nota' => 0,
            ]));

            return response()->json([
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
            'avaliacao_nota' => 100,
            'avaliacao_resposta_usuario' => (int) $request->answer,
        ]));

        if (($isTestUser || $progress->porcentagem_assistida >= 99) && ! $progress->concluido) {
            $progress->update([
                'concluido' => true,
                'data_conclusao' => now(config('app.timezone')),
            ]);

            $this->issueCertificateIfReady($training, $progress->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Avaliação aprovada com sucesso!',
            'progress' => $progress->fresh(),
        ]);
    }

    public function complete($id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        if (! $progress->concluido && ! $user->isTestUser() && ! $user->isAdmin() && $training->prazoExpirado($progress)) {
            return response()->json([
                'error' => 'O prazo para assistir e concluir este treinamento expirou. Fale com seu gestor para liberar um novo prazo.',
                'prazo_expirado' => true,
            ], 403);
        }

        $duracao = max(1, $training->duracaoSegundos());
        $minimoTempoExigido = (int) round($duracao * 0.95);

        if (! $user->isTestUser()) {
            if ((int) $progress->tempo_assistido < $minimoTempoExigido || (int) $progress->porcentagem_assistida < 99) {
                return response()->json([
                    'error' => 'Você precisa assistir a pelo menos 95% do treinamento antes de concluir.',
                ], 422);
            }

            if ($training->hasAssessment() && ! $progress->avaliacao_aprovada) {
                return response()->json([
                    'error' => 'É necessário ser aprovado na avaliação antes de concluir o treinamento.',
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

        return response()->json(['success' => true, 'message' => 'Treinamento concluído!']);
    }

    private function issueCertificateIfReady(Training $training, UserProgress $progress): void
    {
        if (! $progress->concluido || ! $progress->avaliacao_aprovada) {
            return;
        }

        try {
            app(CertificateController::class)->generateCertificate($training, $progress);
        } catch (\Throwable $e) {
            Log::error('Falha ao gerar certificado apos conclusao de treinamento', [
                'training_id' => $training->id,
                'user_id' => $progress->user_id,
                'progress_id' => $progress->id,
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
