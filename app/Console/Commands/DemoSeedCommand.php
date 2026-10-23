<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed {--force : Permite executar em produção}';

    protected $description = 'Popula o banco ativo com usuários e dados fictícios de demonstração';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Bloqueado em produção. Use --force para confirmar.');

            return 1;
        }

        $this->info('Populando dados demo...');
        foreach ([
            'Database\\Seeders\\DemoMarketingSeeder',
            'Database\\Seeders\\DemoEpiSeeder',
            'Database\\Seeders\\DemoFichaSeeder',
            'Database\\Seeders\\DemoSplashSeeder',
        ] as $seeder) {
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ], $this->output);
        }

        $this->info('Recalculando ranking...');
        try {
            Artisan::call('ranking:recalculate', [], $this->output);
        } catch (\Throwable $e) {
            $this->warn('ranking:recalculate falhou: '.$e->getMessage());
        }

        $this->info('Dados demo populados.');

        return 0;
    }
}
