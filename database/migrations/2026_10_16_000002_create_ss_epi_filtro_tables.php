<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ss_epi_filtro_tipo')) {
            Schema::create('ss_epi_filtro_tipo', function (Blueprint $table) {
                $table->id('ss_eft_nb_id');
                $table->string('ss_eft_tx_nome', 150);
                $table->string('ss_eft_tx_fabricante', 150)->nullable();
                $table->string('ss_eft_tx_ca', 50)->nullable();
                $table->unsignedInteger('ss_eft_nb_vida_util_dias')->default(0);
                $table->string('ss_eft_tx_status', 20)->default('ativo');
                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->index(['ss_eft_tx_status']);
                $table->index(['tenant_id']);
            });
        }

        if (! Schema::hasTable('ss_epi_filtro')) {
            Schema::create('ss_epi_filtro', function (Blueprint $table) {
                $table->id('ss_ef_nb_id');
                $table->unsignedBigInteger('ss_ef_nb_entrega_id');
                $table->unsignedBigInteger('ss_ef_nb_colaborador_id');
                $table->unsignedBigInteger('ss_ef_nb_epi_id');
                $table->unsignedBigInteger('ss_ef_nb_tipo_id')->nullable();
                $table->string('ss_ef_tx_tipo_nome', 150);
                $table->string('ss_ef_tx_ca', 50)->nullable();
                $table->unsignedInteger('ss_ef_nb_vida_util_dias')->default(0);
                $table->unsignedInteger('ss_ef_nb_quantidade')->default(2);
                $table->date('ss_ef_tx_data_instalacao')->nullable();
                $table->date('ss_ef_tx_vencimento');
                $table->string('ss_ef_tx_status', 20)->default('ativo');
                $table->string('ss_ef_tx_motivo_descarte', 50)->nullable();
                $table->date('ss_ef_tx_data_descarte')->nullable();
                $table->text('ss_ef_tx_observacao')->nullable();
                $table->longText('ss_ef_tx_assinatura')->nullable();
                $table->boolean('ss_ef_tx_requer_assinatura')->default(true);
                $table->string('ss_ef_tx_status_assinatura', 30)->default('pendente');
                $table->text('ss_ef_tx_justificativa_negacao')->nullable();
                $table->dateTime('ss_ef_tx_data_assinatura')->nullable();
                $table->string('ss_ef_tx_grupo_assinatura', 36)->nullable();
                $table->unsignedBigInteger('ss_ef_nb_userCadastro')->nullable();
                $table->dateTime('ss_ef_tx_dataCadastro')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();

                $table->index(['ss_ef_nb_entrega_id']);
                $table->index(['ss_ef_nb_colaborador_id']);
                $table->index(['ss_ef_tx_status']);
                $table->index(['ss_ef_tx_vencimento']);
                $table->index(['tenant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_epi_filtro');
        Schema::dropIfExists('ss_epi_filtro_tipo');
    }
};
