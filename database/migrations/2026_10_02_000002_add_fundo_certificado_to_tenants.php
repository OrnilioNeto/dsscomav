<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imagem de fundo (base) do certificado, configurável pelo super admin.
     */
    public function up(): void
    {
        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'fundo_certificado')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('fundo_certificado')->nullable()->after('logo_certificado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'fundo_certificado')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('fundo_certificado');
            });
        }
    }
};
