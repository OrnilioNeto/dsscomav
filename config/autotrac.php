<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integração Autotrac / ATIC API
    |--------------------------------------------------------------------------
    |
    | Fallback por ambiente. Quando a tela de configuração do módulo Frota
    | estiver preenchida (tabela frota_configuracoes), os valores do banco
    | têm prioridade — o .env serve para subir rápido em single-tenant.
    |
    */

    'base_url' => env('AUTOTRAC_BASE_URL', 'https://aapi3.autotrac-online.com.br/aticapi'),

    'account_code' => env('AUTOTRAC_ACCOUNT_CODE'),

    'subscription_key' => env('AUTOTRAC_SUBSCRIPTION_KEY'),

    'username' => env('AUTOTRAC_USERNAME'),

    'password' => env('AUTOTRAC_PASSWORD'),

    'enabled' => filter_var(env('AUTOTRAC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Chave geral da regra de acesso (kill switch)
    |--------------------------------------------------------------------------
    |
    | false = a verificação de jornada está DESATIVADA: ninguém é bloqueado,
    | nenhuma chamada é feita no caminho da request e o sync agendado não roda.
    | true  = a regra volta a valer conforme a configuração do tenant.
    |
    | Para reativar: AUTOTRAC_GATE_ENABLED=true no .env + reiniciar a aplicação.
    |
    */
    'gate_enabled' => filter_var(env('AUTOTRAC_GATE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Janela de tolerância quando a API está indisponível: mantém o último
    | status "online" conhecido por este período antes de bloquear.
    */
    'grace_minutes' => (int) env('AUTOTRAC_GRACE_MINUTES', 30),

    /*
    | Tempo máximo de sessão aberta no rastreador (horas). Acima disso o
    | acesso aos treinamentos é bloqueado mesmo com o motorista "online".
    */
    'max_session_hours' => (int) env('AUTOTRAC_MAX_SESSION_HOURS', 12),

    /*
    | Tempo de cache do status de presença consultado sob demanda.
    */
    'presence_ttl' => (int) env('AUTOTRAC_PRESENCE_TTL', 60),

    /*
    | Tipos de usuário sujeitos à regra de jornada.
    */
    'tipos_motorista' => ['motorista', 'motorista_monitor'],

    /*
    | Comportamento em falha de integração: "grace" (padrão), "closed" ou "open".
    */
    'fail_mode' => env('AUTOTRAC_FAIL_MODE', 'grace'),

    'timeout' => (int) env('AUTOTRAC_TIMEOUT', 15),

    'connect_timeout' => (int) env('AUTOTRAC_CONNECT_TIMEOUT', 5),
];
