<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Services\RankingRecalculationService;
use App\Services\RankingMonthlyConsolidationService;
use Illuminate\Support\Facades\Log;

class CertificateObserver
{
    protected RankingRecalculationService $recalculator;
    protected RankingMonthlyConsolidationService $consolidation;

    public function __construct()
    {
        $this->recalculator  = app(RankingRecalculationService::class);
        $this->consolidation = app(RankingMonthlyConsolidationService::class);
    }

    /**
     * Ao criar um certificado:
     * 1. Atualiza o ranking_scores do usuário (recálculo pontual).
     * 2. Reconsolida o ranking_monthly_scores do período, para que todos
     *    os usuários vejam sua posição atualizada imediatamente.
     */
    public function created(Certificate $certificate): void
    {
        try {
            // Passo 1: recalcula ranking_scores para este certificado específico
            $period = $this->recalculator->recalculateForCertificate($certificate);

            // Passo 2: reconsolida ranking_monthly_scores para o período afetado
            if ($period) {
                [$year, $month] = array_map('intval', explode('-', $period));
            } else {
                $source = $certificate->data_emissao
                    ?? $certificate->data_finalizacao_assistencia
                    ?? now();
                $month = (int) $source->month;
                $year  = (int) $source->year;
            }

            $count = $this->consolidation->consolidate($month, $year);

            Log::info("CertificateObserver: ranking consolidado para {$month}/{$year} após certificado #{$certificate->id} ({$count} usuários).");
        } catch (\Throwable $e) {
            Log::error('CertificateObserver: erro ao consolidar ranking após certificado #' . $certificate->id . ': ' . $e->getMessage());
        }
    }
}
