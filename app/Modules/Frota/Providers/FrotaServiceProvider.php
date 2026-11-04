<?php

namespace App\Modules\Frota\Providers;

use App\Modules\Frota\Console\Commands\DiagnoseFrota;
use App\Modules\Frota\Console\Commands\SyncFrota;
use App\Modules\Frota\Services\AutotracClient;
use App\Modules\Frota\Services\DriverPresenceService;
use App\Modules\Frota\Services\JornadaSyncService;
use App\Support\Modules\ModuleServiceProvider;

class FrotaServiceProvider extends ModuleServiceProvider
{
    protected string $slug = 'frota';

    protected string $name = 'Frota e Jornada';

    /**
     * O container não deve auto-resolver as dependências opcionais destes
     * serviços (ele injetaria um FrotaConfiguracao vazio). Forçamos a
     * construção sem argumentos, que carrega a configuração do tenant.
     */
    public function register(): void
    {
        $this->app->bind(AutotracClient::class, fn () => new AutotracClient);
        $this->app->bind(JornadaSyncService::class, fn () => new JornadaSyncService);
        $this->app->bind(DriverPresenceService::class, fn () => new DriverPresenceService);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerMenu(
            'admin.frota.index',
            'Motoristas Online',
            'fas fa-truck',
            'frota',
            62
        );
    }

    protected function moduleCommands(): array
    {
        return [SyncFrota::class, DiagnoseFrota::class];
    }
}
