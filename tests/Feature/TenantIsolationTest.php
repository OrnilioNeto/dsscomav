<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\User;
use App\Support\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Role $roleUsuario;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['saas.enabled' => true]);

        $this->roleUsuario = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);

        $this->tenantA = Tenant::create(['nome' => 'Empresa A', 'slug' => 'empresa-a', 'status' => 'ativo']);
        $this->tenantB = Tenant::create(['nome' => 'Empresa B', 'slug' => 'empresa-b', 'status' => 'ativo']);

        $this->userA = $this->criarUsuario('11111111111', $this->tenantA->id);
        $this->userB = $this->criarUsuario('22222222222', $this->tenantB->id);
    }

    private function criarUsuario(string $cpf, ?int $tenantId): User
    {
        return User::create([
            'nome' => 'Usuário '.$cpf,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $this->roleUsuario->id,
            'tenant_id' => $tenantId,
        ]);
    }

    private function setTenant(Tenant $tenant): void
    {
        app(TenantManager::class)->set($tenant);
    }

    public function test_scope_filtra_por_tenant(): void
    {
        $this->setTenant($this->tenantA);

        $usuarios = User::where('status', 'ativo')->get();

        $this->assertTrue($usuarios->contains('id', $this->userA->id));
        $this->assertFalse($usuarios->contains('id', $this->userB->id));
    }

    public function test_scope_nao_filtra_sem_tenant_resolvido(): void
    {
        // Flag ligada mas sem contexto (ex.: painel da plataforma): vê tudo.
        $usuarios = User::where('status', 'ativo')->get();

        $this->assertCount(2, $usuarios);
    }

    public function test_criacao_preenche_tenant_id_automaticamente(): void
    {
        $this->setTenant($this->tenantA);

        $treinamento = Training::create([
            'titulo' => 'Treinamento A',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=teste',
            'tipo_video' => 'youtube',
            'carga_horaria' => 60,
            'status' => 'ativo',
        ]);

        $this->assertEquals($this->tenantA->id, $treinamento->tenant_id);
        $this->assertEquals(1, Training::where('titulo', 'Treinamento A')->count());
    }

    public function test_tenant_a_nao_ve_treinamento_do_tenant_b(): void
    {
        $this->setTenant($this->tenantB);
        Training::create([
            'titulo' => 'Treinamento B',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=teste',
            'tipo_video' => 'youtube',
            'carga_horaria' => 60,
            'status' => 'ativo',
        ]);

        $this->setTenant($this->tenantA);
        $this->assertFalse(Training::where('titulo', 'Treinamento B')->exists());
        $this->assertCount(0, Training::all());
    }

    public function test_login_com_cpf_de_outro_tenant_falha(): void
    {
        URL::forceRootUrl('http://empresa-a.localhost');

        $response = $this->post('/login', ['cpf' => $this->userB->cpf, 'password' => 'senha123']);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_login_no_tenant_correto_funciona(): void
    {
        URL::forceRootUrl('http://empresa-a.localhost');

        $response = $this->post('/login', ['cpf' => $this->userA->cpf, 'password' => 'senha123']);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->userA);
    }

    public function test_super_admin_nao_e_filtrado_pelo_escopo(): void
    {
        $roleSuper = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $super = User::create([
            'nome' => 'Dono da Plataforma',
            'cpf' => '99999999999',
            'email' => 'dono@plataforma.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $roleSuper->id,
            'tenant_id' => null,
        ]);

        $this->setTenant($this->tenantA);
        $this->actingAs($super);
        $usuarios = User::where('status', 'ativo')->get();

        $this->assertTrue($usuarios->contains('id', $super->id));
    }
}
