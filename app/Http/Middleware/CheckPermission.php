<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Gate de tenant: módulo não contratado = 403 (super_admin passa).
        $manager = app(TenantManager::class);
        if ($manager->isEnabled() && $manager->has() && ! $user->isSuperAdmin()) {
            if (! $manager->current()->hasModule($module)) {
                abort(403, 'Módulo não contratado para esta empresa.');
            }
        }

        if ($user->hasPermission($module, $action)) {
            return $next($request);
        }

        abort(403, 'Você não tem permissão para acessar este recurso.');
    }
}