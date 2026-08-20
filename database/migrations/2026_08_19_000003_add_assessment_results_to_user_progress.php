<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('user_progress', 'avaliacao_respostas_json')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->json('avaliacao_respostas_json')->nullable()->after('avaliacao_resposta_usuario');
            });
        }

        if (!Schema::hasColumn('user_progress', 'avaliacao_nota')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->unsignedTinyInteger('avaliacao_nota')->nullable()->after('avaliacao_respostas_json');
            });
        }
    }

    public function down()
    {
        foreach (['avaliacao_respostas_json', 'avaliacao_nota'] as $coluna) {
            if (Schema::hasColumn('user_progress', $coluna)) {
                Schema::table('user_progress', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            }
        }
    }
};
