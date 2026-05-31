<?php

namespace App\Services;

use App\Models\RankingCriterion;
use App\Models\RankingRule;

class RankingRuleResolverService
{
    /**
     * Encontrar a regra aplicável para um critério e um valor.
     * Retorna pontos (int) ou null se não encontrado.
     */
    public function resolvePoints(string $criterionSlug, $value): ?int
    {
        $criterion = RankingCriterion::where('slug', $criterionSlug)->first();
        if (! $criterion) return null;

        $rule = RankingRule::where('criterion_id', $criterion->id)
            ->where('min_value', '<=', $value)
            ->where('max_value', '>=', $value)
            ->orderBy('sort_order')
            ->first();

        return $rule ? (int) $rule->points : null;
    }

    /**
     * Retorna a regra aplicada (modelo) para um critério e valor — útil para mostrar label/min/max.
     * Mantém compatibilidade com resolvePoints.
     */
    public function resolveRule(string $criterionSlug, $value): ?RankingRule
    {
        $criterion = RankingCriterion::where('slug', $criterionSlug)->first();
        if (! $criterion) return null;

        return RankingRule::where('criterion_id', $criterion->id)
            ->where('min_value', '<=', $value)
            ->where('max_value', '>=', $value)
            ->orderBy('sort_order')
            ->first();
    }
}
