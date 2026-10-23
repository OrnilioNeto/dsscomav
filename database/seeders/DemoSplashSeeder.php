<?php

namespace Database\Seeders;

use App\Models\SplashContent;
use Illuminate\Database\Seeder;

class DemoSplashSeeder extends Seeder
{
    public function run(): void
    {
        SplashContent::firstOrCreate(
            ['titulo' => 'Bem-vindo à Plataforma DSS'],
            [
                'texto_conteudo' => 'Mantenha seus treinamentos em dia e contribua para um ambiente de trabalho mais seguro.',
                'material_tipo' => 'texto',
                'data_inicio' => now()->toDateString(),
                'data_fim' => now()->addMonths(3)->toDateString(),
                'status' => 'ativo',
                'ordem' => 1,
            ]
        );
    }
}
