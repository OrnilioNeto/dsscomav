<?php

namespace App\Services;

use App\Models\RankingScore;
use App\Services\RankingRuleResolverService;
use Illuminate\Support\Facades\DB;
use App\Models\RankingCriterion;
use App\Models\RankingRule;

class RankingCalculatorService
{
    protected $resolver;

    public function __construct(RankingRuleResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Calcula score para um único conteúdo (usuário + training/content)
     * Recebe array de critérios e valores, aplica regras, soma pontos e normaliza.
     */
    public function calculateForContent(
        int $userId,
        ?int $trainingId,
        ?int $contentId,
        array $criteriaValues,
        ?int $month = null,
        ?int $year = null
    ): RankingScore {
        [$points, $normalized, $maxPossible] = $this->resolveScore($criteriaValues);

        $month = $month ?: now()->month;
        $year = $year ?: now()->year;

        return RankingScore::updateOrCreate([
            'user_id' => $userId,
            'training_id' => $trainingId,
            'content_id' => $contentId,
            'month_reference' => $month,
            'year_reference' => $year,
        ], [
            'raw_score' => $points,
            'max_possible_score' => $maxPossible,
            'normalized_score' => round($normalized, 2),
            'calculated_at' => now(),
        ]);
    }

    /**
     * Calcula a pontuação sem persistir em banco.
     */
    public function previewScore(array $criteriaValues): float
    {
        [, $normalized] = $this->resolveScore($criteriaValues);

        return round($normalized, 2);
    }

    /**
     * Resolve pontuação bruta e normalizada para o conjunto de critérios.
     *
     * @return array{0:int,1:float}
     */
    protected function resolveScore(array $criteriaValues): array
    {
        $points = 0;
        $maxPossible = 0;

        foreach ($criteriaValues as $criterionSlug => $value) {
            $rulePoints = $this->resolver->resolvePoints($criterionSlug, $value);
            $points += $rulePoints ?? 0;

            $criterion = RankingCriterion::where('slug', $criterionSlug)->first();
            if ($criterion) {
                $maxForCriterion = (int) RankingRule::where('criterion_id', $criterion->id)->max('points');
                $maxPossible += $maxForCriterion ?? 0;
            }
        }

        $normalized = $maxPossible > 0 ? ($points / $maxPossible) * 100 : 0;

        return [$points, $normalized, $maxPossible];
    }
}
