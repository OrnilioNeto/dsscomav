<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Isenção de treinamentos por afastamento de semana completa
    |--------------------------------------------------------------------------
    | Data de corte para a regra que isenta o conteúdo quando o colaborador
    | tem folga/atestado/licença cobrindo a semana inteira (segunda a domingo)
    | da liberação do treinamento. Lançamentos anteriores a esta data não
    | entram na regra, para não alterar o histórico já consolidado.
    |
    | Formato: Y-m-d. Use null para considerar todo o histórico.
    */
    'isencao_treinamento_desde' => env('FOLGA_ISENCAO_TREINAMENTO_DESDE', '2026-11-19'),
];
