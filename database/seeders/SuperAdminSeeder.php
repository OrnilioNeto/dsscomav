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
        $role = Role::where('nome', 'super_admin')->first();

        if (! $role) {
            $role = Role::create([
                'nome' => 'super_admin',
                'descricao' => 'Superadministrador',
            ]);
        }

        User::updateOrCreate([
            'cpf' => '10178415430',
        ], [
            'nome' => 'Super Admin',
            'email' => 'superadmin@dss.com',
            'password' => Hash::make('@Machado2025'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
            'telefone' => '(11) 99999-9999',
            'setor' => 'Gestão',
            'cargo' => 'Super Administrador',
        ]);
    }
}
