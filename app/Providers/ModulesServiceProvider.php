<?php

namespace App\Providers;

use App\Support\Modules\ModuleRegistry;
use App\Support\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Descobre e registra os módulos de app/Modules.
 *
 * Convenção: app/Modules/<Nome>/Providers/<Nome>ServiceProvider.php com a
 * classe App\Modules\<Nome>\Providers\<Nome>ServiceProvider estendendo
 * App\Support\Modules\ModuleServiceProvider.
 *
 * Registrar em config/app.php (uma única vez) — módulos novos não exigem
 * editar configuração nem rodar `composer dump-autoload`.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);

        foreach ($this->discoverModuleProviders() as $provider) {
            try {
                $this->app->register($provider);
            } catch (\Throwable $e) {
                logger()->warning("Falha ao registrar o módulo [{$provider}]: {$e->getMessage()}");
            }
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function discoverModuleProviders(): array
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return [];
        }

        $providers = [];

        foreach (glob($modulesPath.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
            $module = basename($moduleDir);
            $class = 'App\\Modules\\'.$module.'\\Providers\\'.$module.'ServiceProvider';

            if (class_exists($class) && is_subclass_of($class, BaseModuleServiceProvider::class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        return $providers;
    }
}
