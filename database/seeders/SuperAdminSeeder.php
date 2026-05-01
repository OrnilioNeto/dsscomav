<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Criar role super_admin
        $role = Role::create([
            'nome' => 'super_admin',
            'descricao' => 'Superadministrador',
        ]);

        // Criar usuário super admin
        User::create([
            'nome' => 'Super Admin',
            'cpf' => '00000000000',
            'email' => 'super@admin.com',
            'password' => Hash::make('password'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }
}
