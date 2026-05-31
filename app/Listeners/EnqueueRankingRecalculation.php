<?php

namespace App\Listeners;

use App\Events\TrainingCompleted;
use App\Services\RankingRecalculationService;

class EnqueueRankingRecalculation
{
    protected $recalculator;

    public function __construct(RankingRecalculationService $recalculator)
    {
        $this->recalculator = $recalculator;
    }

    public function handle(TrainingCompleted $event)
    {
        // Chamar recálculo pontual para o certificado
        try {
            $this->recalculator->recalculateForCertificate($event->certificate);
        } catch (\Exception $e) {
            // Log se necessário
        }
    }
}
