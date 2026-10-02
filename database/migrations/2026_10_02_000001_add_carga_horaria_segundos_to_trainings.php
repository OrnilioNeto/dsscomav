<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('trainings', 'carga_horaria_segundos')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->unsignedTinyInteger('carga_horaria_segundos')->nullable()->after('carga_horaria');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('trainings', 'carga_horaria_segundos')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropColumn('carga_horaria_segundos');
            });
        }
    }
};
