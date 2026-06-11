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
        Schema::table('ranking_monthly_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('ranking_monthly_scores', 'total_raw_score')) {
                $table->double('total_raw_score')->default(0)->after('average_score');
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
        Schema::table('ranking_monthly_scores', function (Blueprint $table) {
            if (Schema::hasColumn('ranking_monthly_scores', 'total_raw_score')) {
                $table->dropColumn('total_raw_score');
            }
        });
    }
};