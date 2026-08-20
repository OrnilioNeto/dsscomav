<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('training_projetos_pedagogicos')) {
            Schema::create('training_projetos_pedagogicos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('training_id')->unique();
                $table->string('versao', 20)->default('1.0');

                // Itens obrigatórios do Anexo II 3.1 da NR-01
                $table->text('objetivo_geral')->nullable();
                $table->text('principios_sst')->nullable();
                $table->text('estrategia_pedagogica')->nullable();
                $table->text('conteudo_programatico_pp')->nullable();
                $table->text('objetivo_modulos')->nullable();
                $table->string('carga_horaria_pp', 100)->nullable();
                $table->string('tempo_minimo_diario', 100)->nullable();
                $table->string('prazo_maximo_conclusao', 100)->nullable();
                $table->text('publico_alvo')->nullable();
                $table->text('material_didatico')->nullable();
                $table->text('instrumentos_aprendizado')->nullable();
                $table->text('avaliacao_aprendizagem')->nullable();
                $table->string('instrutores', 255)->nullable();
                $table->text('infraestrutura_operacional')->nullable();

                // Responsável técnico pela capacitação / instrutores
                $table->string('responsavel_tecnico_nome', 255)->nullable();
                $table->string('responsavel_tecnico_qualificacao', 255)->nullable();

                // Validação / revisão (Anexo II 3.3)
                $table->date('data_validacao')->nullable();
                $table->date('data_proxima_revisao')->nullable();

                // Documento assinado (upload opcional)
                $table->string('arquivo_pdf', 255)->nullable();

                $table->timestamps();

                $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('training_projetos_pedagogicos')) {
            Schema::dropIfExists('training_projetos_pedagogicos');
        }
    }
};