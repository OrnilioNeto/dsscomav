<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paridade: o ranking_criteria criado em runtime (AppServiceProvider antigo)
     * não tinha a coluna is_active, que a migration original prevê.
     */
    public function up(): void
    {
        if (Schema::hasTable('ranking_criteria') && ! Schema::hasColumn('ranking_criteria', 'is_active')) {
            Schema::table('ranking_criteria', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ranking_criteria') && Schema::hasColumn('ranking_criteria', 'is_active')) {
            Schema::table('ranking_criteria', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};