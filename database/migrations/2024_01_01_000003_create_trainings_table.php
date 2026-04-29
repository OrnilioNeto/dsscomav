<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['dss', 'treinamento'])->default('treinamento');
            $table->json('tipo_usuario_permitido')->default('["motorista", "funcionario", "terceirizado"]');
            $table->text('url_video');
            $table->enum('tipo_video', ['youtube', 'vimeo', 'upload'])->default('youtube');
            $table->integer('carga_horaria')->default(1); // em minutos
            $table->string('thumbnail')->nullable();
            $table->timestamp('data_publicacao')->nullable();
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->boolean('obrigatorio')->default(false);
            $table->text('avaliacao_pergunta')->nullable();
            $table->json('avaliacao_opcoes')->nullable();
            $table->unsignedTinyInteger('avaliacao_resposta_correta')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('status');
            $table->index('tipo_video');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
