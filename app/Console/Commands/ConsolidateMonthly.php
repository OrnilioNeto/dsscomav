<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyRankingService;
use App\Support\TenantManager;

class ConsolidateMonthly extends Command
{
    protected $signature = 'ranking:consolidate {--month=} {--year=}';
    protected $description = 'Consolida scores mensais para ranking_monthly_scores. Iterage por tenant quando o multi-tenancy está ativo.';

    protected $monthly;

    public function __construct(MonthlyRankingService $monthly)
    {
        parent::__construct();
        $this->monthly = $monthly;
    }

    public function handle()
    {
        $month = $this->option('month') ? (int) $this->option('month') : now()->month;
        $year = $this->option('year') ? (int) $this->option('year') : now()->year;

        $manager = app(TenantManager::class);

        $this->info("Consolidando rankings para $month/$year...");

        $manager->runForEachTenant(function ($tenant) use ($month, $year) {
            if ($tenant) {
                $this->info("  -> Tenant: {$tenant->nome} (#{$tenant->id})");
            }
            $this->monthly->consolidateMonth($month, $year);
        });

        $this->info('Consolidação concluída!');

        return 0;
    }
}