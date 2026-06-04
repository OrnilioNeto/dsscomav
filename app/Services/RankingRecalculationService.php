<?php

namespace App\Services;

use App\Models\Certificate;
use App\Services\RankingCalculatorService;
use App\Services\MonthlyRankingService;

class RankingRecalculationService
{
    protected $calculator;
    protected $monthly;

    public function __construct(RankingCalculatorService $calculator, MonthlyRankingService $monthly)
    {
        $this->calculator = $calculator;
        $this->monthly = $monthly;
    }

    public function recalculateAll()
    {
        $periods = [];

        Certificate::with(['user', 'training'])
            ->where('valido', true)
            ->orderBy('data_emissao')
            ->chunk(200, function ($certificates) use (&$periods) {
                foreach ($certificates as $certificate) {
                    $period = $this->recalculateForCertificate($certificate);
                    if ($period) {
                        $periods[$period] = true;
                    }
                }
            });

        if (empty($periods)) {
            $this->monthly->consolidateMonth();
            return;
        }

        foreach (array_keys($periods) as $periodKey) {
            [$month, $year] = array_map('intval', explode('-', $periodKey));
            $this->monthly->consolidateMonth($month, $year);
        }
    }

    public function recalculateForCertificate($certificate): ?string
    {
        $certificate->loadMissing(['user', 'training']);

        $training = $certificate->training;
        $user = $certificate->user;

        if (! $training || ! $user) {
            return null;
        }

        // Critério 1: tempo desde liberação até início (horas)
        $startHours = null;
        if ($training->data_liberacao && $certificate->data_inicio_assistencia) {
            try {
                $startHours = $training->data_liberacao->diffInHours($certificate->data_inicio_assistencia);
            } catch (\Throwable $e) {
                $startHours = null;
            }
        }

        // Critério 2: tempo de conclusão (dias)
        $completionDays = null;
        if ($certificate->data_inicio_assistencia && $certificate->data_finalizacao_assistencia) {
            try {
                $completionDays = $certificate->data_inicio_assistencia->diffInDays($certificate->data_finalizacao_assistencia);
            } catch (\Throwable $e) {
                $completionDays = null;
            }
        }

        // Critério 3: resultado da avaliação (tentativas)
        // Nota: a plataforma reseta 'avaliacao_tentativas' ao aprovar a avaliação,
        // portanto não é sempre possível inferir o número real de tentativas. Como fallback,
        // assumimos 1 (acertou na primeira tentativa) quando não há informação.
        $attempts = 1;
        try {
            $progress = $user->progress()->where('training_id', $training->id)->orderByDesc('updated_at')->first();
            if ($progress && isset($progress->avaliacao_tentativas)) {
                $t = (int) $progress->avaliacao_tentativas;
                // se zero (comum após aprovação), manter 1 como fallback
                $attempts = $t > 0 ? ($t + 1) : 1;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $criteriaValues = [
            'start_time' => $startHours ?? 9999,
            'completion_time' => $completionDays ?? 9999,
            'quiz_result' => $attempts,
        ];

        $periodSource = $certificate->data_emissao
            ?? $certificate->data_finalizacao_assistencia
            ?? now();

        $month = (int) $periodSource->month;
        $year = (int) $periodSource->year;

        // Calcular e persistir score para este conteúdo
        $this->calculator->calculateForContent($user->id, $training->id, null, $criteriaValues, $month, $year);

        return sprintf('%04d-%02d', $year, $month);
    }
}
