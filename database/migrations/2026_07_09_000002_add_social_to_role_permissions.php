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

            // Garante que não haja duplicatas
            $exists = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('module', 'social')
                ->exists();

            if (!$exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'module' => 'social',
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
        DB::table('role_permissions')->where('module', 'social')->delete();
    }
};
