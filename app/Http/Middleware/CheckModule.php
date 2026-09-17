<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;

/**
 * Gate de módulo por tenant (tenant_modules).
 *
 * - Super admins (usuários da plataforma) sempre passam.
 * - Com a flag SAAS_MULTITENANT_ENABLED desligada, módulos sempre liberados.
 * - A permissão por perfil continua sendo feita pelo middleware `permission:`.
 */
class CheckModule
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $manager = app(TenantManager::class);

        if (! $manager->isEnabled() || ! $manager->has()) {
            return $next($request);
        }

        if (! $manager->current()->hasModule($module)) {
            abort(403, 'Módulo não contratado para esta empresa.');
        }

        return $next($request);
    }
}