<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrainingPlayerController extends Controller
{
    public function show($id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();

        // Verificar acesso
        if (!$user->canAccessTraining($training)) {
            abort(403, 'Acesso negado a este treinamento.');
        }

        // Obter ou criar progresso
        $progress = UserProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
            ],
            [
                'data_inicio' => now(),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        if (!$progress->data_inicio) {
            $progress->update(['data_inicio' => now()]);
        }

        return view('treinamentos.player', compact('training', 'progress'));
    }

    public function updateProgress(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();

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

        $porcentagemAssistida = max((int) $porcentagem, (int) $progress->porcentagem_assistida);

        $updateData = [
            'tempo_assistido' => $tempoCliente,
            'porcentagem_assistida' => $porcentagemAssistida,
        ];

        // aceitar timestamps locais do cliente (ISO8601)
        if ($request->filled('data_inicio_assistencia')) {
            try {
                $updateData['data_inicio'] = Carbon::parse($request->data_inicio_assistencia);
            } catch (\Exception $e) {
                // ignore parse errors, não sobrescrever
            }
        }

        if ($request->filled('data_finalizacao_assistencia')) {
            try {
                $updateData['data_conclusao'] = Carbon::parse($request->data_finalizacao_assistencia);
            } catch (\Exception $e) {
                // ignore parse errors
            }
        }

        $progress->update($updateData);

        $fresh = $progress->fresh();
        $showAssessment = $training->hasAssessment() && $fresh->porcentagem_assistida >= 90 && !$fresh->avaliacao_aprovada;

        if ($fresh->porcentagem_assistida >= 90 && $fresh->avaliacao_aprovada && !$fresh->concluido) {
            $conclusionData = ['concluido' => true];
            if ($request->filled('data_finalizacao_assistencia')) {
                try {
                    $conclusionData['data_conclusao'] = Carbon::parse($request->data_finalizacao_assistencia);
                } catch (\Exception $e) {
                    $conclusionData['data_conclusao'] = now();
                }
            } else {
                $conclusionData['data_conclusao'] = now();
            }

            $progress->update($conclusionData);

            $this->issueCertificateIfReady($training, $progress->fresh());
        }

        return response()->json([
            'progress' => $progress->fresh(),
            'show_assessment' => $showAssessment,
            'status' => 'sucesso',
            'tempo' => $tempoCliente,
            'duracao' => $duracao,
        ]);
    }

    public function submitAssessment(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();

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

            if ($tentativas >= 2) {
                $progress->update([
                    'avaliacao_tentativas' => 0,
                    'avaliacao_aprovada' => false,
                    'concluido' => false,
                    'porcentagem_assistida' => 0,
                    'tempo_assistido' => 0,
                    'data_inicio' => now(),
                    'data_conclusao' => null,
                ]);

                return response()->json([
                    'success' => false,
                    'reset_required' => true,
                    'message' => 'Resposta incorreta nas duas tentativas. Assista o vídeo novamente para liberar uma nova avaliação.',
                ], 422);
            }

            $progress->update([
                'avaliacao_tentativas' => $tentativas,
            ]);

            return response()->json([
                'success' => false,
                'reset_required' => false,
                'message' => 'Resposta incorreta. Você ainda tem mais uma chance.',
            ], 422);
        }

        $progress->update([
            'avaliacao_aprovada' => true,
            'avaliacao_tentativas' => 0,
        ]);

        if ($progress->porcentagem_assistida >= 90 && !$progress->concluido) {
            $progress->update([
                'concluido' => true,
                'data_conclusao' => now(),
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
                'data_conclusao' => now(),
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

        app(CertificateController::class)->generateCertificate($training, $progress);
    }
}
