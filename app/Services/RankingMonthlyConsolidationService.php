<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\RankingMonthlyScore;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consolida o ranking mensal (ranking_monthly_scores) usando a mesma lógica
 * do botão "Recalcular" do admin. Pode ser chamado automaticamente após a
 * criação de um certificado para manter o ranking sempre atualizado.
 */
class RankingMonthlyConsolidationService
{
    protected RankingRuleResolverService $resolver;

    public function __construct(RankingRuleResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Reconsolida o ranking_monthly_scores para o mês/ano informado.
     * Replica exatamente a lógica do RankingController@recalculate.
     *
     * @param  int  $month  Mês (1-12)
     * @param  int  $year   Ano (ex: 2026)
     * @return int  Número de usuários processados
     */
    public function consolidate(int $month, int $year): int
    {
        $driver = DB::getDriverName();
        $isSqlite = $driver === 'sqlite';

        // 1. Todos os usuários elegíveis para KPI
        $users = User::kpiEligible()->get();

        // 2. Tiebreakers: média do timestamp de data_inicio no período
        $avgStartRaw = $isSqlite
            ? 'AVG(strftime("%s", data_inicio))'
            : 'AVG(UNIX_TIMESTAMP(data_inicio))';

        $tiebreakers = UserProgress::select('user_id', DB::raw("$avgStartRaw as avg_start_time"))
            ->whereYear('data_inicio', $year)
            ->whereMonth('data_inicio', $month)
            ->groupBy('user_id')
            ->pluck('avg_start_time', 'user_id');

        $scores = [];

        foreach ($users as $user) {
            // 3. Certificados válidos do usuário no período (sem duplicatas por treinamento)
            $certificates = Certificate::with('training')
                ->where('user_id', $user->id)
                ->where('valido', true)
                ->whereMonth('data_emissao', $month)
                ->whereYear('data_emissao', $year)
                ->get()
                ->unique('training_id');

            $totalScore = 0;

            // 4. Pontuação por certificado
            foreach ($certificates as $cert) {
                $training = $cert->training;
                if (! $training) {
                    continue;
                }

                $startHours = null;
                $releaseDate = $training->data_liberacao ?? $training->created_at;
                if ($releaseDate && $cert->data_inicio_assistencia) {
                    $diffInMinutes = $releaseDate->diffInMinutes($cert->data_inicio_assistencia, false);
                    $startHours = round($diffInMinutes / 60, 1);
                }

                $completionDays = null;
                if ($cert->data_inicio_assistencia && $cert->data_finalizacao_assistencia) {
                    $completionDays = $cert->data_inicio_assistencia->diffInDays($cert->data_finalizacao_assistencia);
                }

                $attempts = 1;
                $prog = UserProgress::where('user_id', $user->id)
                    ->where('training_id', $training->id)
                    ->orderByDesc('updated_at')
                    ->first();
                if ($prog && isset($prog->avaliacao_tentativas)) {
                    $attempts = ($prog->avaliacao_tentativas > 0) ? ($prog->avaliacao_tentativas + 1) : 1;
                }

                $totalScore += $this->resolver->resolvePoints('start_time', $startHours ?? 9999) ?? 0;
                $totalScore += $this->resolver->resolvePoints('completion_time', $completionDays ?? 9999) ?? 0;
                $totalScore += $this->resolver->resolvePoints('quiz_result', $attempts) ?? 0;
            }

            $scores[$user->id] = [
                'score'      => $totalScore,
                'tiebreaker' => isset($tiebreakers[$user->id]) ? (float) $tiebreakers[$user->id] : null,
                'nome'       => $user->nome,
            ];
        }

        // 5. Ordenar: 1º maior score, 2º menor tiebreaker, 3º alfabético
        uasort($scores, function ($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            $ta = $a['tiebreaker'] ?? PHP_INT_MAX;
            $tb = $b['tiebreaker'] ?? PHP_INT_MAX;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return strcmp($a['nome'], $b['nome']);
        });

        // 6. Atribuir posições respeitando empates
        $position     = 0;
        $rankPos      = 0;
        $lastScore    = null;
        $lastTiebr    = null;
        $processedCount = 0;

        foreach ($scores as $userId => $data) {
            $rankPos++;
            if (
                $lastScore === null
                || $data['score'] !== $lastScore
                || $data['tiebreaker'] !== $lastTiebr
            ) {
                $position = $rankPos;
            }

            Log::info("RankingMonthlyConsolidationService: user {$userId}, score {$data['score']}, tiebreaker {$data['tiebreaker']}, position {$position}");

            RankingMonthlyScore::updateOrCreate(
                ['user_id' => $userId, 'month_reference' => $month, 'year_reference' => $year],
                [
                    'average_score'    => $data['score'],
                    'tiebreaker_value' => $data['tiebreaker'],
                    'position'         => $position,
                ]
            );

            $lastScore = $data['score'];
            $lastTiebr = $data['tiebreaker'];
            $processedCount++;
        }

        return $processedCount;
    }
}
