<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos de branding/instrutor para white-label por tenant.
     */
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            $columns = [
                'instrutor_nome' => 'string',
                'instrutor_qualificacao' => 'string',
                'instrutor_rg' => 'string',
            ];

            foreach ($columns as $coluna => $tipo) {
                if (! Schema::hasColumn('tenants', $coluna)) {
                    Schema::table('tenants', function (Blueprint $table) use ($coluna, $tipo) {
                        $table->{$tipo}($coluna)->nullable();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants')) {
            foreach (['instrutor_nome', 'instrutor_qualificacao', 'instrutor_rg'] as $coluna) {
                if (Schema::hasColumn('tenants', $coluna)) {
                    Schema::table('tenants', function (Blueprint $table) use ($coluna) {
                        $table->dropColumn($coluna);
                    });
                }
            }
        }
    }
};