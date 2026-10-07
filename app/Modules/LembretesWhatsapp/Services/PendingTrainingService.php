<?php

namespace App\Modules\LembretesWhatsapp\Services;

use App\Models\Training;
use App\Models\User;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Support\TenantManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Fonte dos colaboradores "pendentes" para um treinamento, reutilizando a
 * mesma regra de elegibilidade dos relatórios (kpiEligible + eligibleForContent)
 * e excluindo quem já concluiu.
 */
class PendingTrainingService
{
    private const PANORAMA_TTL_MINUTES = 10;

    /**
     * @param  array<int, int>|null  $userIds  restringe a seleção do gestor
     * @return Collection<int, User>
     */
    public function pendentes(Training $training, User $gestor, ?array $userIds = null): Collection
    {
        $query = User::query()
            ->kpiEligible()
            ->eligibleForContent($training)
            ->where(function (Builder $q) use ($training) {
                $q->whereDoesntHave('progress', function ($progress) use ($training) {
                    $progress->where('training_id', $training->id);
                })->orWhereHas('progress', function ($progress) use ($training) {
                    $progress->where('training_id', $training->id)->where('concluido', false);
                });
            })
            ->orderBy('nome');

        $this->aplicarEscopoVisibilidade($query, $gestor);

        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }

        return $query->get();
    }

    /**
     * Contagens da base elegível de um treinamento (mesma regra do disparo).
     *
     * @return array{elegiveis: int, concluidos: int, pendentes: int}
     */
    public function contagensDoTreinamento(Training $training, User $gestor): array
    {
        $base = User::query()->kpiEligible()->eligibleForContent($training);
        $this->aplicarEscopoVisibilidade($base, $gestor);

        $elegiveis = (clone $base)->count();
        $concluidos = (clone $base)
            ->whereHas('progress', function ($progress) use ($training) {
                $progress->where('training_id', $training->id)->where('concluido', true);
            })
            ->count();

        return [
            'elegiveis' => $elegiveis,
            'concluidos' => $concluidos,
            'pendentes' => max(0, $elegiveis - $concluidos),
        ];
    }

    /**
     * Panorama de todos os treinamentos com a contagem de pendentes.
     * Contagens só são calculadas para treinamentos ativos e liberados;
     * resultado cacheado para não repetir as queries a cada abertura.
     *
     * @return Collection<int, object{training: Training, ativo: bool, liberado: bool, elegiveis: ?int, concluidos: ?int, pendentes: ?int}>
     */
    public function panorama(User $gestor, bool $atualizar = false): Collection
    {
        $cacheKey = 'wa_panorama:'.(app(TenantManager::class)->id() ?? 'global').':'.($gestor->isSuperAdmin() ? 'all' : 'restrito');

        if ($atualizar) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(self::PANORAMA_TTL_MINUTES), function () use ($gestor) {
            return Training::query()
                ->orderByReleaseDate('desc')
                ->get()
                ->map(function (Training $training) use ($gestor) {
                    $ativo = $training->status === 'ativo';
                    $liberado = $training->isReleased();
                    $contagens = ($ativo && $liberado)
                        ? $this->contagensDoTreinamento($training, $gestor)
                        : ['elegiveis' => null, 'concluidos' => null, 'pendentes' => null];

                    return (object) [
                        'training' => $training,
                        'ativo' => $ativo,
                        'liberado' => $liberado,
                        'elegiveis' => $contagens['elegiveis'],
                        'concluidos' => $contagens['concluidos'],
                        'pendentes' => $contagens['pendentes'],
                    ];
                });
        });
    }

    /**
     * Mesma regra do relatório: admin vê usuários comuns; super admin vê todos.
     */
    public function aplicarEscopoVisibilidade(Builder $query, User $gestor): void
    {
        if ($gestor->isSuperAdmin()) {
            return;
        }

        $query->where(function (Builder $sub) {
            $sub->whereNull('role_id')
                ->orWhereHas('role', function ($role) {
                    $role->whereNotIn('nome', ['admin', 'super_admin']);
                });
        });
    }

    /**
     * Último lembrete enviado hoje para cada usuário (aviso de reenvio no dia).
     *
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, TrainingReminder> keyed por user_id
     */
    public function enviosDeHoje(Training $training, Collection $userIds): Collection
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return TrainingReminder::query()
            ->where('training_id', $training->id)
            ->whereIn('user_id', $userIds)
            ->where('status', TrainingReminder::STATUS_ENVIADO)
            ->whereDate('enviado_em', Carbon::now(config('app.timezone'))->toDateString())
            ->orderByDesc('enviado_em')
            ->get()
            ->keyBy('user_id');
    }
}
