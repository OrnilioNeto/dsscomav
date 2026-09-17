<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration do módulo de exemplo (referência).
 * Padrão do projeto: guarda hasTable + tenant_id nullable indexado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exemplo_registros')) {
            return;
        }

        Schema::create('exemplo_registros', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemplo_registros');
    }
};
