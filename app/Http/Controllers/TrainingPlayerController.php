<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\UserProgress;
use Illuminate\Http\Request;

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
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
            ]
        );

        return view('treinamentos.player', compact('training', 'progress'));
    }

    public function updateProgress(Request $request, $id)
    {
        $training = Training::findOrFail($id);
        $user = auth()->user();

        if (!$user->canAccessTraining($training)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        $tempoAssistido = max((int) ($request->tempo_assistido ?? $progress->tempo_assistido), (int) $progress->tempo_assistido);
        $porcentagemAssistida = max((int) ($request->porcentagem_assistida ?? $progress->porcentagem_assistida), (int) $progress->porcentagem_assistida);

        $progress->update([
            'tempo_assistido' => $tempoAssistido,
            'porcentagem_assistida' => $porcentagemAssistida,
        ]);

        $showAssessment = $training->hasAssessment() && $progress->porcentagem_assistida >= 90 && !$progress->avaliacao_aprovada;

        if ($progress->porcentagem_assistida >= 90 && $progress->avaliacao_aprovada && !$progress->concluido) {
            $progress->update([
                'concluido' => true,
                'data_conclusao' => now(),
            ]);
        }

        return response()->json([
            'progress' => $progress->fresh(),
            'show_assessment' => $showAssessment,
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
            return response()->json([
                'success' => false,
                'message' => 'Resposta incorreta. Tente novamente.',
            ], 422);
        }

        $progress->update([
            'avaliacao_aprovada' => true,
        ]);

        if ($progress->porcentagem_assistida >= 90 && !$progress->concluido) {
            $progress->update([
                'concluido' => true,
                'data_conclusao' => now(),
            ]);
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

        return response()->json(['success' => true, 'message' => 'Treinamento concluído!']);
    }
}
