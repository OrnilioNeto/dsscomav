<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            $canView = ($role->nome === 'admin');
            $canEdit = ($role->nome === 'admin');

            $exists = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('module', 'projeto_pedagogico')
                ->exists();

            if (!$exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'module' => 'projeto_pedagogico',
                    'can_view' => $canView,
                    'can_edit' => $canEdit,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('module', 'projeto_pedagogico')->delete();
    }
};