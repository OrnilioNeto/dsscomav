<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DemoPrepareCommand extends Command
{
    protected $signature = 'demo:prepare {--force : Permite executar em produção}';

    protected $description = 'Prepara o banco demo (cria DB, migra e popula dados fictícios)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Bloqueado em produção. Use --force para confirmar.');

            return 1;
        }

        // Força uso da conexão demo (SQLite) nesta execução
        config(['database.default' => 'sqlite_demo']);
        DB::purge('sqlite_demo');
        DB::setDefaultConnection('sqlite_demo');

        $this->info('Rodando migrations no banco demo (SQLite)...');
        Artisan::call('migrate', ['--force' => true, '--database' => 'sqlite_demo'], $this->output);

        $this->info('Rodando seeders padrão...');
        Artisan::call('db:seed', ['--force' => true, '--database' => 'sqlite_demo'], $this->output);

        $this->info('Populando dados demo...');
        Artisan::call('demo:seed', ['--force' => $this->option('force')], $this->output);

        $this->info('Banco demo pronto. Ative DEMO_MODE=true no .env e reinicie o serve.');

        return 0;
    }
}
