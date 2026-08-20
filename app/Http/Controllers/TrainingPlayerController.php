<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingQuestion;
use App\Models\TrainingLog;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
        if (!$user->canAccessTraining($training)) {
            abort(403, 'Acesso negado a este treinamento.');
        }

        // Verificar se o treinamento já está liberado pela data/hora configurada
        if (!$training->isReleased()) {
            abort(403, 'Treinamento ainda não liberado.');
        }

        // Obter ou criar progresso.
        $progress = UserProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
            ],
            [
                'data_inicio' => now(config('app.timezone')),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        if (!$progress->data_inicio) {
            $progress->update(['data_inicio' => now(config('app.timezone'))]);
        }

        TrainingLog::registrar($training->id, $user->id, 'curso_iniciado', 'Acesso ao player do treinamento.');

        return view('treinamentos.player', compact('training', 'progress'));
    }

    public function updateProgress(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();
        $isTestUser = $user->isTestUser();

        if (!$user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
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
        $duracao = (int) $training->carga_horaria * 60;

        // Capear tempo à duração máxima
        $tempoCliente = min($tempoCliente, $duracao);

        // Obter progresso anterior
        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        $tempoAnterior = (int) $progress->tempo_assistido;

        // REGRA DE BLOQUEIO: máximo avanço de 10 segundos por requisição
        $avancoPermitido = 10;
        if ($tempoCliente > $tempoAnterior && ($tempoCliente - $tempoAnterior) > $avancoPermitido) {
            $tempoCliente = $tempoAnterior + $avancoPermitido;
        }

        $tempoAssistido = max($tempoAnterior, $tempoCliente);
        $porcentagemAssistida = max((int) $porcentagem, (int) $progress->porcentagem_assistida);

        $updateData = [
            'tempo_assistido' => $tempoAssistido,
            'porcentagem_assistida' => $porcentagemAssistida,
        ];

        if (!$progress->data_inicio) {
            $updateData['data_inicio'] = now(config('app.timezone'));
        }

        $progress->update($updateData);

        $fresh = $progress->fresh();
        $showAssessment = $training->hasAssessment() && ($isTestUser || $fresh->porcentagem_assistida >= 99) && !$fresh->avaliacao_aprovada;

        if (($isTestUser || $fresh->porcentagem_assistida >= 99) && $fresh->avaliacao_aprovada && !$fresh->concluido) {
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
     * Etapa 1 da avaliação: re-identificação do trabalhador por senha individual
     * (NR-01 Anexo II 4.6.1/4.6.2) e liberação das questões embaralhadas.
     */
    public function iniciarAvaliacao(Request $request, $id)
    {
        $training = Training::with('questions')->findOrFail($id);
        $user = auth()->user();

        if (!$user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        if (!$training->hasAssessment()) {
            return response()->json(['error' => 'Treinamento sem avaliação cadastrada'], 422);
        }

        $validator = validator($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Informe sua senha para iniciar a avaliação.'], 422);
        }

        // Re-identificação individual: confere a senha do usuário autenticado (4.6.1)
        if (!Hash::check($request->input('password'), $user->password)) {
            TrainingLog::registrar($training->id, $user->id, 'avaliacao_senha_invalida', 'Tentativa de iniciar avaliação com senha incorreta.');
            return response()->json(['error' => 'Senha incorreta. Verifique e tente novamente.'], 422);
        }

        // Registra o início da prova (log de rastreabilidade)
        TrainingLog::registrar($training->id, $user->id, 'avaliacao_iniciada', 'Avaliação iniciada com re-identificação por senha.');

        // Caso 1: banco de questões — sorteia questões e embaralha opções
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

            return response()->json([
                'success' => true,
                'modo' => 'banco',
                'quantidade' => $selecionadas->count(),
                'questoes' => $payload->map(fn ($q) => [
                    'id' => $q['id'],
                    'pergunta' => $q['pergunta'],
                    'opcoes' => $q['opcoes'],
                ])->values(),
            ]);
        }

        // Caso 2: pergunta única legada (DSS / treinamento sem banco)
        return response()->json([
            'success' => true,
            'modo' => 'legado',
            'pergunta' => $training->avaliacao_pergunta,
            'opcoes' => array_values(array_filter($training->avaliacao_opcoes ?? [])),
        ]);
    }

    public function submitAssessment(Request $request, $id)
    {
        $training = Training::with('questions')->findOrFail($id);
        $user = auth()->user();
        $isTestUser = $user->isTestUser();

        if (!$user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        if (!$training->hasAssessment()) {
            return response()->json(['error' => 'Treinamento sem avaliação cadastrada'], 422);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        // ===== Banco de questões =====
        if ($training->hasQuestionBank()) {
            $mapa = session()->get("avaliacao_{$training->id}_map", []);

            if (empty($mapa)) {
                return response()->json(['error' => 'Sessão da prova expirada ou não iniciada. Clique em "Realizar avaliação" e confirme sua senha novamente.'], 422);
            }

            $respostas = $request->input('respostas', []);
            if (!is_array($respostas)) {
                return response()->json(['error' => 'Respostas inválidas'], 422);
            }

            $corretas = 0;
            $total = count($mapa);
            $respostasSalvas = [];

            foreach ($mapa as $questaoId => $indicesOriginais) {
                $questao = $training->questions->firstWhere('id', $questaoId);
                if (!$questao) {
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
            $aprovado = $nota >= $notaMinima;

            TrainingLog::registrar($training->id, $user->id, 'avaliacao_submetida', "Nota {$nota}% (mínimo {$notaMinima}%) em {$total} questão(ões).");

            if (!$aprovado) {
                $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

                if (!$isTestUser && $tentativas >= 2) {
                    $progress->update($this->filterUserProgressColumns([
                        'avaliacao_tentativas' => 0,
                        'avaliacao_aprovada' => false,
                        'avaliacao_nota' => null,
                        'avaliacao_respostas_json' => null,
                        'concluido' => false,
                        'porcentagem_assistida' => 0,
                        'tempo_assistido' => 0,
                        'data_inicio' => now(config('app.timezone')),
                        'data_conclusao' => null,
                        'avaliacao_resposta_usuario' => null,
                    ]));
                    session()->forget("avaliacao_{$training->id}_map");

                    return response()->json([
                        'success' => false,
                        'reset_required' => true,
                        'message' => "Nota insatisfatória ({$nota}%) nas duas tentativas. Assista o vídeo novamente para liberar uma nova avaliação.",
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
                        ? "Nota insatisfatória ({$nota}%). Modo teste: pode tentar novamente sem reassistir o vídeo."
                        : "Nota insatisfatória ({$nota}%). Você ainda tem mais uma chance. Nota mínima para aprovação: {$notaMinima}%.",
                ], 422);
            }

            $progress->update($this->filterUserProgressColumns([
                'avaliacao_aprovada' => true,
                'avaliacao_tentativas' => 0,
                'avaliacao_nota' => $nota,
                'avaliacao_respostas_json' => $respostasSalvas,
            ]));
            session()->forget("avaliacao_{$training->id}_map");

            if (($isTestUser || $progress->porcentagem_assistida >= 99) && !$progress->concluido) {
                $progress->update([
                    'concluido' => true,
                    'data_conclusao' => now(config('app.timezone')),
                ]);

                $this->issueCertificateIfReady($training, $progress->fresh());
            }

            return response()->json([
                'success' => true,
                'message' => "Avaliação aprovada com sucesso! Nota: {$nota}% (satisfatório).",
                'progress' => $progress->fresh(),
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

        if (!$isCorrect) {
            $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

            if (!$isTestUser && $tentativas >= 2) {
                $progress->update($this->filterUserProgressColumns([
                    'avaliacao_tentativas' => 0,
                    'avaliacao_aprovada' => false,
                    'avaliacao_nota' => null,
                    'concluido' => false,
                    'porcentagem_assistida' => 0,
                    'tempo_assistido' => 0,
                    'data_inicio' => now(config('app.timezone')),
                    'data_conclusao' => null,
                    'avaliacao_resposta_usuario' => null,
                ]));

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

        if (($isTestUser || $progress->porcentagem_assistida >= 99) && !$progress->concluido) {
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

        if (!$progress->concluido) {
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
        if (!$progress->concluido || !$progress->avaliacao_aprovada) {
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
