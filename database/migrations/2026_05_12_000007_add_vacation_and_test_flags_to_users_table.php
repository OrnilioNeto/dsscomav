<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumns('users', ['ferias_inicio', 'ferias_fim', 'usuario_teste'])) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ferias_inicio')) {
                $table->date('ferias_inicio')->nullable()->after('responsavel');
            }
            if (!Schema::hasColumn('users', 'ferias_fim')) {
                $table->date('ferias_fim')->nullable()->after('ferias_inicio');
            }
            if (!Schema::hasColumn('users', 'usuario_teste')) {
                $table->boolean('usuario_teste')->default(false)->after('ferias_fim');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ferias_inicio', 'ferias_fim', 'usuario_teste']);
        });
    }
};
