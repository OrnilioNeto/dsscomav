<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_progress', 'avaliacao_tentativas')) {
            return;
        }

        Schema::table('user_progress', function (Blueprint $table) {
            $table->unsignedTinyInteger('avaliacao_tentativas')->default(0)->after('avaliacao_aprovada');
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn('avaliacao_tentativas');
        });
    }
};