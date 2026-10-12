<?php

namespace App\Console\Commands;

use App\Services\EpiVencimentoService;
use App\Support\TenantManager;
use Illuminate\Console\Command;

class EpiRecalcularVencimentos extends Command
{
    protected $signature = 'epi:recalcular-vencimentos';

    protected $description = 'Recalcula o vencimento das entregas ativas (data de entrega + vida útil atual do EPI). Iterage por tenant quando o multi-tenancy está ativo.';

    public function handle(EpiVencimentoService $vencimentos)
    {
        $manager = app(TenantManager::class);

        $this->info('Recalculando vencimentos das entregas ativas...');

        $manager->runForEachTenant(function ($tenant) use ($vencimentos) {
            if ($tenant) {
                $this->info("  -> Tenant: {$tenant->nome} (#{$tenant->id})");
            }

            $total = $vencimentos->recalcularTodos();

            $this->info("  Entregas ativas recalculadas: {$total}");
        });

        $this->info('Recálculo finalizado.');

        return 0;
    }
}
