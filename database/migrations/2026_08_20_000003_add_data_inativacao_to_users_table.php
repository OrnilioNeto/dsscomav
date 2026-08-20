<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'data_inativacao')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('data_inativacao')->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'data_inativacao')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('data_inativacao');
            });
        }
    }
};