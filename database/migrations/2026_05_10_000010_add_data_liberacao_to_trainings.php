<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('trainings', 'data_liberacao')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dateTime('data_liberacao')->nullable()->after('data_publicacao');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('trainings', 'data_liberacao')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropColumn('data_liberacao');
            });
        }
    }
};
