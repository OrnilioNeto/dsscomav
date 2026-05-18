<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TrainingPlayerController extends Controller
{
    public function show($id)
    {
        $training = Training::with('materials')->findOrFail($id);
        $user = auth()->user();

        // Verificar acesso
        if (!$user->canAccessTraining($training)) {
            abort(403, 'Acesso negado a este treinamento.');
        }

        // Verificar se o treinamento já está liberado pela data/hora configurada
        if (!$training->isReleased()) {
            abort(403, 'Treinamento ainda não liberado.');
        }

        // Obter ou criar progresso
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

    public function submitAssessment(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();
        $isTestUser = $user->isTestUser();

        if (!$user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        if (!$training->hasAssessment()) {
            return response()->json(['error' => 'Treinamento sem avaliação cadastrada'], 422);
        }

        $validator = validator($request->all(), [
            'answer' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Resposta inválida'], 422);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();


        $isCorrect = (int) $request->answer === (int) $training->avaliacao_resposta_correta;

        if (!$isCorrect) {
            $tentativas = (int) ($progress->avaliacao_tentativas ?? 0) + 1;

            if (!$isTestUser && $tentativas >= 2) {
                $progress->update($this->filterUserProgressColumns([
                    'avaliacao_tentativas' => 0,
                    'avaliacao_aprovada' => false,
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
