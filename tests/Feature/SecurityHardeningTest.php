<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Training;
use App\Models\TrainingMaterial;
use App\Models\User;
use App\Support\SafeUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function criarRole(string $nome, array $permissoes = []): Role
    {
        $role = Role::create(['nome' => $nome, 'descricao' => $nome]);

        foreach ($permissoes as $module => $editavel) {
            RolePermission::create([
                'role_id' => $role->id,
                'module' => $module,
                'can_view' => true,
                'can_edit' => $editavel,
            ]);
        }

        return $role;
    }

    private function criarUsuario(string $cpf, Role $role, string $nome = 'Usuário Teste'): User
    {
        return User::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarTraining(): Training
    {
        return Training::create([
            'titulo' => 'DSS Teste',
            'descricao' => 'Treinamento de teste',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['funcionario'],
            'url_video' => 'https://example.com/video',
            'tipo_video' => 'youtube',
            'status' => 'ativo',
        ]);
    }

    public function test_login_bloqueia_apos_cinco_tentativas_seguidas(): void
    {
        $role = $this->criarRole('usuario');
        $this->criarUsuario('33333333333', $role);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['cpf' => '33333333333', 'password' => 'errada'])->assertStatus(302);
        }

        $this->post('/login', ['cpf' => '33333333333', 'password' => 'errada'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Muitas tentativas em pouco tempo. Aguarde um minuto e tente novamente.');

        $this->assertGuest();
    }

    public function test_senha_nao_fica_no_flash_da_sessao(): void
    {
        $role = $this->criarRole('usuario');
        $this->criarUsuario('44444444444', $role);

        $response = $this->post('/login', ['cpf' => '44444444444', 'password' => 'senha-secreta-invalida']);

        $response->assertSessionHas('_old_input.cpf');
        $response->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_admin_nao_pode_criar_usuario_super_admin(): void
    {
        $adminRole = $this->criarRole('admin', ['users' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');
        $superRole = $this->criarRole('super_admin');

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'nome' => 'Novo Super',
            'cpf' => '66666666666',
            'email' => 'novo-super@teste.com',
            'password' => 'senhaSegura123',
            'tipo_usuario' => 'funcionario',
            'role_id' => $superRole->id,
        ]);

        $response->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['cpf' => '66666666666']);
    }

    public function test_admin_nao_pode_atribuir_role_super_admin_em_update(): void
    {
        $adminRole = $this->criarRole('admin', ['users' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');
        $alvo = $this->criarUsuario('77777777777', $this->criarRole('usuario'), 'Alvo Teste');
        $superRole = $this->criarRole('super_admin');

        $response = $this->actingAs($admin)->put(route('usuarios.update', $alvo->id), [
            'nome' => 'Alvo Teste',
            'email' => 'alvo@teste.com',
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $superRole->id,
        ]);

        $response->assertSessionHasErrors('role_id');
        $this->assertDatabaseHas('users', ['id' => $alvo->id, 'role_id' => $alvo->role_id]);
    }

    public function test_admin_nao_pode_excluir_super_admin(): void
    {
        $adminRole = $this->criarRole('admin', ['users' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');
        $super = $this->criarUsuario('88888888888', $this->criarRole('super_admin'), 'Super Teste');

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $super->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }

    public function test_usuario_nao_pode_excluir_a_propria_conta(): void
    {
        $adminRole = $this->criarRole('admin', ['users' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_upload_de_material_rejeita_arquivo_php(): void
    {
        Storage::fake('public');

        $adminRole = $this->criarRole('admin', ['trainings' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');
        $training = $this->criarTraining();

        $response = $this->actingAs($admin)->postJson(route('materiais.upload', $training->id), [
            'arquivo' => UploadedFile::fake()->createWithContent('malware.php', '<?php echo "hack"; ?>'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, TrainingMaterial::count());
    }

    public function test_upload_de_material_aceita_pdf_com_nome_gerado_no_servidor(): void
    {
        Storage::fake('public');

        $adminRole = $this->criarRole('admin', ['trainings' => true]);
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Teste');
        $training = $this->criarTraining();

        $conteudoPdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";

        $response = $this->actingAs($admin)->postJson(route('materiais.upload', $training->id), [
            'arquivo' => UploadedFile::fake()->createWithContent('documento.pdf', $conteudoPdf),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $material = TrainingMaterial::firstOrFail();
        $this->assertStringEndsWith('.pdf', $material->arquivo);
        $this->assertStringNotContainsString('documento.pdf', $material->arquivo);
        $this->assertTrue(Str::isUuid(pathinfo($material->arquivo, PATHINFO_FILENAME)));
    }

    public function test_safe_upload_rejeita_extensoes_executaveis(): void
    {
        $malware = UploadedFile::fake()->createWithContent('shell.phtml', '<?php system($_GET["c"]); ?>');
        $this->assertNull(SafeUpload::filenameForUploadedFile($malware, ['pdf', 'txt']));

        $pdf = UploadedFile::fake()->createWithContent('ok.pdf', "%PDF-1.4\n%%EOF");
        $this->assertSame('pdf', SafeUpload::extensionFromUploadedFile($pdf, ['pdf', 'txt']));
    }

    public function test_validacao_publica_de_certificado_mascara_pii(): void
    {
        $role = $this->criarRole('usuario');
        $user = $this->criarUsuario('22222222222', $role, 'João da Silva');
        $training = $this->criarTraining();

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'codigo_certificado' => 'ABC123XYZ789',
            'data_emissao' => now(),
            'valido' => true,
        ]);

        $response = $this->get(route('validar.certificado', $certificate->codigo_certificado));

        $response->assertOk();
        $response->assertSee(mask_cpf($user->cpf));
        $response->assertSee(mask_email($user->email));
        $response->assertDontSee('222.222.222-22');
    }

    public function test_api_ficha_publica_mascara_cpf(): void
    {
        $role = $this->criarRole('usuario');
        $user = $this->criarUsuario('22222222222', $role, 'João da Silva');

        $response = $this->getJson('/api/v1/ficha/'.$user->qrcode_token);

        $response->assertOk();
        $response->assertJsonPath('data.colaborador.cpf', mask_cpf('22222222222'));
    }

    public function test_helpers_de_mascara_pii(): void
    {
        $this->assertSame('***.222.222-**', mask_cpf('222.222.222-22'));
        $this->assertSame('***', mask_cpf('123'));
        $this->assertSame('j***@teste.com', mask_email('joao@teste.com'));
        $this->assertSame('(**) *****-8888', mask_phone('(11) 98888-8888'));
        $this->assertSame('Não informado', mask_phone(null));
    }

    public function test_rodape_exibe_versao_e_data(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(app_version());
        $response->assertSee(app_version_date());
    }
}
