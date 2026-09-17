<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Inserir registro de permissão 'folgas' para todos os perfis existentes
        $roles = DB::table('roles')->where('nome', '!=', 'super_admin')->get();
        foreach ($roles as $role) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $role->id,
                'module' => 'folgas',
                'can_view' => in_array($role->nome, ['admin']),
                'can_edit' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('module', 'folgas')->delete();
    }
};
