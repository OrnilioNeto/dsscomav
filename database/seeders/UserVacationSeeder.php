<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserVacation;
use Illuminate\Database\Seeder;

class UserVacationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNotNull('ferias_inicio')
            ->whereNotNull('ferias_fim')
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $exists = UserVacation::where('user_id', $user->id)
                ->where('data_inicio', $user->ferias_inicio->format('Y-m-d'))
                ->where('data_fim', $user->ferias_fim->format('Y-m-d'))
                ->exists();

            if (!$exists) {
                UserVacation::create([
                    'user_id' => $user->id,
                    'data_inicio' => $user->ferias_inicio->format('Y-m-d'),
                    'data_fim' => $user->ferias_fim->format('Y-m-d'),
                ]);
                $count++;
            }
        }

        $this->command->info("Histórico de férias populado: {$count} registro(s) criado(s).");
    }
}
