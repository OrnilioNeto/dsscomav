<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('nome', 'super_admin')->first();
        $adminRole = Role::where('nome', 'admin')->first();
        $usuarioRole = Role::where('nome', 'usuario')->first();

        // Super Administrador
        User::create([
            'nome' => 'Super Administrador',
            'cpf' => '00000000000',
            'email' => 'superadmin@dss.com',
            'password' => Hash::make('admin123'),
            'telefone' => '(11) 98765-4321',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $superAdminRole->id,
            'setor' => 'Gestão',
            'cargo' => 'Super Administrador',
        ]);

        // Administrador
        User::create([
            'nome' => 'Administrador',
            'cpf' => '11111111111',
            'email' => 'admin@dss.com',
            'password' => Hash::make('admin123'),
            'telefone' => '(11) 98765-4322',
            'data_nascimento' => '1991-01-01',
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $adminRole->id,
            'setor' => 'TI',
            'cargo' => 'Administrador',
        ]);

        // Motorista exemplo
        User::create([
            'nome' => 'João da Silva - Motorista',
            'cpf' => '22222222222',
            'email' => 'joao@dss.com',
            'password' => Hash::make('senha123'),
            'telefone' => '(11) 98765-4323',
            'data_nascimento' => '1985-05-15',
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $usuarioRole->id,
            'cnh' => '1234567890',
            'categoria_cnh' => 'D',
            'validade_cnh' => now()->addYears(2),
        ]);

        // Funcionário exemplo
        User::create([
            'nome' => 'Maria Santos - Funcionária',
            'cpf' => '33333333333',
            'email' => 'maria@dss.com',
            'password' => Hash::make('senha123'),
            'telefone' => '(11) 98765-4324',
            'data_nascimento' => '1988-07-20',
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $usuarioRole->id,
            'setor' => 'Operacional',
            'cargo' => 'Analista',
        ]);

        // Terceirizado exemplo
        User::create([
            'nome' => 'Pedro Costa - Terceirizado',
            'cpf' => '44444444444',
            'email' => 'pedro@dss.com',
            'password' => Hash::make('senha123'),
            'telefone' => '(11) 98765-4325',
            'data_nascimento' => '1992-03-10',
            'tipo_usuario' => 'terceirizado',
            'status' => 'ativo',
            'role_id' => $usuarioRole->id,
            'empresa' => 'Empresa X',
            'responsavel' => 'Contato X',
        ]);
    }
}
