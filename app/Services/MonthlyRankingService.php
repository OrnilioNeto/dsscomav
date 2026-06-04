<?php

namespace App\Services;

use App\Models\RankingMonthlyScore;
use App\Models\RankingScore;
use Illuminate\Support\Facades\DB;

class MonthlyRankingService
{
    /**
     * Consolida scores do mês atual e calcula posições respeitando empates.
     */
    public function consolidateMonth(int $month = null, int $year = null)
    {
        $month = $month ?: now()->month;
        $year = $year ?: now()->year;

        // calcular média simples por usuário
        $averages = RankingScore::select('user_id', DB::raw('AVG(normalized_score) as average_score'))
            ->where('month_reference', $month)
            ->where('year_reference', $year)
            ->groupBy('user_id')
            ->orderByDesc('average_score')
            ->get();

        $userIds = $averages->pluck('user_id')->all();

        if (empty($userIds)) {
            RankingMonthlyScore::where('month_reference', $month)
                ->where('year_reference', $year)
                ->delete();

            return;
        }

        RankingMonthlyScore::where('month_reference', $month)
            ->where('year_reference', $year)
            ->whereNotIn('user_id', $userIds)
            ->delete();

        $position = 0;
        $lastScore = null;
        $rankPos = 0;

        foreach ($averages as $row) {
            $rankPos++;
            if ($lastScore === null || $row->average_score < $lastScore) {
                $position = $rankPos;
            }

            RankingMonthlyScore::updateOrCreate([
                'user_id' => $row->user_id,
                'month_reference' => $month,
                'year_reference' => $year,
            ], [
                'average_score' => round($row->average_score, 2),
                'position' => $position,
            ]);

            $lastScore = $row->average_score;
        }
    }
}
