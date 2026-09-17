<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\TenantManager;
use Illuminate\Support\Facades\Schema;

/**
 * Trait multi-tenant para models.
 *
 * - Adiciona o TenantScope (filtro automático por tenant_id).
 * - Preenche tenant_id automaticamente na criação quando há contexto de tenant.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $manager = app(TenantManager::class);

            if (! $manager->isEnabled() || ! $manager->has()) {
                return;
            }

            if (empty($model->getAttribute('tenant_id')) && $model->isTenantScoped()) {
                $model->tenant_id = $manager->id();
            }
        });
    }

    protected function isTenantScoped(): bool
    {
        return Schema::hasColumn($this->getTable(), 'tenant_id');
    }
}
