<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->integer('avaliacao_resposta_usuario')->nullable()->after('avaliacao_tentativas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn('avaliacao_resposta_usuario');
        });
    }
};
