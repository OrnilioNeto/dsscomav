<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lembretes de treinamento via WhatsApp (gateway WA-AKG)
    |--------------------------------------------------------------------------
    |
    | Integração com o gateway WA-AKG (Baileys). O DSS enfileira um lembrete
    | por colaborador pendente e o comando `lembretes:processar` envia um a um,
    | com intervalos aleatórios, respeitando janela de horário e teto diário
    | para reduzir o risco de bloqueio do número.
    |
    */

    // Liga/desliga o disparo (as telas continuam visíveis, mas o envio é bloqueado).
    'enabled' => env('WA_REMINDERS_ENABLED', false),

    // URL base do gateway (ex.: http://localhost:3030 ou https://zap.suaempresa.com).
    'gateway_url' => env('WA_GATEWAY_URL', 'http://localhost:3030'),

    // API key do WA-AKG (header X-API-Key; gerada no dashboard do gateway).
    'api_key' => env('WA_GATEWAY_API_KEY', ''),

    // sessionId da sessão conectada no WA-AKG.
    'session_id' => env('WA_GATEWAY_SESSION', 'dss'),

    // Timeout (segundos) das chamadas HTTP ao gateway.
    'timeout' => (int) env('WA_GATEWAY_TIMEOUT', 20),

    // Teto de mensagens enviadas por dia (por número/sessão).
    'daily_cap' => (int) env('WA_DAILY_CAP', 40),

    // Máximo de colaboradores por disparo manual (proteção anti-ban).
    'batch_limit' => (int) env('WA_BATCH_LIMIT', 30),

    // Intervalo aleatório entre mensagens, em segundos. O processador roda a
    // cada 5 minutos (app/Console/Kernel.php), então este é o piso do
    // espaçamento: o envio real ocorre na próxima execução do comando.
    'delay_min' => (int) env('WA_DELAY_MIN', 300),
    'delay_max' => (int) env('WA_DELAY_MAX', 600),

    // Janela de envio (horário de Brasília, conforme app.timezone).
    'window_start' => env('WA_WINDOW_START', '08:00'),
    'window_end' => env('WA_WINDOW_END', '18:00'),
    'allow_weekends' => env('WA_ALLOW_WEEKENDS', false),

    // Pausa automática: após N falhas consecutivas o processamento para.
    'max_consecutive_failures' => (int) env('WA_MAX_CONSECUTIVE_FAILURES', 5),

    // Segredo HMAC para validar o webhook de status de entrega do WA-AKG.
    'webhook_secret' => env('WA_WEBHOOK_SECRET', ''),

    // Texto padrão do lembrete. Placeholders: {nome} e {treinamento}.
    'default_message' => env(
        'WA_DEFAULT_MESSAGE',
        'Olá, {nome}! Identificamos que você ainda não finalizou o treinamento "{treinamento}". '
        .'Por favor, acesse a plataforma e conclua o quanto antes. Em caso de dúvidas, fale com seu gestor.'
    ),
];
