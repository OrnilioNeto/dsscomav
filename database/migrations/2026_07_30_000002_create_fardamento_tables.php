<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos de fardamento no cadastro de colaboradores (users)
        if (Schema::hasTable('users')) {
            $colunas = [
                'camisa_tamanho' => 'string',
                'calca_tamanho' => 'string',
                'bota_numero' => 'string',
            ];

            foreach ($colunas as $coluna => $tipo) {
                if (!Schema::hasColumn('users', $coluna)) {
                    Schema::table('users', function (Blueprint $table) use ($coluna) {
                        $table->string($coluna, 20)->nullable()->after('cargo');
                    });
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['camisa_tamanho', 'calca_tamanho', 'bota_numero'] as $coluna) {
            if (Schema::hasTable('users') && Schema::hasColumn('users', $coluna)) {
                Schema::table('users', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            }
        }
    }
};
