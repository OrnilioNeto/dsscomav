<?php

namespace App\Providers;

use App\Models\Certificate;
use App\Observers\CertificateObserver;
use App\Services\AuditLogger;
use App\Support\TenantManager;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
            logger()->warning('Falha ao registrar CertificateObserver: '.$e->getMessage());
        }

        // Auditoria de autenticação (login, falha e logout)
        Event::listen(Login::class, function (Login $event) {
            app(AuditLogger::class)->log('login', [
                'user_id' => $event->user?->getAuthIdentifier(),
                'module' => 'auth',
                'description' => 'Login realizado: '.($event->user->nome ?? ''),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            app(AuditLogger::class)->log('login_failed', [
                'user_id' => $event->user?->getAuthIdentifier(),
                'module' => 'auth',
                'description' => 'Falha de autenticação',
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            app(AuditLogger::class)->log('logout', [
                'user_id' => $event->user?->getAuthIdentifier(),
                'module' => 'auth',
                'description' => 'Logout realizado: '.($event->user->nome ?? ''),
            ]);
        });
    }
}
