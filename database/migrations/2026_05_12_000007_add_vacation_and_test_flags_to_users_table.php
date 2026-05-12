<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('ferias_inicio')->nullable()->after('responsavel');
            $table->date('ferias_fim')->nullable()->after('ferias_inicio');
            $table->boolean('usuario_teste')->default(false)->after('ferias_fim');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ferias_inicio', 'ferias_fim', 'usuario_teste']);
        });
    }
};
