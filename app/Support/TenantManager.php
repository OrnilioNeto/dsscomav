<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Singleton do tenant ativo na request.
 *
 * - `isEnabled()`: multi-tenancy ligado via config/saas.php (SAAS_MULTITENANT_ENABLED).
 * - `set()`/`clear()`: definido pelo middleware ResolveTenant.
 * - `resolveFromHost()`: resolve o tenant pelo host da request (subdomínio,
 *   domínio próprio ou raiz = tenant padrão).
 */
class TenantManager
{
    protected ?Tenant $tenant = null;

    public function isEnabled(): bool
    {
        return (bool) config('saas.enabled', false);
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    /**
     * Executa um callback para cada tenant ativo (quando o multi-tenancy está
     * ligado). Com a flag desligada, executa uma única vez sem contexto de
     * tenant (comportamento legado single-tenant).
     */
    public function runForEachTenant(callable $callback): void
    {
        if (! $this->isEnabled()) {
            $this->set(null);
            $callback(null);

            return;
        }

        foreach (Tenant::whereIn('status', ['ativo', 'trial'])->orderBy('id')->get() as $tenant) {
            $this->set($tenant);
            $callback($tenant);
        }

        $this->clear();
    }

    public function resolveFromHost(string $host): ?Tenant
    {
        $host = strtolower(trim($host));

        // 1. Domínio próprio de um tenant (white-label)
        $tenant = Tenant::where('dominio', $host)->first();
        if ($tenant) {
            return $this->ensureActive($tenant);
        }

        // 2. Raiz da plataforma (e www.*) => tenant padrão
        $root = strtolower(trim((string) config('saas.root_domain', '')));
        if ($root !== '' && ($host === $root || str_starts_with($host, 'www.'.$root) || str_starts_with($host, 'www.'))) {
            return $this->ensureActive(Tenant::where('slug', config('saas.root_tenant_slug', 'cliente'))->first());
        }

        // 2.5 Localhost/loopback (dev): atende o tenant padrão
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $this->ensureActive(Tenant::where('slug', config('saas.root_tenant_slug', 'cliente'))->first());
        }

        // 3. Subdomínio: cliente.dominio.com
        $label = explode('.', $host)[0] ?? '';
        if ($label !== '' && $label !== 'www') {
            return $this->ensureActive(Tenant::where('slug', $label)->first());
        }

        return null;
    }

    protected function ensureActive(?Tenant $tenant): ?Tenant
    {
        if ($tenant === null || ! $tenant->isActive()) {
            return null;
        }

        return $tenant;
    }
}
