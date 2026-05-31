<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RankingRecalculationService;

class RankingRecalculate extends Command
{
    protected $signature = 'ranking:recalculate {--month=} {--year=}';
    protected $description = 'Recalcula rankings (mensal).';

    protected $recalculator;

    public function __construct(RankingRecalculationService $recalculator)
    {
        parent::__construct();
        $this->recalculator = $recalculator;
    }

    public function handle()
    {
        $this->info('Iniciando recálculo de rankings...');
        $this->recalculator->recalculateAll();
        $this->info('Recálculo finalizado.');
        return 0;
    }
}
