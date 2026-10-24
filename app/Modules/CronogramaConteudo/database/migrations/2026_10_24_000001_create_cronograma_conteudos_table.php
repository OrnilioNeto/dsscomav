<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_conteudos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('nome');
            $table->date('data_prevista_liberacao')->nullable();
            $table->foreignId('training_id')->nullable()->constrained('trainings')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('nome');
            $table->index('data_prevista_liberacao');
            $table->index('training_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_conteudos');
    }
};