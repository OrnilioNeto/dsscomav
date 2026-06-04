<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlyRankingService;

class ConsolidateMonthly extends Command
{
    protected $signature = 'ranking:consolidate {--month=} {--year=}';
    protected $description = 'Consolida scores mensais para ranking_monthly_scores';

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

        $this->info("Consolidando rankings para $month/$year...");
        $this->monthly->consolidateMonth($month, $year);
        $this->info('Consolidação concluída!');

        return 0;
    }
}
