<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    /**
     * Exibe o ranking de engajamento de elite (Apenas Super Admin)
     */
    public function index()
    {
        // 1. Ranking de Pioneiros: Quem concluiu os treinamentos mais rápido após a publicação
        // Considera data_liberacao se disponível, senão created_at
        $pioneiros = UserProgress::where('concluido', true)
            ->join('trainings', 'user_progress.training_id', '=', 'trainings.id')
            ->select(
                'user_id',
                DB::raw('AVG(strftime("%s", user_progress.data_conclusao) - strftime("%s", COALESCE(trainings.data_liberacao, trainings.created_at))) as tempo_reacao')
            )
            ->groupBy('user_id')
            ->with('user:id,nome,cargo')
            ->orderBy('tempo_reacao', 'asc')
            ->take(10)
            ->get();

        // 2. Ranking de Foco: Quem assiste o conteúdo de uma vez (menor tempo entre início e fim)
        // Calcula a fluidez como a diferença entre conclusão e início, normalizada pela carga horária
        $focados = UserProgress::where('concluido', true)
            ->join('trainings', 'user_progress.training_id', '=', 'trainings.id')
            ->select(
                'user_id',
                DB::raw('AVG((strftime("%s", user_progress.data_conclusao) - strftime("%s", user_progress.data_inicio)) / (trainings.carga_horaria * 60.0)) as fluidez_ratio')
            )
            ->groupBy('user_id')
            ->with('user:id,nome,empresa')
            ->orderBy('fluidez_ratio', 'asc')
            ->take(10)
            ->get();

        // 3. Score Geral para a Diretoria (Métrica de Elite)
        $topGeral = User::kpiEligible()
            ->withCount(['progress as concluidos' => function($q) { $q->where('concluido', true); }])
            ->withSum('progress as tempo_total', 'tempo_assistido')
            ->orderByDesc('concluidos')
            ->orderByDesc('tempo_total')
            ->take(5)
            ->get();

        return view('admin.ranking.index', compact('pioneiros', 'focados', 'topGeral'));
    }
}