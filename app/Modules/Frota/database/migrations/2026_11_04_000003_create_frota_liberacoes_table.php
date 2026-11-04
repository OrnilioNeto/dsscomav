<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('frota_liberacoes_treinamento')) {
            return;
        }

        Schema::create('frota_liberacoes_treinamento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('liberado_por')->nullable();
            $table->text('motivo')->nullable();
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->dateTime('revogado_em')->nullable();
            $table->unsignedBigInteger('revogado_por')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'fim'], 'frota_liberacoes_user_fim_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frota_liberacoes_treinamento');
    }
};
