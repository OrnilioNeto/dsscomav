<?php

namespace App\Services;

use App\Models\UserProgress;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Arr;

class TrainingAnalyzer
{
    /**
     * Retorna métricas agregadas para um treinamento (ou para todos quando null)
     * Usa Certificate como fonte de verdade para conclusões reais
     * @param int|null $trainingId
     * @return array
     */
    public function analyze(?int $trainingId = null): array
    {
        $trainingFilter = $trainingId ? Training::find($trainingId) : null;

        // Obter usuários ativos elegíveis (mesma lógica do dashboard)
        $usuariosAtivosQuery = User::kpiEligible()->where('status', 'ativo');
        if ($trainingFilter) {
            $usuariosAtivosQuery->eligibleForTrainingKpi($trainingFilter);
        }
        $totalUsuariosAtivos = $usuariosAtivosQuery->count();

        // Obter certificados (fonte de verdade para conclusões)
        $certQuery = Certificate::where('valido', true);
        if ($trainingId) $certQuery->where('training_id', $trainingId);
        if ($trainingFilter) {
            $certQuery->whereHas('user', function ($q) use ($trainingFilter) {
                $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
            });
        }
        
        $certificates = $certQuery->get();

        // Obter progressos para métricas complementares
        $progQuery = UserProgress::query();
        if ($trainingId) $progQuery->where('training_id', $trainingId);
        if ($trainingFilter) {
            $progQuery->whereHas('user', function ($q) use ($trainingFilter) {
                $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
            });
        }
        
        $progressos = $progQuery->get();

        $total = $progressos->count();
        $concluidos = $certificates->count(); // Contar certificados válidos
        $iniciados = $progressos->whereNotNull('data_inicio')->count();

        // Tempo médio assistido - usar Certificate quando disponível, senão UserProgress
        $times = [];
        if ($certificates->count() > 0) {
            $times = $certificates->map(function($c){ return (int)($c->tempo_assistido_segundos ?? 0); })->filter()->values();
        } else {
            $times = $progressos->map(function($p){ return (int)($p->tempo_assistido ?? 0); })->filter()->values();
        }
        $avgSeconds = $times->count() ? (int)($times->sum() / $times->count()) : 0;

        // Dias médios para conclusão (entre data_inicio_assistencia e data_finalizacao_assistencia)
        // Usar Certificate como base, com dias de calendário
        $durations = $certificates->filter(function($c){ 
            return $c->data_inicio_assistencia && $c->data_finalizacao_assistencia; 
        })->map(function($c){
            $start = strtotime($c->data_inicio_assistencia);
            $end = strtotime($c->data_finalizacao_assistencia);
            // Calcular dias de calendário: ceil para contar parciais como 1 dia
            return max(1, ceil(($end - $start) / 86400));
        });
        $avgDays = $durations->count() ? round($durations->avg(), 1) : null;

        // Usuários com certificados (participantes com sucesso)
        // Filtrar certificados removendo super_admin, usuário de teste e admin que não participa
        $certUserIds = $certificates->pluck('user_id')->unique()->values()->all();
        $certUsers = User::whereIn('id', $certUserIds)->get()->keyBy('id');

        $filteredCertUserIds = [];
        $vacationIncluded = 0;
        foreach ($certUserIds as $uid) {
            $u = $certUsers->get($uid);
            if (! $u) continue;
            // Excluir super_admin e usuários de teste
            if ($u->isSuperAdmin() || $u->isTestUser()) continue;
            // Excluir admin que não participa de treinamentos
            if ($u->role && $u->role->nome === 'admin' && ! $u->participa_treinamentos) continue;

            $filteredCertUserIds[] = $uid;

            // Se está em férias atualmente e gerou certificado, contaremos para o denominador efetivo
            if ($u->isOnVacation()) {
                $vacationIncluded++;
            }
        }

        $usuariosComCertificado = count($filteredCertUserIds);

        // Denominador efetivo: usuários ativos elegíveis + aqueles em férias que participaram (evita duplicação)
        $usuariosAtivosEfectivos = $totalUsuariosAtivos + $vacationIncluded;

        // Métrica crítica: % de usuários ativos (efetivo) que completaram
        $percentualUsuariosAtivos = $usuariosAtivosEfectivos > 0
            ? round(($usuariosComCertificado / $usuariosAtivosEfectivos) * 100, 1)
            : 0;

        // compor resumo heurístico
        $metrics = [
            'total_progressos' => $total,
            'usuarios_ativos_total' => $totalUsuariosAtivos,
            'usuarios_ativos_total_effective' => $usuariosAtivosEfectivos,
            'usuarios_com_certificado' => $usuariosComCertificado,
            'percentual_usuarios_ativos' => $percentualUsuariosAtivos,
            'iniciados' => $iniciados,
            'concluidos' => $concluidos,
            'percent_concluidos' => $total ? round(($concluidos / $total) * 100, 1) : 0,
            'avg_time_seconds' => $avgSeconds,
            'avg_time_human' => $this->secondsToHms($avgSeconds),
            'avg_days_to_complete' => $avgDays,
        ];

        $metrics['human_summary'] = $this->composeSummary($metrics, $trainingId);

        return $metrics;
    }

    protected function secondsToHms(int $seconds): string
    {
        if ($seconds <= 0) return '00:00:00';
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    protected function composeSummary(array $m, $trainingId = null): string
    {
        $title = $trainingId ? "Treinamento #{$trainingId}" : 'Conjunto de treinamentos';
        $parts = [];
        $parts[] = "Análise gerencial para: {$title}.";
        $parts[] = "Usuários ativos elegíveis: {$m['usuarios_ativos_total']} (ajustado: {$m['usuarios_ativos_total_effective']}), Participantes com certificado: {$m['usuarios_com_certificado']} ({$m['percentual_usuarios_ativos']}% dos efetivos).";
        $parts[] = "Iniciaram: {$m['iniciados']}, Concluíram: {$m['concluidos']} ({$m['percent_concluidos']}%).";
        $parts[] = "Tempo médio assistido: {$m['avg_time_human']}.";
        if ($m['avg_days_to_complete'] !== null) {
            $parts[] = "Dias médios para conclusão: {$m['avg_days_to_complete']} dias.";
        }
        $parts[] = "Análise finalizada com sucesso.";
        return implode(' ', $parts);
    }
}
