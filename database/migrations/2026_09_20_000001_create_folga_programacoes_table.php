<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('folga_programacoes')) {
            return;
        }

        Schema::create('folga_programacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->enum('tipo', ['folga', 'atestado', 'licenca'])->default('folga');
            $table->string('motivo')->nullable();
            $table->text('observacao')->nullable();
            $table->enum('status', ['ativa', 'cancelada'])->default('ativa');
            $table->date('cancelada_em')->nullable()->comment('Dias anteriores a esta data continuam valendo como folga');
            $table->unsignedBigInteger('cancelada_por')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['data_inicio', 'data_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folga_programacoes');
    }
};
