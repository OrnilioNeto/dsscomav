<?php

namespace App\Services;

use App\Models\RankingMonthlyScore;
use App\Models\RankingScore;
use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;

class MonthlyRankingService
{
    /**
     * Consolida scores do mês atual e calcula posições respeitando o desempate.
     * O desempate segue a mesma regra da página /admin/ranking:
     *   1º maior average_score, 2º menor avg_start_time (iniciou mais cedo), 3º ordem alfabética.
     */
    public function consolidateMonth(int $month = null, int $year = null)
    {
        $month = $month ?: now()->month;
        $year = $year ?: now()->year;

        // Calcular média simples por usuário a partir dos ranking_scores
        $averages = RankingScore::select('user_id', DB::raw('AVG(normalized_score) as average_score'))
            ->where('month_reference', $month)
            ->where('year_reference', $year)
            ->groupBy('user_id')
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

        // Carregar o tiebreaker (média de timestamp de data_inicio) para todos os usuários do período
        $driver = DB::getDriverName();
        $isSqlite = $driver === 'sqlite';
        $avgStartRaw = $isSqlite
            ? 'AVG(strftime("%s", data_inicio))'
            : 'AVG(UNIX_TIMESTAMP(data_inicio))';

        $tiebreakers = UserProgress::select('user_id', DB::raw("$avgStartRaw as avg_start_time"))
            ->whereIn('user_id', $userIds)
            ->whereYear('data_inicio', $year)
            ->whereMonth('data_inicio', $month)
            ->groupBy('user_id')
            ->pluck('avg_start_time', 'user_id');

        // Carregar nomes para o terceiro critério de desempate (ordem alfabética)
        $names = \App\Models\User::whereIn('id', $userIds)->pluck('nome', 'id');

        // Montar array e ordenar com a mesma lógica da página admin
        $rows = $averages->map(function ($row) use ($tiebreakers, $names) {
            $row->tiebreaker = isset($tiebreakers[$row->user_id])
                ? (float) $tiebreakers[$row->user_id]
                : null;
            $row->nome = $names[$row->user_id] ?? '';
            return $row;
        })->sort(function ($a, $b) {
            // 1º: maior score
            if ($b->average_score != $a->average_score) {
                return $b->average_score <=> $a->average_score;
            }
            // 2º: menor tiebreaker (iniciou mais cedo)
            $ta = $a->tiebreaker ?? PHP_INT_MAX;
            $tb = $b->tiebreaker ?? PHP_INT_MAX;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            // 3º: ordem alfabética
            return strcmp($a->nome, $b->nome);
        })->values();

        // Atribuir posições respeitando empates
        $position = 0;
        $rankPos = 0;
        $lastScore = null;
        $lastTiebreaker = null;

        foreach ($rows as $row) {
            $rankPos++;
            if (
                $lastScore === null
                || $row->average_score != $lastScore
                || $row->tiebreaker !== $lastTiebreaker
            ) {
                $position = $rankPos;
            }

            RankingMonthlyScore::updateOrCreate([
                'user_id'         => $row->user_id,
                'month_reference' => $month,
                'year_reference'  => $year,
            ], [
                'average_score'    => round($row->average_score, 2),
                'tiebreaker_value' => $row->tiebreaker,
                'position'         => $position,
            ]);

            $lastScore = $row->average_score;
            $lastTiebreaker = $row->tiebreaker;
        }
    }
}
