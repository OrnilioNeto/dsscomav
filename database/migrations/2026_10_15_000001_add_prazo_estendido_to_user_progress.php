<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('user_progress', 'prazo_estendido_ate')) {
            Schema::table('user_progress', function (Blueprint $table) {
                // Novo prazo concedido pelo gestor para assistir/avaliar após o
                // prazo padrão (23:00 do domingo subsequente à liberação).
                $table->dateTime('prazo_estendido_ate')->nullable()->after('data_conclusao')->index();
                $table->unsignedBigInteger('prazo_estendido_por')->nullable()->after('prazo_estendido_ate');
                $table->dateTime('prazo_estendido_em')->nullable()->after('prazo_estendido_por');
                $table->string('prazo_estendido_motivo', 500)->nullable()->after('prazo_estendido_em');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('user_progress', 'prazo_estendido_ate')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->dropColumn([
                    'prazo_estendido_ate',
                    'prazo_estendido_por',
                    'prazo_estendido_em',
                    'prazo_estendido_motivo',
                ]);
            });
        }
    }
};
