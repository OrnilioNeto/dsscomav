<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Limpeza do módulo de exemplo removido do repositório (não vai a produção).
 * Em bancos que nunca tiveram o módulo, é um no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exemplo_registros')) {
            Schema::drop('exemplo_registros');
        }
    }

    public function down(): void
    {
        // Sem rollback: a tabela pertencia a um módulo de referência removido.
    }
};
