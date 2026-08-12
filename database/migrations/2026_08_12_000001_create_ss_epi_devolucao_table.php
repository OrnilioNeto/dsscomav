<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ss_epi_devolucao')) {
            return;
        }

        Schema::create('ss_epi_devolucao', function (Blueprint $table) {
            $table->id('ss_ed_nb_id');
            $table->unsignedBigInteger('ss_ed_nb_entrega_id')->nullable();
            $table->unsignedBigInteger('ss_ed_nb_epi_id');
            $table->unsignedBigInteger('ss_ed_nb_colaborador_id')->nullable();
            $table->unsignedBigInteger('ss_ed_nb_empresa_id')->nullable();
            $table->unsignedBigInteger('ss_ed_nb_variacao_id')->nullable();
            $table->unsignedInteger('ss_ed_nb_quantidade')->default(1);
            $table->string('ss_ed_tx_motivo', 50);
            $table->string('ss_ed_tx_destino', 20)->default('descarte');
            $table->string('ss_ed_tx_status', 20)->default('concluida');
            $table->string('ss_ed_tx_resultado_inspecao', 20)->nullable();
            $table->text('ss_ed_tx_observacao')->nullable();
            $table->unsignedBigInteger('ss_ed_nb_userRegistro')->nullable();
            $table->dateTime('ss_ed_tx_data_registro')->nullable();
            $table->unsignedBigInteger('ss_ed_nb_userDecisao')->nullable();
            $table->dateTime('ss_ed_tx_data_decisao')->nullable();

            $table->index(['ss_ed_nb_entrega_id']);
            $table->index(['ss_ed_nb_epi_id']);
            $table->index(['ss_ed_tx_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_epi_devolucao');
    }
};
