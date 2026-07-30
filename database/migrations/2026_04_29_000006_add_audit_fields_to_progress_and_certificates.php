<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_progress', 'data_inicio')) {
            Schema::table('user_progress', function (Blueprint $table) {
                $table->timestamp('data_inicio')->nullable()->after('training_id');
            });
        }

        if (!Schema::hasColumn('certificates', 'data_inicio_assistencia')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->timestamp('data_inicio_assistencia')->nullable()->after('data_emissao');
                $table->timestamp('data_finalizacao_assistencia')->nullable()->after('data_inicio_assistencia');
                $table->integer('tempo_assistido_segundos')->default(0)->after('data_finalizacao_assistencia');
                $table->integer('porcentagem_assistida')->default(0)->after('tempo_assistido_segundos');
            });
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'data_inicio_assistencia',
                'data_finalizacao_assistencia',
                'tempo_assistido_segundos',
                'porcentagem_assistida',
            ]);
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn('data_inicio');
        });
    }
};