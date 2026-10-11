<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioTipoMotoristaMonitorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $roleUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $this->roleUsuario = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);

        $this->admin = User::create([
            'nome' => 'Admin Teste',
            'cpf' => '10178415430',
            'email' => 'admin@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $superRole->id,
        ]);
    }

    private function criarMotoristaMonitor(string $cpf = '99988877766'): User
    {
        return User::create([
            'nome' => 'Motorista Monitor Teste',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista_monitor',
            'status' => 'ativo',
            'role_id' => $this->roleUsuario->id,
        ]);
    }

    public function test_edicao_permite_alterar_tipo_para_motorista_monitor(): void
    {
        $usuario = User::create([
            'nome' => 'Motorista Comum',
            'cpf' => '11122233344',
            'email' => 'comum@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $this->roleUsuario->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $usuario->id), [
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'tipo_usuario' => 'motorista_monitor',
                'status' => 'ativo',
                'role_id' => $this->roleUsuario->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'tipo_usuario' => 'motorista_monitor',
        ]);
    }

    public function test_tela_de_edicao_exibe_opcao_motorista_monitor(): void
    {
        $usuario = $this->criarMotoristaMonitor();

        $this->actingAs($this->admin)
            ->get(route('usuarios.edit', $usuario->id))
            ->assertOk()
            ->assertSee('Motorista Monitor');
    }

    public function test_motorista_monitor_e_equivalente_a_motorista_em_treinamentos(): void
    {
        $monitor = $this->criarMotoristaMonitor();

        $training = Training::create([
            'titulo' => 'DSS Monitor',
            'descricao' => 'Treinamento de teste',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://example.com/video',
            'tipo_video' => 'youtube',
            'status' => 'ativo',
        ]);

        $this->assertTrue($training->isPermittedFor('motorista_monitor'));
        $this->assertTrue($monitor->canAccessTraining($training));
        $this->assertTrue($monitor->isEligibleForContent($training));
        $this->assertTrue(
            User::kpiEligible()->eligibleForContent($training)->where('id', $monitor->id)->exists()
        );
    }

    public function test_motorista_monitor_aparece_na_lista_de_folgas(): void
    {
        $monitor = $this->criarMotoristaMonitor();

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('Motorista Monitor Teste');
    }

    public function test_configuracao_pode_desativar_controle_de_folgas_do_monitor(): void
    {
        $monitor = $this->criarMotoristaMonitor();
        $motorista = User::create([
            'nome' => 'Motorista Comum',
            'cpf' => '11122233344',
            'email' => 'comum@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $this->roleUsuario->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.folgas.config.update'), [
                'dias_para_folga' => 6,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('folga_settings', ['incluir_motorista_monitor' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('Motorista Comum')
            ->assertDontSee('Motorista Monitor Teste');

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.relatorios.csv', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('Motorista Comum', false)
            ->assertDontSee('Motorista Monitor Teste', false);

        $this->artisan('folgas:recalculate', ['--month' => now()->month, '--year' => now()->year])
            ->assertExitCode(0);

        $this->assertDatabaseHas('folga_saldos_mensais', [
            'user_id' => $motorista->id,
            'mes' => now()->month,
            'ano' => now()->year,
        ]);

        $this->assertDatabaseMissing('folga_saldos_mensais', [
            'user_id' => $monitor->id,
            'mes' => now()->month,
            'ano' => now()->year,
        ]);
    }
}
