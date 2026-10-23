<?php

namespace Database\Seeders;

use App\Models\EmployeeTraining;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFichaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = ['NR-35 Trabalho em Altura', 'NR-10 Segurança em Eletricidade', 'Direção Defensiva', 'Primeiros Socorros'];
        $users = User::where('email', 'like', '%@demo.plataformadss.local')->get();

        foreach ($users->take(8) as $user) {
            for ($i = 0; $i < 2; $i++) {
                EmployeeTraining::firstOrCreate(
                    ['user_id' => $user->id, 'nome' => $tipos[($user->id + $i) % count($tipos)]],
                    [
                        'data_treinamento' => now()->subDays(rand(30, 120))->toDateString(),
                        'data_validade' => now()->addDays(rand(60, 365))->toDateString(),
                        'observacoes' => 'Treinamento registrado na ficha (demo).',
                    ]
                );
            }
        }
    }
}
