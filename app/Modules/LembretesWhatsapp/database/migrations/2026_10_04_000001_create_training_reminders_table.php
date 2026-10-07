<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('telefone', 30)->nullable();
            $table->string('jid')->nullable();
            $table->text('mensagem');
            // fila | enviado | falhou | pulado | cancelado
            $table->string('status', 20)->default('fila');
            $table->timestamp('agendado_para')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->string('gateway_message_id')->nullable();
            $table->text('erro')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['status', 'agendado_para']);
            $table->index(['user_id', 'training_id']);
            $table->index('enviado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_reminders');
    }
};
