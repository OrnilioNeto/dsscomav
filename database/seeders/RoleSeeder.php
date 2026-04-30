<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['nome' => 'super_admin'],
            ['descricao' => 'Super Administrador - Acesso total ao sistema']
        );

        Role::updateOrCreate(
            ['nome' => 'admin'],
            ['descricao' => 'Administrador - Gestão de usuários, treinamentos e certificados']
        );

        Role::updateOrCreate(
            ['nome' => 'usuario'],
            ['descricao' => 'Usuário - Acesso a treinamentos conforme seu tipo']
        );
    }
}
