<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformCertificateSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf, string $roleNome): User
    {
        $role = Role::create(['nome' => $roleNome, 'descricao' => $roleNome]);

        return User::create([
            'nome' => 'Usuário Teste',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    public function test_super_admin_acessa_configuracoes_do_certificado(): void
    {
        $user = $this->criarUsuario('10178415430', 'super_admin');

        $response = $this->actingAs($user)->get('/plataforma/configuracoes');

        $response->assertOk();
        $response->assertSee('Certificado padrão da plataforma', false);
    }

    public function test_admin_comum_nao_acessa_configuracoes_do_certificado(): void
    {
        $user = $this->criarUsuario('11111111111', 'admin');

        $this->actingAs($user)->get('/plataforma/configuracoes')->assertForbidden();
    }
}
