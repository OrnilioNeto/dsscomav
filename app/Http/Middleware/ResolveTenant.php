<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolve o tenant da request pelo host (web e API).
 *
 * - Path/host da plataforma => contexto de plataforma (sem tenant).
 * - Raiz do domínio => tenant padrão (cliente atual).
 * - Subdomínio => tenant pelo slug.
 * - Host desconhecido => 404.
 */
class ResolveTenant
{
    public function __construct(protected TenantManager $tenants) {}

    public function handle(Request $request, Closure $next)
    {
        $manager = $this->tenants;

        if (! $manager->isEnabled()) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $platformPath = (string) config('saas.platform_path', 'plataforma');
        $platformHost = strtolower(trim((string) config('saas.platform_host', '')));

        $isPlatform = ($platformHost !== '' && $host === $platformHost)
            || $request->is($platformPath.'*');

        if ($isPlatform) {
            $manager->set(null);

            return $next($request);
        }

        $tenant = $manager->resolveFromHost($host);
        if (! $tenant) {
            abort(404, 'Empresa não encontrada.');
        }

        $manager->set($tenant);

        return $next($request);
    }
}
