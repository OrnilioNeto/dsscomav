<?php

namespace App\Modules\LembretesWhatsapp\Providers;

use App\Modules\LembretesWhatsapp\Console\Commands\DiagnoseReminders;
use App\Modules\LembretesWhatsapp\Console\Commands\ProcessReminders;
use App\Support\Modules\ModuleServiceProvider;

class LembretesWhatsappServiceProvider extends ModuleServiceProvider
{
    protected string $slug = 'lembretes_whatsapp';

    protected string $name = 'Lembretes WhatsApp';

    public function boot(): void
    {
        parent::boot();

        $this->registerMenu('admin.lembretes.disparo', 'Lembretes WhatsApp', 'fab fa-whatsapp', 'lembretes_whatsapp', 60);
    }

    protected function moduleCommands(): array
    {
        return [ProcessReminders::class, DiagnoseReminders::class];
    }
}
