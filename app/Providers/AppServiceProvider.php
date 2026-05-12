<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->ensureUserOperationalColumns();
    }

    private function ensureUserOperationalColumns(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            Schema::table('users', function ($table) {
                if (! Schema::hasColumn('users', 'ferias_inicio')) {
                    $table->date('ferias_inicio')->nullable()->after('responsavel');
                }

                if (! Schema::hasColumn('users', 'ferias_fim')) {
                    $table->date('ferias_fim')->nullable()->after('ferias_inicio');
                }

                if (! Schema::hasColumn('users', 'usuario_teste')) {
                    $table->boolean('usuario_teste')->default(false)->after('ferias_fim');
                }
            });
        } catch (\Throwable $e) {
            logger()->warning('Falha ao garantir colunas operacionais de users: ' . $e->getMessage());
        }
    }
}
