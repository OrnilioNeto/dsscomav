<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiebreakerAndPositionToRankingMonthlyScores extends Migration
{
    public function up()
    {
        Schema::table('ranking_monthly_scores', function (Blueprint $table) {
            // Valor de desempate: média do timestamp UNIX de data_inicio nos conteúdos do período.
            // Menor valor = iniciou mais cedo = melhor posição no empate.
            $table->double('tiebreaker_value')->nullable()->after('average_score');
        });
    }

    public function down()
    {
        Schema::table('ranking_monthly_scores', function (Blueprint $table) {
            $table->dropColumn('tiebreaker_value');
        });
    }
}
