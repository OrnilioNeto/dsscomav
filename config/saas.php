<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    |
    | Com SAAS_MULTITENANT_ENABLED=false (padrão), o sistema se comporta como
    | single-tenant (comportamento atual). Ao ativar, o tenant é resolvido
    | pelo host (subdomínio) e o isolamento passa a valer.
    |
    */
    'enabled' => (bool) env('SAAS_MULTITENANT_ENABLED', false),

    /*
    | Domínio raiz da plataforma. A raiz (e www.*) atende o tenant padrão
    | (cliente atual / COMAV-DSS), preservando URLs e QR codes já emitidos.
    */
    'root_domain' => env('SAAS_ROOT_DOMAIN', ''),

    /*
    | Slug do tenant que a raiz do domínio atende.
    */
    'root_tenant_slug' => env('SAAS_ROOT_TENANT_SLUG', 'cliente'),

    /*
    | Path do painel da plataforma (ex.: https://dominio.com/plataforma).
    | Nesse path não há resolução de tenant (contexto de plataforma).
    */
    'platform_path' => env('SAAS_PLATFORM_PATH', 'plataforma'),

    /*
    | Host dedicado do painel da plataforma (opcional; ex.: admin.dominio.com).
    | Quando vazio, apenas o path acima define o contexto de plataforma.
    */
    'platform_host' => env('SAAS_PLATFORM_HOST', ''),
];
