<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\TrainingRewatchRequest;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrainingRewatchController extends Controller
{
    public function index(Request $request, User $user)
    {
        $treinamentos = Training::where('status', 'ativo')->orderBy('titulo')->get();

        $progressMap = UserProgress::where('user_id', $user->id)
            ->with('training:id,titulo,tipo')
            ->get()
            ->keyBy('training_id');

        $certificateMap = Certificate::where('user_id', $user->id)
            ->where('valido', true)
            ->get()
            ->keyBy('training_id');

        $rewatchMap = TrainingRewatchRequest::where('user_id', $user->id)
            ->with('certificateAnterior', 'certificateNovo')
            ->get()
            ->keyBy('training_id');

        $lista = $treinamentos->map(function ($training) use ($user, $progressMap, $certificateMap, $rewatchMap) {
            $podeAcessar = $user->canAccessTraining($training);
            $progress = $progressMap->get($training->id);
            $certificate = $certificateMap->get($training->id);
            $rewatch = $rewatchMap->get($training->id);

            return (object) [
                'training' => $training,
                'pode_acessar' => $podeAcessar,
                'tem_progresso' => $progress !== null,
                'concluido' => $progress ? $progress->concluido : false,
                'porcentagem' => $progress ? $progress->porcentagem_assistida : 0,
                'status' => $progress ? ($progress->concluido ? 'concluido' : 'pendente') : 'nao_iniciado',
                'tem_certificado' => $certificate !== null,
                'certificate' => $certificate,
                'tem_rewatch' => $rewatch !== null,
                'rewatch' => $rewatch,
            ];
        })->filter(function ($item) {
            return $item->pode_acessar;
        })->values();

        return view('usuarios.treinamentos_reassistir', [
            'usuario' => $user,
            'lista' => $lista,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'justificativa' => 'required|string|max:1000',
        ]);

        $trainingId = $request->input('training_id');
        $justificativa = $request->input('justificativa');
        $adminUser = $request->user();

        DB::beginTransaction();

        try {
            $certificate = Certificate::where('user_id', $user->id)
                ->where('training_id', $trainingId)
                ->where('valido', true)
                ->first();

            if ($certificate) {
                $certificate->update(['valido' => false]);
            }

            $progress = UserProgress::where('user_id', $user->id)
                ->where('training_id', $trainingId)
                ->first();

            if ($progress) {
                $updateData = [
                    'concluido' => false,
                    'avaliacao_aprovada' => false,
                    'avaliacao_tentativas' => 0,
                    'avaliacao_nota' => null,
                    'avaliacao_respostas_json' => null,
                    'avaliacao_resposta_usuario' => null,
                    'porcentagem_assistida' => 0,
                    'tempo_assistido' => 0,
                    'data_conclusao' => null,
                    'data_inicio' => now(config('app.timezone')),
                ];

                $progress->update($updateData);
            }

            $rewatchRequest = TrainingRewatchRequest::create([
                'user_id' => $user->id,
                'training_id' => $trainingId,
                'justificativa' => $justificativa,
                'authorized_by' => $adminUser->id,
                'certificate_anterior_id' => $certificate?->id,
                'status' => 'pendente',
            ]);

            DB::commit();

            return back()->with('success', 'Conteúdo liberado para reassistir com sucesso! O usuário deverá assistir novamente e passar na avaliação.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao processar solicitação: ' . $e->getMessage());
        }
    }

    public function destroy(User $user, TrainingRewatchRequest $rewatch)
    {
        if ($rewatch->user_id !== $user->id) {
            abort(404);
        }

        $rewatch->delete();

        return back()->with('success', 'Solicitação de reassistir removida com sucesso!');
    }
}
