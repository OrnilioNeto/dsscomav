<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RankingRepository;
use Illuminate\Http\Request;
use App\Services\RankingRuleResolverService;
use App\Models\Certificate;
use Illuminate\Support\Collection;

class RankingController extends Controller
{
    protected $repo;

    public function __construct(RankingRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Retorna a decomposição das pontuações para um usuário no período.
     * Resposta JSON: { trainings: [ { training_id, title, criteria: [{slug,label,points,value}], raw_score, max_possible, normalized } ] }
     */
    public function breakdown(Request $request, $userId, RankingRuleResolverService $resolver)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $certificates = Certificate::with(['training'])
            ->where('user_id', $userId)
            ->whereYear('data_emissao', $year)
            ->whereMonth('data_emissao', $month)
            ->get();

        $result = $certificates->map(function ($certificate) use ($resolver) {
            $training = $certificate->training;

            // calcular mesmos valores que o recalculo usa
            $startHours = null;
            if ($training->data_liberacao && $certificate->data_inicio_assistencia) {
                try { $startHours = $training->data_liberacao->diffInHours($certificate->data_inicio_assistencia); } catch (\Throwable $e) { $startHours = null; }
            }

            $completionDays = null;
            if ($certificate->data_inicio_assistencia && $certificate->data_finalizacao_assistencia) {
                try { $completionDays = $certificate->data_inicio_assistencia->diffInDays($certificate->data_finalizacao_assistencia); } catch (\Throwable $e) { $completionDays = null; }
            }

            $attempts = 1;
            try {
                $progress = $certificate->user?->progress()->where('training_id', $training->id)->orderByDesc('updated_at')->first();
                if ($progress && isset($progress->avaliacao_tentativas)) {
                    $t = (int) $progress->avaliacao_tentativas;
                    $attempts = $t > 0 ? ($t + 1) : 1;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            $criteria = [
                'start_time' => $startHours ?? 9999,
                'completion_time' => $completionDays ?? 9999,
                'quiz_result' => $attempts,
            ];

            $perCriterion = [];
            $rawTotal = 0;
            $maxTotal = 0;

            foreach ($criteria as $slug => $value) {
                $rule = $resolver->resolveRule($slug, $value);
                $points = $rule ? (int) $rule->points : 0;
                $label = $rule ? $rule->label : '—';

                $maxForCriterion = $rule ? (int) \App\Models\RankingRule::where('criterion_id', $rule->criterion_id)->max('points') : 0;

                $perCriterion[] = [
                    'slug' => $slug,
                    'label' => $label,
                    'points' => $points,
                    'value' => $value,
                    'min' => $rule?->min_value,
                    'max' => $rule?->max_value,
                ];

                $rawTotal += $points;
                $maxTotal += $maxForCriterion;
            }

            $normalized = $maxTotal > 0 ? round(($rawTotal / $maxTotal) * 100, 2) : 0;

            return [
                'training_id' => $training->id ?? null,
                'training_title' => $training->titulo ?? null,
                'criteria' => $perCriterion,
                'raw_score' => $rawTotal,
                'max_possible' => $maxTotal,
                'normalized' => $normalized,
            ];
        })->values();

        return response()->json(['trainings' => $result]);
    }

    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $top = (int) $request->input('top', 20);
        $type = $request->input('type', 'all');

        $rows = $this->repo->getTopMonthly($month, $year, $top, $type);
        $hasRealRanking = $rows->first() ? !isset($rows->first()->fallback_source) : false;

        return view('admin.ranking.index', compact('rows', 'month', 'year', 'top', 'type', 'hasRealRanking'));
    }

    public function history(Request $request)
    {
        // Placeholder: implementar pesquisa por usuário/histórico
        return view('admin.ranking.history');
    }
}
