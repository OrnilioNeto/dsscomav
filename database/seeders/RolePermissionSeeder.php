<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['users', 'trainings', 'certificates', 'rankings', 'splash', 'social', 'epi', 'projeto_pedagogico', 'rewatch', 'permissions'];

        // 1. Admin Role
        $admin = Role::where('nome', 'admin')->first();
        if ($admin) {
            foreach ($modules as $module) {
                // Admin tem acesso total a tudo exceto gerenciamento de permissões
                $canEdit = ($module !== 'permissions');
                
                RolePermission::updateOrCreate(
                    ['role_id' => $admin->id, 'module' => $module],
                    ['can_view' => true, 'can_edit' => $canEdit]
                );
            }
        }

        // 2. Usuario Role
        $usuario = Role::where('nome', 'usuario')->first();
        if ($usuario) {
            foreach ($modules as $module) {
                // Usuário comum pode apenas visualizar treinamentos na plataforma
                $canView = ($module === 'trainings');
                
                RolePermission::updateOrCreate(
                    ['role_id' => $usuario->id, 'module' => $module],
                    ['can_view' => $canView, 'can_edit' => false]
                );
            }
        }
    }
}
