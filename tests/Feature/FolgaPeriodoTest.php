<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\Role;
use App\Models\User;
use App\Services\FolgaRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaPeriodoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $motorista;

    protected function setUp(): void
    {
        parent::setUp();

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
            'nome' => 'Motorista Teste',
            'cpf' => '99988877766',
            'email' => 'motorista@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);
    }

    private function periodo(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->motorista->id,
            'data_inicio' => '2026-08-10',
            'data_fim' => '2026-08-15',
            'tipo' => 'folga',
            'motivo_folga' => 'Férias',
        ], $overrides);
    }

    public function test_pagina_de_folgas_renderiza_com_o_modal_de_periodo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index'))
            ->assertOk()
            ->assertSee('Lançar Período')
            ->assertSee('periodoModal', false);
    }

    public function test_lanca_periodo_de_folgas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.periodo.store'), $this->periodo())
            ->assertRedirect();

        $this->assertSame(6, FolgaDia::where('user_id', $this->motorista->id)->where('tipo', 'folga')->count());
        $this->assertSame(6, FolgaMovimento::where('user_id', $this->motorista->id)->where('tipo', 'debito_folga')->count());
        $this->assertSame(1, FolgaDia::where('user_id', $this->motorista->id)->whereDate('data', '2026-08-15')->count());
        $this->assertSame(0, FolgaDia::where('user_id', $this->motorista->id)->whereDate('data', '2026-08-16')->count());

        $this->assertNotNull(
            FolgaSaldoMensal::where('user_id', $this->motorista->id)->where('mes', 8)->where('ano', 2026)->first()
        );
    }

    public function test_alterar_periodo_para_trabalho_estorna_movimentos(): void
    {
        $this->actingAs($this->admin)->post(route('admin.folgas.periodo.store'), $this->periodo());
        $this->actingAs($this->admin)->post(route('admin.folgas.periodo.store'), $this->periodo(['tipo' => 'trabalho']));

        $this->assertSame(0, FolgaDia::where('user_id', $this->motorista->id)->where('tipo', 'folga')->count());
        $this->assertSame(6, FolgaDia::where('user_id', $this->motorista->id)->where('tipo', 'trabalho')->count());
        $this->assertSame(6, FolgaMovimento::where('user_id', $this->motorista->id)->where('tipo', 'estorno')->count());

        // Sem folgas no mês: 31 dias => 5 créditos
        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 8, 2026);
        $this->assertSame(5, $snapshot['saldo_acumulado']);
    }

    public function test_valida_data_final_anterior_a_inicial(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.periodo.store'), $this->periodo(['data_fim' => '2026-08-09']))
            ->assertSessionHasErrors('data_fim');

        $this->assertSame(0, FolgaDia::count());
    }

    public function test_limite_de_60_dias_por_lancamento(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.periodo.store'), $this->periodo([
                'data_inicio' => '2026-08-01',
                'data_fim' => '2026-10-01',
            ]))
            ->assertSessionHas('error');

        $this->assertSame(0, FolgaDia::count());
    }

    public function test_periodo_atravessa_meses_e_recalcula_cascata(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.periodo.store'), $this->periodo([
                'data_inicio' => '2026-08-28',
                'data_fim' => '2026-09-02',
            ]))
            ->assertRedirect();

        $this->assertSame(6, FolgaDia::where('user_id', $this->motorista->id)->count());
        $this->assertNotNull(
            FolgaSaldoMensal::where('user_id', $this->motorista->id)->where('mes', 8)->where('ano', 2026)->first()
        );
        $this->assertNotNull(
            FolgaSaldoMensal::where('user_id', $this->motorista->id)->where('mes', 9)->where('ano', 2026)->first()
        );
    }
}
