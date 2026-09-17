<?php

namespace Tests\Feature\Modules;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    private function criarRole(string $nome, array $permissoes = []): Role
    {
        $role = Role::create(['nome' => $nome, 'descricao' => $nome]);

        foreach ($permissoes as $module) {
            RolePermission::create([
                'role_id' => $role->id,
                'module' => $module,
                'can_view' => true,
                'can_edit' => false,
            ]);
        }

        return $role;
    }

    private function criarUsuario(string $cpf, Role $role): User
    {
        return User::create([
            'nome' => 'Usuário '.$cpf,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    public function test_modulo_de_exemplo_registra_rota_view_e_migration(): void
    {
        $this->assertTrue(Route::has('exemplo.index'), 'A rota do módulo não foi registrada.');
        $this->assertTrue(Schema::hasTable('exemplo_registros'), 'A migration do módulo não foi carregada.');

        $super = $this->criarUsuario('99999999999', $this->criarRole('super_admin'));

        $this->actingAs($super)
            ->get(route('exemplo.index'))
            ->assertOk()
            ->assertSee('Módulo de Exemplo')
            ->assertSee('Exemplo (referência)');
    }

    public function test_rota_do_modulo_exige_role_super_admin(): void
    {
        $this->get(route('exemplo.index'))->assertRedirect(route('login'));

        $admin = $this->criarUsuario('88888888888', $this->criarRole('admin'));

        $this->actingAs($admin)->get(route('exemplo.index'))->assertStatus(403);
    }

    public function test_registry_de_menu_filtra_por_permissao_e_ordena(): void
    {
        $registry = app(ModuleRegistry::class);
        $registry->register(['slug' => 'b', 'route' => 'dashboard', 'label' => 'Restrito', 'icon' => 'fas fa-b', 'permission' => 'users', 'order' => 1]);
        $registry->register(['slug' => 'a', 'route' => 'dashboard', 'label' => 'Livre', 'icon' => 'fas fa-a', 'permission' => null, 'order' => 2]);

        $comPermissao = $this->criarUsuario('77777777777', $this->criarRole('admin', ['users']));
        $this->actingAs($comPermissao);
        $this->assertSame(['Restrito', 'Livre'], array_column($registry->all(), 'label'));

        $semPermissao = $this->criarUsuario('66666666666', $this->criarRole('usuario'));
        $this->actingAs($semPermissao);
        $this->assertSame(['Livre'], array_column($registry->all(), 'label'));
    }

    public function test_permission_controller_usa_config_modules_como_fonte_unica(): void
    {
        config(['modules.exemplo_teste' => [
            'label' => 'Módulo de Teste XYZ',
            'description' => 'Módulo criado em runtime no teste',
        ]]);

        // A matriz de permissões só renderiza os módulos quando há perfis listados.
        $this->criarRole('admin_teste');

        $super = $this->criarUsuario('55555555555', $this->criarRole('super_admin'));

        $this->actingAs($super)
            ->get(route('admin.permissoes.index'))
            ->assertOk()
            ->assertSee('Módulo de Teste XYZ');
    }

    public function test_catalogo_config_modules_cobre_modulos_do_sistema(): void
    {
        $slugs = [
            'users', 'trainings', 'certificates', 'rankings', 'splash', 'social',
            'epi', 'projeto_pedagogico', 'folgas', 'rewatch', 'auditoria', 'permissions',
        ];

        foreach ($slugs as $slug) {
            $this->assertArrayHasKey($slug, config('modules', []), "Módulo [{$slug}] ausente em config/modules.php.");
            $this->assertNotEmpty(config("modules.{$slug}.label"), "Módulo [{$slug}] sem label em config/modules.php.");
        }
    }
}
