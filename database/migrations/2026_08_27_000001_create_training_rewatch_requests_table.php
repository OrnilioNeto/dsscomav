<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_rewatch_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');
            $table->text('justificativa');
            $table->foreignId('authorized_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('certificate_anterior_id')->nullable()->constrained('certificates')->onDelete('set null');
            $table->foreignId('certificate_novo_id')->nullable()->constrained('certificates')->onDelete('set null');
            $table->enum('status', ['pendente', 'concluido'])->default('pendente');
            $table->timestamps();

            $table->index('user_id');
            $table->index('training_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_rewatch_requests');
    }
};
