<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ss_epi_variacao')) {
            Schema::create('ss_epi_variacao', function (Blueprint $table) {
                $table->id('ss_ev_nb_id');
                $table->integer('ss_ev_nb_epi_id');
                $table->string('ss_ev_tx_nome', 255);
                $table->string('ss_ev_tx_status', 30)->default('ativo');

                $table->index(['ss_ev_nb_epi_id']);
            });
        }

        if (Schema::hasTable('ss_epi_estoque') && !Schema::hasColumn('ss_epi_estoque', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_estoque', function (Blueprint $table) {
                $table->integer('ss_e_nb_variacao_id')->nullable()->after('ss_e_nb_empresa_id');
            });
        }

        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->integer('ss_e_nb_variacao_id')->nullable()->after('ss_e_nb_epi_id');
            });
        }

        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_requer_assinatura')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->boolean('ss_e_tx_requer_assinatura')->default(true)->after('ss_e_tx_status');
                $table->string('ss_e_tx_status_assinatura', 30)->default('pendente')->after('ss_e_tx_requer_assinatura');
                $table->text('ss_e_tx_justificativa_negacao')->nullable()->after('ss_e_tx_status_assinatura');
                $table->dateTime('ss_e_tx_data_assinatura')->nullable()->after('ss_e_tx_justificativa_negacao');
            });
        }
    }

    public function down(): void
    {
        $columns = ['ss_e_tx_data_assinatura', 'ss_e_tx_justificativa_negacao', 'ss_e_tx_status_assinatura', 'ss_e_tx_requer_assinatura'];
        foreach ($columns as $col) {
            if (Schema::hasColumn('ss_epi_entrega', $col)) {
                Schema::table('ss_epi_entrega', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }

        if (Schema::hasColumn('ss_epi_estoque', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_estoque', function (Blueprint $table) {
                $table->dropColumn('ss_e_nb_variacao_id');
            });
        }

        if (Schema::hasColumn('ss_epi_entrega', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->dropColumn('ss_e_nb_variacao_id');
            });
        }

        Schema::dropIfExists('ss_epi_variacao');
    }
};
