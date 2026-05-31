<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('training_materials')) {
            return;
        }

        Schema::create('training_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_id');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('arquivo'); // Caminho do arquivo armazenado
            $table->string('tipo_arquivo'); // pdf, imagem, etc.
            $table->unsignedBigInteger('tamanho'); // Tamanho em bytes
            $table->integer('ordem')->default(0); // Para ordenação
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->index('training_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_materials');
    }
};
