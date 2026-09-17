<?php

namespace App\Modules\Exemplo\Providers;

use App\Support\Modules\ModuleServiceProvider;

class ExemploServiceProvider extends ModuleServiceProvider
{
    protected string $slug = 'exemplo';

    protected string $name = 'Módulo de Exemplo';

    public function boot(): void
    {
        parent::boot();

        // Item de menu: aparece no menu Administração apenas para quem tiver
        // permissão (super_admin sempre passa; perfis comuns não veem).
        $this->registerMenu(
            route: 'exemplo.index',
            label: 'Exemplo (referência)',
            icon: 'fas fa-cube',
            order: 999
        );
    }
}
