<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');
            $table->string('codigo_certificado')->unique();
            $table->timestamp('data_emissao');
            $table->string('caminho_arquivo')->nullable();
            $table->boolean('valido')->default(true);
            $table->timestamps();

            $table->index('codigo_certificado');
            $table->index('user_id');
            $table->index('training_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
