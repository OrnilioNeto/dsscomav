<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('certificates', 'foi_reassistido')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->boolean('foi_reassistido')->default(false)->after('porcentagem_assistida');
            });
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('foi_reassistido');
        });
    }
};
