<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RankingCriteriaSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $criteria = [
            ['name' => 'Tempo para iniciar treinamento', 'slug' => 'start_time', 'description' => 'Tempo entre liberação e primeiro start do usuário.'],
            ['name' => 'Tempo de conclusão', 'slug' => 'completion_time', 'description' => 'Tempo entre início e conclusão do treinamento.'],
            ['name' => 'Resultado da avaliação', 'slug' => 'quiz_result', 'description' => 'Pontuação baseada em tentativas e necessidade de reassistir.'],
        ];

        foreach ($criteria as $c) {
            DB::table('ranking_criteria')->updateOrInsert([
                'slug' => $c['slug']
            ], array_merge($c, ['created_at' => $now, 'updated_at' => $now]));
        }
    }
}
