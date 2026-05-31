<?php

namespace App\Repositories;

use App\Models\Certificate;
use App\Services\RankingCalculatorService;
use App\Models\RankingMonthlyScore;
use App\Models\RankingScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingRepository
{
    protected $calculator;

    public function __construct(RankingCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function getTopMonthly(int $month, int $year, int $limit = 10, ?string $role = null)
    {
        $query = RankingMonthlyScore::with('user')
            ->where('month_reference', $month)
            ->where('year_reference', $year)
            ->orderByDesc('average_score');

        if ($role && $role !== 'all') {
            // role can be 'motorista' or 'funcionario' mapped to campo `tipo_usuario` on users
            $query->whereHas('user', function ($q) use ($role) {
                $q->where('tipo_usuario', $role);
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();

        if ($rows->isNotEmpty()) {
            return $this->enrichRowsWithPeriodStats($rows, $month, $year, $role);
        }

        // Fallback: enquanto o consolidado mensal ainda não foi gerado,
        // mostrar os certificados do mês atual para não deixar a tela vazia.
        $fallback = Certificate::query()
            ->with(['user', 'training'])
            ->whereYear('data_emissao', $year)
            ->whereMonth('data_emissao', $month)
            ->orderByDesc('data_emissao');

        if ($role && $role !== 'all') {
            $fallback->whereHas('user', function ($q) use ($role) {
                $q->where('tipo_usuario', $role);
            });
        }

        if ($limit > 0) {
            $fallback->limit($limit);
        }

        $rows = $fallback->get();

        return $this->groupFallbackRowsByUser($rows)
            ->sortByDesc('average_score')
            ->values()
            ->map(function ($row, $index) {
                $row->position = $index + 1;
                $row->fallback_source = 'certificates';
                return $row;
            });
    }

    protected function enrichRowsWithPeriodStats(Collection $rows, int $month, int $year, ?string $role = null): Collection
    {
        $certificates = Certificate::query()
            ->with(['training', 'user'])
            ->whereYear('data_emissao', $year)
            ->whereMonth('data_emissao', $month);

        if ($role && $role !== 'all') {
            $certificates->whereHas('user', function ($q) use ($role) {
                $q->where('tipo_usuario', $role);
            });
        }

        $certificateGroups = $certificates->get()->groupBy('user_id');

        return $rows->map(function ($row) use ($certificateGroups) {
            $userId = $row->user_id ?? optional($row->user)->id;
            $group = $userId ? ($certificateGroups[$userId] ?? collect()) : collect();

            $row->content_count = $group->count();
            $row->last_training_title = optional($group->sortByDesc('data_emissao')->first()?->training)->titulo;

            return $row;
        });
    }

    protected function groupFallbackRowsByUser(Collection $certificates): Collection
    {
        return $certificates->groupBy('user_id')->map(function (Collection $group) {
            $first = $group->first();
            $averageScore = $group->avg(function (Certificate $certificate) {
                return $this->calculateFallbackScore($certificate);
            });

            return (object) [
                'user_id' => $first->user_id,
                'user' => $first->user,
                'average_score' => round((float) $averageScore, 2),
                'content_count' => $group->count(),
                'last_training_title' => optional($group->sortByDesc('data_emissao')->first()?->training)->titulo,
                'training' => $group->sortByDesc('data_emissao')->first()?->training,
            ];
        });
    }

    protected function calculateFallbackScore(Certificate $certificate): float
    {
        $points = 0;

        try {
            $certificate->loadMissing(['training', 'user']);

            $training = $certificate->training;
            if (! $training) {
                return 0;
            }

            $startHours = null;
            if ($training->data_liberacao && $certificate->data_inicio_assistencia) {
                $startHours = $training->data_liberacao->diffInHours($certificate->data_inicio_assistencia);
            }

            $completionDays = null;
            if ($certificate->data_inicio_assistencia && $certificate->data_finalizacao_assistencia) {
                $completionDays = $certificate->data_inicio_assistencia->diffInDays($certificate->data_finalizacao_assistencia);
            }

            $attempts = 1;
            $progress = $certificate->user?->progress()->where('training_id', $training->id)->orderByDesc('updated_at')->first();
            if ($progress && isset($progress->avaliacao_tentativas)) {
                $attempts = ((int) $progress->avaliacao_tentativas) > 0 ? ((int) $progress->avaliacao_tentativas + 1) : 1;
            }

            $criteriaValues = [
                'start_time' => $startHours ?? 9999,
                'completion_time' => $completionDays ?? 9999,
                'quiz_result' => $attempts,
            ];

            $points = $this->calculator->previewScore($criteriaValues);
        } catch (\Throwable $e) {
            $points = 0;
        }

        return round($points, 2);
    }
}
