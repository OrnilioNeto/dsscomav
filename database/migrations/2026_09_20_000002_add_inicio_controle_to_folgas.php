<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('folga_settings') && ! Schema::hasColumn('folga_settings', 'data_inicio_controle')) {
            Schema::table('folga_settings', function (Blueprint $table) {
                $table->date('data_inicio_controle')->nullable()->after('dias_para_folga')
                    ->comment('A partir desta data o banco de folgas passa a contar créditos/débitos');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'saldo_inicial_folgas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('saldo_inicial_folgas')->default(0)->after('ultima_folga_data')
                    ->comment('Saldo de folgas no início do controle');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('folga_settings') && Schema::hasColumn('folga_settings', 'data_inicio_controle')) {
            Schema::table('folga_settings', function (Blueprint $table) {
                $table->dropColumn('data_inicio_controle');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'saldo_inicial_folgas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('saldo_inicial_folgas');
            });
        }
    }
};
