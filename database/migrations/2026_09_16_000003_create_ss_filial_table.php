<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filiais do módulo EPI. Antes criada apenas em runtime pelo EpiController.
     * Filial é sub-unidade DENTRO de um tenant (não confundir com tenant/cliente).
     */
    public function up(): void
    {
        if (! Schema::hasTable('ss_filial')) {
            Schema::create('ss_filial', function (Blueprint $table) {
                $table->id('ss_f_nb_id');
                $table->string('ss_f_tx_nome', 255);
                $table->string('ss_f_tx_codigo', 50)->nullable();
                $table->string('ss_f_tx_cidade', 255)->nullable();
                $table->string('ss_f_tx_status', 30)->default('ativo');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_filial');
    }
};
