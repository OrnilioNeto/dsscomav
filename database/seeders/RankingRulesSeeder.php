<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankingRulesSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $startTimeCriteria = DB::table('ranking_criteria')->where('slug', 'start_time')->first();
        $completionCriteria = DB::table('ranking_criteria')->where('slug', 'completion_time')->first();
        $quizCriteria = DB::table('ranking_criteria')->where('slug', 'quiz_result')->first();

        if ($startTimeCriteria) {
            $rules = [
                ['label' => '0-1 hora', 'min_value' => 0, 'max_value' => 1, 'points' => 50, 'sort_order' => 1],
                ['label' => '1-6 horas', 'min_value' => 1, 'max_value' => 6, 'points' => 40, 'sort_order' => 2],
                ['label' => '6-12 horas', 'min_value' => 6, 'max_value' => 12, 'points' => 30, 'sort_order' => 3],
                ['label' => '12-24 horas', 'min_value' => 12, 'max_value' => 24, 'points' => 20, 'sort_order' => 4],
                ['label' => '24-72 horas', 'min_value' => 24, 'max_value' => 72, 'points' => 10, 'sort_order' => 5],
            ];

            foreach ($rules as $r) {
                DB::table('ranking_rules')->updateOrInsert([
                    'criterion_id' => $startTimeCriteria->id,
                    'label' => $r['label']
                ], array_merge($r, ['criterion_id' => $startTimeCriteria->id, 'created_at' => $now, 'updated_at' => $now]));
            }
        }

        if ($completionCriteria) {
            $rules = [
                ['label' => 'Mesmo dia', 'min_value' => 0, 'max_value' => 0, 'points' => 40, 'sort_order' => 1],
                ['label' => '+1 dia', 'min_value' => 1, 'max_value' => 1, 'points' => 30, 'sort_order' => 2],
                ['label' => '+2 dias', 'min_value' => 2, 'max_value' => 2, 'points' => 20, 'sort_order' => 3],
                ['label' => '+3 dias', 'min_value' => 3, 'max_value' => 3, 'points' => 10, 'sort_order' => 4],
                ['label' => '+4 dias ou mais', 'min_value' => 4, 'max_value' => 9999, 'points' => 5, 'sort_order' => 5],
            ];

            foreach ($rules as $r) {
                DB::table('ranking_rules')->updateOrInsert([
                    'criterion_id' => $completionCriteria->id,
                    'label' => $r['label']
                ], array_merge($r, ['criterion_id' => $completionCriteria->id, 'created_at' => $now, 'updated_at' => $now]));
            }
        }

        if ($quizCriteria) {
            $rules = [
                ['label' => 'Primeira tentativa', 'min_value' => 1, 'max_value' => 1, 'points' => 50, 'sort_order' => 1],
                ['label' => 'Segunda tentativa', 'min_value' => 2, 'max_value' => 2, 'points' => 25, 'sort_order' => 2],
                ['label' => 'Reassistiu conteúdo', 'min_value' => 3, 'max_value' => 9999, 'points' => 5, 'sort_order' => 3],
            ];

            foreach ($rules as $r) {
                DB::table('ranking_rules')->updateOrInsert([
                    'criterion_id' => $quizCriteria->id,
                    'label' => $r['label']
                ], array_merge($r, ['criterion_id' => $quizCriteria->id, 'created_at' => $now, 'updated_at' => $now]));
            }
        }
    }
}
