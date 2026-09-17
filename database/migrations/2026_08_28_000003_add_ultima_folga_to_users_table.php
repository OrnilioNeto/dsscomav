<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ultima_folga_data')) {
                $table->date('ultima_folga_data')->nullable()->after('data_inativacao')->comment('Data da última folga registrada manualmente (usada para iniciar a contagem do streak)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ultima_folga_data');
        });
    }
};
