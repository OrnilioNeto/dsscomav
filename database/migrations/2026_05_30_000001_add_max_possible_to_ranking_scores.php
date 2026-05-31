<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxPossibleToRankingScores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ranking_scores', function (Blueprint $table) {
            if (! Schema::hasColumn('ranking_scores', 'max_possible_score')) {
                $table->double('max_possible_score')->default(0)->after('raw_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ranking_scores', function (Blueprint $table) {
            if (Schema::hasColumn('ranking_scores', 'max_possible_score')) {
                $table->dropColumn('max_possible_score');
            }
        });
    }
}
