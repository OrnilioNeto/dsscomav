<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ModuleGatingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Role $roleAdmin;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['saas.enabled' => true]);

        $this->roleAdmin = Role::create(['nome' => 'admin', 'descricao' => 'Admin']);
        Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);
        $roleSuper = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);

        $this->tenant = Tenant::create(['nome' => 'Empresa A', 'slug' => 'empresa-a', 'status' => 'ativo']);

        $this->admin = User::create([
            'nome' => 'Admin Empresa A',
            'cpf' => '11111111111',
            'email' => 'admin@a.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $this->roleAdmin->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->super = User::create([
            'nome' => 'Dono Plataforma',
            'cpf' => '99999999999',
            'email' => 'dono@plataforma.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $roleSuper->id,
            'tenant_id' => null,
        ]);

        // Permissão do perfil admin no módulo epi
        RolePermission::create([
            'role_id' => $this->roleAdmin->id,
            'module' => 'epi',
            'can_view' => true,
            'can_edit' => true,
        ]);
    }

    private function liberarModulo(string $module): void
    {
        TenantModule::updateOrCreate(
            ['tenant_id' => $this->tenant->id, 'module' => $module],
            ['enabled' => true, 'enabled_at' => now()]
        );
    }

    private function bloquearModulo(string $module): void
    {
        TenantModule::updateOrCreate(
            ['tenant_id' => $this->tenant->id, 'module' => $module],
            ['enabled' => false]
        );
    }

    private function comoEmpresaA(): self
    {
        URL::forceRootUrl('http://empresa-a.localhost');
        $this->actingAs($this->admin);

        return $this;
    }

    public function test_modulo_liberado_acessa_rota(): void
    {
        $this->liberarModulo('epi');

        $this->comoEmpresaA()
            ->get('/epi')
            ->assertStatus(200);
    }

    public function test_modulo_bloqueado_retorna_403(): void
    {
        $this->bloquearModulo('epi');

        $this->comoEmpresaA()
            ->get('/epi')
            ->assertForbidden();
    }

    public function test_super_admin_ignora_gate_de_modulo(): void
    {
        $this->bloquearModulo('epi');
        URL::forceRootUrl('http://empresa-a.localhost');
        $this->actingAs($this->super);

        $this->get('/epi')->assertStatus(200);
    }

    public function test_roles_de_sistema_visiveis_no_contexto_do_tenant(): void
    {
        app(TenantManager::class)->set($this->tenant);

        $this->assertTrue(Role::where('nome', 'admin')->exists());
        $this->assertTrue(Role::where('nome', 'usuario')->exists());
        $this->assertTrue(Role::where('nome', 'super_admin')->exists());
    }

    public function test_painel_plataforma_so_para_super_admin(): void
    {
        URL::forceRootUrl('http://localhost');

        // Super admin acessa
        $this->actingAs($this->super)->get('/plataforma')->assertStatus(200);

        // Admin de tenant não acessa
        $this->actingAs($this->admin)->get('/plataforma')->assertForbidden();
    }

    public function test_criar_tenant_pelo_painel(): void
    {
        URL::forceRootUrl('http://localhost');
        $this->actingAs($this->super);

        $this->post('/plataforma', [
            'nome' => 'Empresa Beta',
            'slug' => 'empresa-beta',
            'status' => 'ativo',
            'plano' => 'Pro',
            'modules' => ['trainings', 'epi'],
        ])->assertRedirect(route('plataforma.index'));

        $novo = Tenant::where('slug', 'empresa-beta')->first();
        $this->assertNotNull($novo);
        $this->assertTrue($novo->hasModule('trainings'));
        $this->assertTrue($novo->hasModule('epi'));
        $this->assertFalse($novo->hasModule('social'));
    }

    public function test_toggle_modulo_do_tenant(): void
    {
        URL::forceRootUrl('http://localhost');
        $this->actingAs($this->super);

        TenantModule::create(['tenant_id' => $this->tenant->id, 'module' => 'social', 'enabled' => true]);

        $this->post("/plataforma/{$this->tenant->id}/modulos/social/toggle")
            ->assertRedirect();

        $this->assertFalse($this->tenant->hasModule('social'));
    }

    public function test_slug_auto_gerado_garante_unicidade(): void
    {
        URL::forceRootUrl('http://localhost');
        $this->actingAs($this->super);

        // Primeiro "Transp" -> slug "transp"
        $this->post('/plataforma', [
            'nome' => 'Transp',
            'status' => 'ativo',
        ])->assertRedirect(route('plataforma.index'));

        // Segundo "Transp" -> slug deve virar "transp-2" sem erro
        $this->post('/plataforma', [
            'nome' => 'Transp',
            'status' => 'ativo',
        ])->assertRedirect(route('plataforma.index'));

        $this->assertNotNull(Tenant::where('slug', 'transp')->first());
        $this->assertNotNull(Tenant::where('slug', 'transp-2')->first());
    }

    public function test_criar_admin_do_tenant_pelo_painel(): void
    {
        URL::forceRootUrl('http://localhost');
        $this->actingAs($this->super);

        $this->post("/plataforma/{$this->tenant->id}/admin", [
            'nome' => 'Gerente Beta',
            'cpf' => '12345678900',
            'email' => 'gerente@beta.com',
            'senha' => 'senha123',
        ])->assertRedirect();

        $admin = User::where('cpf', '12345678900')->first();
        $this->assertNotNull($admin);
        $this->assertEquals($this->tenant->id, $admin->tenant_id);
        $this->assertEquals('admin', $admin->role?->nome);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('senha123', $admin->password));
    }
}