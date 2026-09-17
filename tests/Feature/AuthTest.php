<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf = '22222222222', string $status = 'ativo', ?Role $role = null): User
    {
        return User::create([
            'nome' => 'João Motorista',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => $status,
            'role_id' => $role?->id,
        ]);
    }

    public function test_login_com_cpf_com_mascara(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario('22222222222', 'ativo', $role);

        $response = $this->post('/login', ['cpf' => '222.222.222-22', 'password' => 'senha123']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_com_senha_invalida(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $this->criarUsuario('22222222222', 'ativo', $role);

        $response = $this->post('/login', ['cpf' => '22222222222', 'password' => 'errada']);

        $response->assertSessionHas('error', 'CPF ou senha inválidos');
        $this->assertGuest();
    }

    public function test_login_bloqueia_usuario_inativo(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $this->criarUsuario('22222222222', 'inativo', $role);

        $response = $this->post('/login', ['cpf' => '22222222222', 'password' => 'senha123']);

        $response->assertSessionHas('error', 'Acesso bloqueado: usuário inativo. Contate o administrador.');
        $this->assertGuest();
    }

    public function test_pagina_login_redireciona_autenticado_para_dashboard(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario('22222222222', 'ativo', $role);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_requer_autenticacao(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
