<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            UserSeeder::class,
            TrainingSeeder::class,
            \Database\Seeders\RankingCriteriaSeeder::class,
            \Database\Seeders\RankingRulesSeeder::class,
        ]);
    }
}
