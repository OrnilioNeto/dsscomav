<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Semeadura dos critérios/regras padrão de ranking.
     * Antes feita em runtime pelo AppServiceProvider::seedDefaultRankingCriteria.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ranking_criteria') || DB::table('ranking_criteria')->count() > 0) {
            return;
        }

        $criteria = [
            ['name' => 'Velocidade de Início', 'slug' => 'start_time', 'description' => 'Horas decorridas entre a liberação e o início do conteúdo', 'sort_order' => 1],
            ['name' => 'Tempo de Conclusão', 'slug' => 'completion_time', 'description' => 'Dias decorridos entre o início e a finalização', 'sort_order' => 2],
            ['name' => 'Resultado da Avaliação', 'slug' => 'quiz_result', 'description' => 'Número de tentativas para aprovação', 'sort_order' => 3],
        ];

        foreach ($criteria as $criterion) {
            $id = DB::table('ranking_criteria')->insertGetId(array_merge($criterion, ['created_at' => now(), 'updated_at' => now()]));

            if ($criterion['slug'] === 'start_time') {
                $rules = [
                    ['label' => 'Pioneiro (até 24h)', 'min_value' => 0, 'max_value' => 24, 'points' => 100, 'sort_order' => 1],
                    ['label' => 'Rápido (até 48h)', 'min_value' => 24.1, 'max_value' => 48, 'points' => 50, 'sort_order' => 2],
                    ['label' => 'Normal (após 48h)', 'min_value' => 48.1, 'max_value' => 9999, 'points' => 10, 'sort_order' => 3],
                ];
            } elseif ($criterion['slug'] === 'completion_time') {
                $rules = [
                    ['label' => 'Foco Total (mesmo dia)', 'min_value' => 0, 'max_value' => 0, 'points' => 50, 'sort_order' => 1],
                    ['label' => 'Intermediário (até 3 dias)', 'min_value' => 1, 'max_value' => 3, 'points' => 30, 'sort_order' => 2],
                    ['label' => 'Lento (mais de 3 dias)', 'min_value' => 3.1, 'max_value' => 999, 'points' => 5, 'sort_order' => 3],
                ];
            } else {
                $rules = [
                    ['label' => 'Excelente (1ª tentativa)', 'min_value' => 1, 'max_value' => 1, 'points' => 100, 'sort_order' => 1],
                    ['label' => 'Bom (2ª tentativa)', 'min_value' => 2, 'max_value' => 2, 'points' => 50, 'sort_order' => 2],
                    ['label' => 'Recuperação (3+ tentativas)', 'min_value' => 3, 'max_value' => 99, 'points' => 20, 'sort_order' => 3],
                ];
            }

            foreach ($rules as $rule) {
                DB::table('ranking_rules')->insert(array_merge($rule, ['criterion_id' => $id, 'created_at' => now(), 'updated_at' => now()]));
            }
        }
    }

    public function down(): void
    {
        // Não remove dados semeados (regra de segurança em produção).
    }
};
