<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retenção da auditoria
    |--------------------------------------------------------------------------
    | Número de dias que os registros de audit_logs são mantidos.
    | A limpeza é executada diariamente pelo comando audit:prune.
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
