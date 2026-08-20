<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('training_projetos_pedagogicos', 'assinatura_rt')) {
            Schema::table('training_projetos_pedagogicos', function (Blueprint $table) {
                $table->longText('assinatura_rt')->nullable();
                $table->string('assinatura_rt_nome', 255)->nullable();
                $table->dateTime('assinatura_rt_data')->nullable();
            });
        }
    }

    public function down()
    {
        foreach (['assinatura_rt', 'assinatura_rt_nome', 'assinatura_rt_data'] as $coluna) {
            if (Schema::hasColumn('training_projetos_pedagogicos', $coluna)) {
                Schema::table('training_projetos_pedagogicos', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            }
        }
    }
};