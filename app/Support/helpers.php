<?php

use App\Models\Tenant;
use App\Support\TenantManager;
use Carbon\Carbon;

if (! function_exists('site_asset')) {
    function site_asset(string $path = ''): string
    {
        $prefix = trim((string) config('app.asset_prefix', ''), '/');
        $cleanPath = ltrim($path, '/');

        if ($prefix !== '') {
            return asset($prefix.'/'.$cleanPath);
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
        $tenantId = app(TenantManager::class)->id();

        return $tenantId ? "uploads/{$tenantId}/{$subdir}" : "uploads/{$subdir}";
    }
}

if (! function_exists('tenant')) {
    /**
     * Tenant ativo da request (ou null sem contexto/fla desligada).
     */
    function tenant(): ?Tenant
    {
        return app(TenantManager::class)->current();
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
        $tenantId = app(TenantManager::class)->id();

        return $tenantId ? "tenants/{$tenantId}/{$relative}" : $relative;
    }
}

if (! function_exists('mask_cpf')) {
    /**
     * Mascara um CPF para exibição em páginas públicas (LGPD).
     */
    function mask_cpf(?string $cpf): string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($digits) !== 11) {
            return '***';
        }

        return '***.'.substr($digits, 3, 3).'.'.substr($digits, 6, 3).'-**';
    }
}

if (! function_exists('mask_email')) {
    /**
     * Mascara um e-mail para exibição em páginas públicas (LGPD).
     */
    function mask_email(?string $email): string
    {
        $email = (string) $email;
        $pos = strpos($email, '@');

        if ($pos === false || $pos === 0) {
            return '***';
        }

        $local = substr($email, 0, $pos);
        $domain = substr($email, $pos);

        return substr($local, 0, 1).str_repeat('*', max(1, strlen($local) - 1)).$domain;
    }
}

if (! function_exists('mask_phone')) {
    /**
     * Mascara um telefone para exibição em páginas públicas (LGPD).
     */
    function mask_phone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return 'Não informado';
        }

        return '(**) *****-'.substr($digits, -4);
    }
}

if (! function_exists('app_version')) {
    /**
     * Versão atual do sistema (ex.: v2.0.0).
     */
    function app_version(): string
    {
        return 'v'.config('version.version', '0.0.0');
    }
}

if (! function_exists('app_version_date')) {
    /**
     * Data da última atualização/release no formato d/m/Y.
     */
    function app_version_date(): string
    {
        $date = config('version.released_at');

        if (! $date) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (Throwable $e) {
            return (string) $date;
        }
    }
}
