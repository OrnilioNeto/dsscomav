<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_progress')) {
            return;
        }

        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');
            $table->integer('tempo_assistido')->default(0); // em segundos
            $table->integer('porcentagem_assistida')->default(0);
            $table->boolean('avaliacao_aprovada')->default(false);
            $table->boolean('concluido')->default(false);
            $table->timestamp('data_conclusao')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'training_id']);
            $table->index('concluido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
