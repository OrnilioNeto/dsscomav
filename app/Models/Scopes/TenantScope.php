<?php

namespace App\Models\Scopes;

use App\Models\Role;
use App\Models\RolePermission;
use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Escopo global de tenant: com o multi-tenancy ativo e um tenant resolvido,
 * toda query Eloquent é filtrada pela coluna tenant_id do modelo.
 *
 * Usuários da plataforma (tenant_id NULL, ex.: super_admin) não são filtrados.
 * A checagem usa apenas o atributo tenant_id para não disparar consultas de
 * relacionamento dentro do próprio escopo (evita recursão).
 *
 * Role e RolePermission são especiais: além das linhas do tenant, as linhas
 * de sistema (tenant_id NULL) são sempre visíveis (ex.: perfis admin/usuario).
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(TenantManager::class);

        if (! $manager->isEnabled() || ! $manager->has()) {
            return;
        }

        if (Auth::hasUser() && Auth::user()->getAttribute('tenant_id') === null) {
            return;
        }

        $table = $model->getTable();

        if ($model instanceof Role || $model instanceof RolePermission) {
            $builder->where(function ($q) use ($table, $manager) {
                $q->where($table.'.tenant_id', $manager->id())
                  ->orWhereNull($table.'.tenant_id');
            });

            return;
        }

        $builder->where($table.'.tenant_id', $manager->id());
    }
}
