<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite ligar/desligar o controle de folgas dos motoristas monitores.
     */
    public function up(): void
    {
        if (! Schema::hasTable('folga_settings') || Schema::hasColumn('folga_settings', 'incluir_motorista_monitor')) {
            return;
        }

        Schema::table('folga_settings', function (Blueprint $table) {
            $table->boolean('incluir_motorista_monitor')
                ->default(true)
                ->after('bloquear_sem_domingo')
                ->comment('Incluir motoristas monitores no controle de folgas');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('folga_settings') || ! Schema::hasColumn('folga_settings', 'incluir_motorista_monitor')) {
            return;
        }

        Schema::table('folga_settings', function (Blueprint $table) {
            $table->dropColumn('incluir_motorista_monitor');
        });
    }
};
