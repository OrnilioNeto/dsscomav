<?php

namespace App\Modules\CronogramaConteudo\Providers;

use App\Support\Modules\ModuleServiceProvider;

class CronogramaConteudoServiceProvider extends ModuleServiceProvider
{
    protected string $slug = 'cronograma_conteudo';

    protected string $name = 'Cronograma de Conteúdo';

    public function boot(): void
    {
        parent::boot();

        $this->registerMenu('admin.cronograma.index', 'Cronograma de Conteúdo', 'fas fa-calendar-alt', 'cronograma_conteudo', 55);
    }
}