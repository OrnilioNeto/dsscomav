<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coluna de agrupamento de assinaturas (assinatura em lote) na ss_epi_entrega.
     * Antes criada apenas em runtime pelo EpiController.
     */
    public function up(): void
    {
        if (Schema::hasTable('ss_epi_entrega') && ! Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_grupo_assinatura')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->string('ss_e_tx_grupo_assinatura', 36)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ss_epi_entrega') && Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_grupo_assinatura')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->dropColumn('ss_e_tx_grupo_assinatura');
            });
        }
    }
};
