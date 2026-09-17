<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de saldo do banco de folgas de domingo
        if (! Schema::hasTable('folga_domingo_saldos')) {
            Schema::create('folga_domingo_saldos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedTinyInteger('mes');
                $table->unsignedSmallInteger('ano');
                $table->integer('creditos_ganhos')->default(0)->comment('Domingos sem folga no mês (+1 cada)');
                $table->integer('creditos_usados')->default(0)->comment('Folgas de domingo tiradas no mês');
                $table->integer('saldo_mes')->default(0)->comment('Saldo do mês: ganhos - usados');
                $table->integer('saldo_acumulado')->default(0)->comment('Saldo acumulado de domingos');
                $table->timestamps();

                $table->unique(['user_id', 'mes', 'ano']);
            });
        }

        // Coluna domingo_ref em folga_dias para indicar a qual domingo a folga se refere
        if (Schema::hasTable('folga_dias') && ! Schema::hasColumn('folga_dias', 'domingo_ref')) {
            Schema::table('folga_dias', function (Blueprint $table) {
                $table->date('domingo_ref')->nullable()->after('motivo_folga')->comment('Data do domingo ao qual esta folga se refere');
            });
        }
    }

    public function down(): void
    {
        Schema::table('folga_dias', function (Blueprint $table) {
            $table->dropColumn('domingo_ref');
        });
        Schema::dropIfExists('folga_domingo_saldos');
    }
};
