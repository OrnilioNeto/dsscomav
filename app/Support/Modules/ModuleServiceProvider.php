<?php

namespace App\Support\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Classe base para Service Providers de módulos em app/Modules/<Nome>.
 *
 * Estrutura esperada do módulo:
 *
 *   app/Modules/Agendamento/
 *   ├── Providers/AgendamentoServiceProvider.php
 *   ├── Http/Controllers/...
 *   ├── Models/...
 *   ├── routes/web.php            (opcional)
 *   ├── routes/api.php            (opcional)
 *   ├── resources/views/...       (views no namespace `agendamento::`)
 *   ├── database/migrations/...   (rodam no `php artisan migrate`)
 *   ├── Console/Commands/...      (opcional)
 *   └── lang/...                  (opcional)
 *
 * O provider filho só precisa definir `$slug`; o registro é automático
 * via App\Providers\ModulesServiceProvider (ver config/app.php).
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Slug do módulo (ex.: 'agendamento'). Usado como namespace de views,
     * traduções e como permissão default do item de menu.
     */
    protected string $slug = '';

    /**
     * Nome de exibição (documentação/menus).
     */
    protected string $name = '';

    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleViews();
        $this->loadModuleMigrations();
        $this->loadModuleCommands();
        $this->loadModuleTranslations();
    }

    public function moduleSlug(): string
    {
        return $this->slug !== ''
            ? $this->slug
            : strtolower(class_basename(static::class));
    }

    public function moduleName(): string
    {
        return $this->name !== '' ? $this->name : ucfirst($this->moduleSlug());
    }

    /**
     * Raiz do módulo (pasta que contém Providers/).
     * Os providers devem ficar em <Modulo>/Providers/<Modulo>ServiceProvider.php.
     */
    protected function moduleRoot(): string
    {
        $file = (new \ReflectionClass(static::class))->getFileName();

        return dirname((string) $file, 2);
    }

    protected function modulePath(string $path = ''): string
    {
        $root = $this->moduleRoot();

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Registra um item no menu de administração (renderizado pelo layout para
     * quem tiver a permissão informada).
     */
    protected function registerMenu(
        string $route,
        string $label,
        string $icon = 'fas fa-cube',
        ?string $permission = null,
        int $order = 100
    ): void {
        app(ModuleRegistry::class)->register([
            'slug' => $this->moduleSlug(),
            'route' => $route,
            'label' => $label,
            'icon' => $icon,
            'permission' => $permission ?: $this->moduleSlug(),
            'order' => $order,
        ]);
    }

    /**
     * Rotas do módulo: routes/web.php no grupo `web` e routes/api.php no
     * grupo `api` com prefixo /api (os middlewares de auth/permissão ficam
     * dentro dos arquivos do módulo).
     */
    protected function loadModuleRoutes(): void
    {
        $web = $this->modulePath('routes/web.php');

        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $api = $this->modulePath('routes/api.php');

        if (is_file($api)) {
            Route::prefix('api')->middleware('api')->group($api);
        }
    }

    protected function loadModuleViews(): void
    {
        $views = $this->modulePath('resources/views');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, $this->moduleSlug());
        }
    }

    protected function loadModuleMigrations(): void
    {
        $migrations = $this->modulePath('database/migrations');

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    protected function loadModuleCommands(): void
    {
        $commands = $this->moduleCommands();

        if (! empty($commands)) {
            $this->commands($commands);
        }
    }

    /**
     * Comandos artisan do módulo (sobrescreva no provider filho).
     *
     * @return array<int, class-string>
     */
    protected function moduleCommands(): array
    {
        return [];
    }

    protected function loadModuleTranslations(): void
    {
        foreach (['lang', 'resources/lang'] as $dir) {
            $path = $this->modulePath($dir);

            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $this->moduleSlug());

                return;
            }
        }
    }
}
