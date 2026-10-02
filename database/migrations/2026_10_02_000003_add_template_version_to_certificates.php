<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versão do layout do certificado.
     *
     * - NULL/1: certificados emitidos antes do modelo único (layout legado,
     *   renderizado exatamente como sempre foi — nada muda para eles).
     * - 2+: modelo profissional único, usado por todos os certificados novos.
     */
    public function up(): void
    {
        if (Schema::hasTable('certificates') && ! Schema::hasColumn('certificates', 'template_version')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->unsignedTinyInteger('template_version')->nullable()->after('porcentagem_assistida');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificates') && Schema::hasColumn('certificates', 'template_version')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->dropColumn('template_version');
            });
        }
    }
};
