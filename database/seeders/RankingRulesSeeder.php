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
                ['label' => 'Elite (até 1h)', 'min_value' => 0, 'max_value' => 1, 'points' => 150, 'sort_order' => 1], // Play entre 08:30 e 09:30
                ['label' => 'Prontidão Máxima (1h a 4h)', 'min_value' => 1.0001, 'max_value' => 4, 'points' => 100, 'sort_order' => 2], // Play até 12:30
                ['label' => 'Proativo (4h a 12h)', 'min_value' => 4.0001, 'max_value' => 12, 'points' => 70, 'sort_order' => 3], // Tarde de segunda
                ['label' => 'Mesmo Dia (12h a 24h)', 'min_value' => 12.0001, 'max_value' => 24, 'points' => 40, 'sort_order' => 4], // Noite de segunda
                ['label' => 'Tardio (após 24h)', 'min_value' => 24.0001, 'max_value' => 9999, 'points' => 10, 'sort_order' => 5], // A partir de terça
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
                ['label' => 'Mesmo dia (0 dias)', 'min_value' => 0, 'max_value' => 0, 'points' => 60, 'sort_order' => 1],
                ['label' => 'Até 1 dia', 'min_value' => 0.0001, 'max_value' => 1, 'points' => 45, 'sort_order' => 2],
                ['label' => 'Até 3 dias', 'min_value' => 1.0001, 'max_value' => 3, 'points' => 25, 'sort_order' => 3],
                ['label' => 'Até 7 dias', 'min_value' => 3.0001, 'max_value' => 7, 'points' => 10, 'sort_order' => 4],
                ['label' => 'Mais de 7 dias', 'min_value' => 7.0001, 'max_value' => 9999, 'points' => 5, 'sort_order' => 5],
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
                ['label' => '1ª Tentativa', 'min_value' => 1, 'max_value' => 1, 'points' => 70, 'sort_order' => 1],
                ['label' => '2ª Tentativa', 'min_value' => 2, 'max_value' => 2, 'points' => 30, 'sort_order' => 2],
                ['label' => '3+ Tentativas', 'min_value' => 3, 'max_value' => 9999, 'points' => 10, 'sort_order' => 3],
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
