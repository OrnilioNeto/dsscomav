<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\Tenant;
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

    public function test_super_admin_remove_fundo_padrao_da_plataforma(): void
    {
        $user = $this->criarUsuario('10178415430', 'super_admin');

        $arquivo = $this->criarArquivoFake('uploads/certificado', 'teste_fundo_padrao.png');
        $setting = PlatformSetting::create(['fundo_certificado' => 'uploads/certificado/teste_fundo_padrao.png']);

        $this->actingAs($user)
            ->delete('/plataforma/configuracoes/fundo')
            ->assertRedirect(route('plataforma.settings.edit'));

        $this->assertNull($setting->fresh()->fundo_certificado);
        $this->assertFileDoesNotExist($arquivo);
    }

    public function test_super_admin_remove_fundo_do_cliente(): void
    {
        $user = $this->criarUsuario('10178415430', 'super_admin');

        $arquivo = $this->criarArquivoFake('uploads/999/certificado', 'teste_fundo_cliente.png');

        $tenant = Tenant::create([
            'nome' => 'Cliente Teste',
            'slug' => 'cliente-teste',
            'status' => 'ativo',
            'fundo_certificado' => 'uploads/999/certificado/teste_fundo_cliente.png',
        ]);

        $this->actingAs($user)
            ->delete("/plataforma/{$tenant->id}/fundo")
            ->assertRedirect(route('plataforma.edit', $tenant));

        $this->assertNull($tenant->fresh()->fundo_certificado);
        $this->assertFileDoesNotExist($arquivo);
    }

    private function criarArquivoFake(string $dirRelativo, string $nome): string
    {
        $dir = public_path($dirRelativo);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $caminho = $dir.DIRECTORY_SEPARATOR.$nome;
        file_put_contents($caminho, 'fake');

        return $caminho;
    }
}
