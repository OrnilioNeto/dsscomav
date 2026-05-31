<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Services\RankingRecalculationService;

class CertificateObserver
{
    protected $recalculator;

    public function __construct()
    {
        $this->recalculator = app(RankingRecalculationService::class);
    }

    public function created(Certificate $certificate)
    {
        // Ao criar certificado, tentar recalcular ranking para este conteúdo.
        try {
            $this->recalculator->recalculateForCertificate($certificate);
        } catch (\Throwable $e) {
            logger()->error('Erro ao recalcular ranking para certificado: ' . $e->getMessage());
        }
    }
}
