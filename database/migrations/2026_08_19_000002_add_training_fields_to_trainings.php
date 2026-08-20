<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('trainings', 'conteudo_programatico')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->text('conteudo_programatico')->nullable()->after('descricao');
            });
        }

        if (!Schema::hasColumn('trainings', 'tipo_treinamento')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->string('tipo_treinamento', 20)->nullable()->after('tipo');
            });
        }

        if (!Schema::hasColumn('trainings', 'dias_validade')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->unsignedInteger('dias_validade')->nullable()->after('carga_horaria');
            });
        }

        if (!Schema::hasColumn('trainings', 'quantidade_questoes_prova')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->unsignedTinyInteger('quantidade_questoes_prova')->nullable()->after('avaliacao_resposta_correta');
            });
        }

        if (!Schema::hasColumn('trainings', 'nota_minima_aprovacao')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->unsignedTinyInteger('nota_minima_aprovacao')->default(70)->after('quantidade_questoes_prova');
            });
        }
    }

    public function down()
    {
        foreach (['conteudo_programatico', 'tipo_treinamento', 'dias_validade', 'quantidade_questoes_prova', 'nota_minima_aprovacao'] as $coluna) {
            if (Schema::hasColumn('trainings', $coluna)) {
                Schema::table('trainings', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            }
        }
    }
};
