<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Token de API ciente de multi-tenancy.
 *
 * A resolução do usuário (tokenable) ignora o TenantScope, pelo mesmo motivo
 * do TenantAwareUserProvider na sessão web: o token é secreto e por usuário, e
 * usuários da plataforma (super_admin, tenant_id NULL) precisam autenticar em
 * qualquer host. O isolamento entre tenants é garantido depois, na validação
 * do token (ver AppServiceProvider::boot).
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function tokenable()
    {
        return $this->morphTo('tokenable')->withoutGlobalScopes([TenantScope::class]);
    }
}
