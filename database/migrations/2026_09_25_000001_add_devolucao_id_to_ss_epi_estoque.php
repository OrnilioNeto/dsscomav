<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ss_epi_estoque')) {
            return;
        }

        if (! Schema::hasColumn('ss_epi_estoque', 'ss_e_nb_devolucao_id')) {
            Schema::table('ss_epi_estoque', function (Blueprint $table) {
                $table->unsignedBigInteger('ss_e_nb_devolucao_id')->nullable()->after('ss_e_nb_variacao_id');
                $table->index('ss_e_nb_devolucao_id');
            });
        }

        // Backfill: vincula movimentações de devolução já existentes pelo texto do motivo
        // ("Devolução #ID ..." ou "Aprovado na inspeção (devolução #ID) ...").
        DB::table('ss_epi_estoque')
            ->where('ss_e_tx_tipo', 'devolucao')
            ->whereNull('ss_e_nb_devolucao_id')
            ->whereNotNull('ss_e_tx_motivo')
            ->chunkById(200, function ($movimentos) {
                foreach ($movimentos as $movimento) {
                    if (preg_match('/devolu[çc][ãa]o\s*#(\d+)/iu', (string) $movimento->ss_e_tx_motivo, $matches)) {
                        DB::table('ss_epi_estoque')
                            ->where('ss_e_nb_id', $movimento->ss_e_nb_id)
                            ->update(['ss_e_nb_devolucao_id' => (int) $matches[1]]);
                    }
                }
            }, 'ss_e_nb_id');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ss_epi_estoque') || ! Schema::hasColumn('ss_epi_estoque', 'ss_e_nb_devolucao_id')) {
            return;
        }

        Schema::table('ss_epi_estoque', function (Blueprint $table) {
            $table->dropIndex(['ss_e_nb_devolucao_id']);
            $table->dropColumn('ss_e_nb_devolucao_id');
        });
    }
};
