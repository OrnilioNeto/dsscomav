<?php

if (! function_exists('site_asset')) {
    function site_asset(string $path = ''): string
    {
        $prefix = trim((string) config('app.asset_prefix', ''), '/');
        $cleanPath = ltrim($path, '/');

        if ($prefix !== '') {
            return asset($prefix . '/' . $cleanPath);
        }

        return asset($cleanPath);
    }
}

if (! function_exists('tenant_upload_dir')) {
    /**
     * Diretório de uploads públicos isolado por tenant.
     * Ex.: uploads/3/perfil, uploads/3/splash, uploads/3/social.
     * Sem contexto de tenant, mantém o caminho legado (uploads/perfil).
     */
    function tenant_upload_dir(string $subdir): string
    {
        $tenantId = app(\App\Support\TenantManager::class)->id();

        return $tenantId ? "uploads/{$tenantId}/{$subdir}" : "uploads/{$subdir}";
    }
}

if (! function_exists('tenant')) {
    /**
     * Tenant ativo da request (ou null sem contexto/fla desligada).
     */
    function tenant(): ?\App\Models\Tenant
    {
        return app(\App\Support\TenantManager::class)->current();
    }
}

if (! function_exists('plataforma_nome')) {
    /**
     * Nome exibido da plataforma: nome do tenant (white-label) ou padrão.
     */
    function plataforma_nome(): string
    {
        $tenant = tenant();

        return $tenant?->getNomeExibicao() ?? (string) config('app.name', 'Plataforma DSS');
    }
}

if (! function_exists('plataforma_logo')) {
    /**
     * Caminho absoluto do logo do tenant (para leitura em PDF/TCPDF),
     * ou null para o fallback legado.
     */
    function plataforma_logo(string $campo = 'logo'): ?string
    {
        return tenant()?->logoFilePath($campo);
    }
}

if (! function_exists('tenant_public_storage_dir')) {
    /**
     * Subpasta no disco 'public' (storage/app/public) isolada por tenant.
     * Ex.: tenants/3/materiais-apoio/training-5. Sem tenant, mantém o legado.
     */
    function tenant_public_storage_dir(string $relative): string
    {
        $tenantId = app(\App\Support\TenantManager::class)->id();

        return $tenantId ? "tenants/{$tenantId}/{$relative}" : $relative;
    }
}