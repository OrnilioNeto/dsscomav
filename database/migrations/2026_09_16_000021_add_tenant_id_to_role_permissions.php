<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * role_permissions ficou de fora da migration 000011 (correção).
     * O tenant_id é herdado da role: roles de sistema (tenant_id NULL) mantêm
     * NULL; roles de tenant ganham o tenant da role.
     */
    public function up(): void
    {
        if (! Schema::hasTable('role_permissions') || Schema::hasColumn('role_permissions', 'tenant_id')) {
            return;
        }

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions') && Schema::hasColumn('role_permissions', 'tenant_id')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};