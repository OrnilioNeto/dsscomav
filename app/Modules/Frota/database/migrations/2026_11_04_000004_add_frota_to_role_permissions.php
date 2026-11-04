<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulos novos precisam nascer na matriz de permissões para aparecerem
     * na tela de Perfis. Admin recebe view/edit; usuário comum fica sem.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $modulos = ['frota', 'integracao_autotrac'];
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $isAdmin = in_array($role->nome, ['admin', 'super_admin'], true);

            foreach ($modulos as $modulo) {
                $existe = DB::table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('module', $modulo)
                    ->exists();

                if ($existe) {
                    continue;
                }

                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'module' => $modulo,
                    'can_view' => $isAdmin,
                    'can_edit' => $isAdmin,
                    'tenant_id' => $role->tenant_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->whereIn('module', ['frota', 'integracao_autotrac'])
            ->delete();
    }
};
