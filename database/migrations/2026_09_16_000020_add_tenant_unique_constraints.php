<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uniques compostos com tenant_id.
     *
     * - users.cpf / users.email: globais -> por tenant
     * - roles.nome: global -> por tenant (roles de sistema ficam com tenant_id NULL,
     *   e o MySQL/MariaDB permite múltiplos NULLs em unique index)
     * - ranking_criteria.slug: global -> por tenant
     *
     * qrcode_token (users) e codigo_certificado (certificates) permanecem globais
     * porque são usados em lookups públicos (ficha QR / validação de certificado).
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            if (Schema::hasIndex('users', ['cpf'])) {
                Schema::table('users', function (Blueprint $t) {
                    $t->dropUnique(['cpf']);
                });
            }
            if (Schema::hasIndex('users', ['email'])) {
                Schema::table('users', function (Blueprint $t) {
                    $t->dropUnique(['email']);
                });
            }
            if (! Schema::hasIndex('users', ['tenant_id', 'cpf'])) {
                Schema::table('users', function (Blueprint $t) {
                    $t->unique(['tenant_id', 'cpf'], 'users_tenant_cpf_unique');
                });
            }
            if (! Schema::hasIndex('users', ['tenant_id', 'email'])) {
                Schema::table('users', function (Blueprint $t) {
                    $t->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
                });
            }
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'tenant_id')) {
            if (Schema::hasIndex('roles', ['nome'])) {
                Schema::table('roles', function (Blueprint $t) {
                    $t->dropUnique(['nome']);
                });
            }
            if (! Schema::hasIndex('roles', ['tenant_id', 'nome'])) {
                Schema::table('roles', function (Blueprint $t) {
                    $t->unique(['tenant_id', 'nome'], 'roles_tenant_nome_unique');
                });
            }
        }

        if (Schema::hasTable('ranking_criteria') && Schema::hasColumn('ranking_criteria', 'tenant_id')) {
            if (Schema::hasIndex('ranking_criteria', ['slug'])) {
                Schema::table('ranking_criteria', function (Blueprint $t) {
                    $t->dropUnique(['slug']);
                });
            }
            if (! Schema::hasIndex('ranking_criteria', ['tenant_id', 'slug'])) {
                Schema::table('ranking_criteria', function (Blueprint $t) {
                    $t->unique(['tenant_id', 'slug'], 'ranking_criteria_tenant_slug_unique');
                });
            }
        }
    }

    public function down(): void
    {
        // Reversão deliberadamente não implementada (segurança em produção).
        // Para reverter: recriar uniques globais manualmente.
    }
};
