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
                $table->boolean('ss_e_tx_retroativo')->default(false)->after('ss_e_tx_grupo_assinatura');
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
