<?php

namespace App\Auth;

use App\Models\Scopes\TenantScope;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Provider de usuários ciente de multi-tenancy.
 *
 * A resolução da sessão (retrieveById) ignora o TenantScope: o isolamento
 * do cookie de sessão é por domínio (host-only), então um usuário de um
 * tenant não recebe sessão de outro. Isso também permite que usuários da
 * plataforma (super_admin, tenant_id NULL) usem qualquer host.
 */
class TenantAwareUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->withoutGlobalScope(TenantScope::class)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }
}
