<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_retroativo')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                // ss_e_tx_grupo_assinatura pode ainda não existir (antes da migration
                // 2026_09_16_000002_add_grupo_assinatura_to_ss_epi_entrega); sem after() é seguro.
                $table->boolean('ss_e_tx_retroativo')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ss_epi_entrega') && Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_retroativo')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->dropColumn('ss_e_tx_retroativo');
            });
        }
    }
};
