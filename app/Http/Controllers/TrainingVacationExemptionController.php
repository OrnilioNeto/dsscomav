<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingVacationExemption;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class TrainingVacationExemptionController extends Controller
{
    public function index(Request $request, User $user)
    {
        $treinamentos = Training::where('status', 'ativo')->orderBy('titulo')->get();

        $progressMap = UserProgress::where('user_id', $user->id)
            ->with('training:id,titulo,tipo')
            ->get()
            ->keyBy('training_id');

        $exemptionMap = TrainingVacationExemption::where('user_id', $user->id)
            ->get()
            ->keyBy('training_id');

        $lista = $treinamentos->map(function ($training) use ($user, $progressMap, $exemptionMap) {
            $podeAcessar = $user->canAccessTraining($training);
            $progress = $progressMap->get($training->id);
            $exemption = $exemptionMap->get($training->id);

            return (object) [
                'training' => $training,
                'pode_acessar' => $podeAcessar,
                'tem_progresso' => $progress !== null,
                'concluido' => $progress ? $progress->concluido : false,
                'porcentagem' => $progress ? $progress->porcentagem_assistida : 0,
                'status' => $progress ? ($progress->concluido ? 'concluido' : 'pendente') : 'nao_iniciado',
                'tem_isencao' => $exemption !== null,
                'isencao' => $exemption,
            ];
        })->filter(function ($item) {
            return $item->pode_acessar;
        })->values();

        return view('usuarios.treinamentos_ferias', [
            'usuario' => $user,
            'lista' => $lista,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'motivo' => 'nullable|string|max:500',
        ]);

        $exists = TrainingVacationExemption::where('user_id', $user->id)
            ->where('training_id', $request->input('training_id'))
            ->where('data_inicio', $request->input('data_inicio'))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Já existe uma isenção para este treinamento nesse período.');
        }

        TrainingVacationExemption::create([
            'user_id' => $user->id,
            'training_id' => $request->input('training_id'),
            'data_inicio' => $request->input('data_inicio'),
            'data_fim' => $request->input('data_fim'),
            'motivo' => $request->input('motivo'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Isenção registrada com sucesso!');
    }

    public function destroy(User $user, TrainingVacationExemption $exemption)
    {
        if ($exemption->user_id !== $user->id) {
            abort(404);
        }

        $exemption->delete();

        return back()->with('success', 'Isenção removida com sucesso!');
    }
}
