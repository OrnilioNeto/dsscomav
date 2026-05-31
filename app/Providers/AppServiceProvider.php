<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\Certificate;
use App\Observers\CertificateObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->ensureUserOperationalColumns();
        // Registrar observer para Certificate para acionar recálculo do ranking
        try {
            if (class_exists(Certificate::class)) {
                Certificate::observe(CertificateObserver::class);
            }
        } catch (\Throwable $e) {
            logger()->warning('Falha ao registrar CertificateObserver: ' . $e->getMessage());
        }
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
