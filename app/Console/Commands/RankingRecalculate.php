<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RankingRecalculationService;
use App\Support\TenantManager;

class RankingRecalculate extends Command
{
    protected $signature = 'ranking:recalculate {--month=} {--year=}';
    protected $description = 'Recalcula rankings (mensal). Iterage por tenant quando o multi-tenancy está ativo.';

    protected $recalculator;

    public function __construct(RankingRecalculationService $recalculator)
    {
        parent::__construct();
        $this->recalculator = $recalculator;
    }

    public function handle()
    {
        $manager = app(TenantManager::class);

        $this->info('Iniciando recálculo de rankings...');

        $manager->runForEachTenant(function ($tenant) {
            if ($tenant) {
                $this->info("  -> Tenant: {$tenant->nome} (#{$tenant->id})");
            }
            $this->recalculator->recalculateAll();
        });

        $this->info('Recálculo finalizado.');

        return 0;
    }
}