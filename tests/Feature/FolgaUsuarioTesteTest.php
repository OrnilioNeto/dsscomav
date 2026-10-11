<?php

namespace Tests\Feature;

use App\Models\FolgaLog;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaUsuarioTesteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $motorista;

    private User $motoristaTeste;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-05 09:00:00');

        $role = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);

        $this->admin = User::create([
            'nome' => 'Admin Teste',
            'cpf' => '10178415430',
            'email' => 'admin@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);

        $this->motorista = User::create([
            'nome' => 'Motorista Comum',
            'cpf' => '99988877766',
            'email' => 'motorista@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);

        $this->motoristaTeste = User::create([
            'nome' => 'Motorista Marcado Teste',
            'cpf' => '11122233344',
            'email' => 'marcado.teste@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'usuario_teste' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_indice_de_folgas_oculta_motorista_marcado_como_teste(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Motorista Comum')
            ->assertDontSee('Motorista Marcado Teste');
    }

    public function test_relatorio_csv_oculta_motorista_marcado_como_teste(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.relatorios.csv', ['month' => 11, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Motorista Comum', false)
            ->assertDontSee('Motorista Marcado Teste', false);
    }

    public function test_auditoria_oculta_logs_do_motorista_marcado_como_teste(): void
    {
        foreach ([$this->motorista, $this->motoristaTeste] as $user) {
            FolgaLog::create([
                'user_id' => $user->id,
                'acao' => 'criar_dia',
                'tabela' => 'folga_dias',
                'registro_id' => 1,
                'created_by' => $this->admin->id,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.auditoria'))
            ->assertOk()
            ->assertSee('Motorista Comum')
            ->assertDontSee('Motorista Marcado Teste');
    }

    public function test_recalculo_ignora_motorista_marcado_como_teste(): void
    {
        $this->artisan('folgas:recalculate', ['--month' => 11, '--year' => 2026])
            ->assertExitCode(0);

        $this->assertDatabaseHas('folga_saldos_mensais', [
            'user_id' => $this->motorista->id,
            'mes' => 11,
            'ano' => 2026,
        ]);

        $this->assertDatabaseMissing('folga_saldos_mensais', [
            'user_id' => $this->motoristaTeste->id,
            'mes' => 11,
            'ano' => 2026,
        ]);
    }
}
