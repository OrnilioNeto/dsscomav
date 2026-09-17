<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Certificate;
use App\Observers\CertificateObserver;
use App\Support\TenantManager;
use Illuminate\Database\Query\Builder;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        // Macro de escopo de tenant para queries raw (DB::table).
        // Global scopes Eloquent não cobrem DB::table; use ->whereTenant('tabela').
        Builder::macro('whereTenant', function (?string $table = null) {
            $manager = app(TenantManager::class);

            if (! $manager->isEnabled() || ! $manager->has()) {
                return $this;
            }

            $column = ($table ?? $this->from).'.tenant_id';

            return $this->where($column, $manager->id());
        });

        // Registrar observer para Certificate para acionar recálculo do ranking
        try {
            if (class_exists(Certificate::class)) {
                Certificate::observe(CertificateObserver::class);
            }
        } catch (\Throwable $e) {
            logger()->warning('Falha ao registrar CertificateObserver: ' . $e->getMessage());
        }
    }
}