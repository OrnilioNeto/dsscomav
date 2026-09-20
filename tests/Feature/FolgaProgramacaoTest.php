<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\FolgaMovimento;
use App\Models\FolgaProgramacao;
use App\Models\Role;
use App\Models\User;
use App\Services\FolgaRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaProgramacaoTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function programar(array $overrides = []): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.programacao.store'), array_merge([
                'user_id' => $this->motorista->id,
                'data_inicio' => '2026-09-15',
                'data_fim' => '2026-09-18',
                'tipo' => 'folga',
            ], $overrides))
            ->assertRedirect();
    }

    public function test_programacao_nao_debita_antes_da_data_chegar(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');

        $this->programar();

        $rules = app(FolgaRulesService::class);

        $antes = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(0, $antes['tiradas_mes']);

        // Chegou o dia 15 e 16: debita só os dias que chegaram
        Carbon::setTestNow('2026-09-16 09:00:00');
        $durante = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(2, $durante['tiradas_mes']);

        // Fim do período: todos os dias debitados
        Carbon::setTestNow('2026-09-20 09:00:00');
        $depois = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(4, $depois['tiradas_mes']);
    }

    public function test_cancelar_programacao_mantem_dias_ja_passados(): void
    {
        Carbon::setTestNow('2026-09-08 09:00:00');
        $this->programar(['data_inicio' => '2026-09-10', 'data_fim' => '2026-09-15']);

        Carbon::setTestNow('2026-09-13 09:00:00');

        $programacao = FolgaProgramacao::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.folgas.programacao.cancelar', $programacao->id))
            ->assertRedirect();

        $programacao->refresh();
        $this->assertSame('cancelada', $programacao->status);

        // 10, 11 e 12 continuam debitados; 13 em diante não
        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(3, $snapshot['tiradas_mes']);
    }

    public function test_programacao_de_mes_anterior_entra_no_saldo_seguinte(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');

        $this->programar(['data_inicio' => '2026-08-30', 'data_fim' => '2026-08-31']);

        // Agosto: 4 créditos (dias 1-29) - 2 folgas = 2
        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(2, $snapshot['saldo_anterior']);
    }

    public function test_desfazer_periodo_remove_lancamentos_e_estorna(): void
    {
        Carbon::setTestNow('2026-09-01 09:00:00');

        $this->actingAs($this->admin)->post(route('admin.folgas.periodo.store'), [
            'user_id' => $this->motorista->id,
            'data_inicio' => '2026-09-10',
            'data_fim' => '2026-09-15',
            'tipo' => 'folga',
        ])->assertRedirect();

        $this->assertSame(6, FolgaDia::where('user_id', $this->motorista->id)->count());

        $this->actingAs($this->admin)->post(route('admin.folgas.periodo.destroy'), [
            'user_id' => $this->motorista->id,
            'data_inicio' => '2026-09-10',
            'data_fim' => '2026-09-15',
        ])->assertRedirect();

        $this->assertSame(0, FolgaDia::where('user_id', $this->motorista->id)->count());
        $this->assertSame(6, FolgaMovimento::where('user_id', $this->motorista->id)->where('tipo', 'estorno')->count());

        // Com o mês fechado, sem folgas: 30 dias => 5 créditos no saldo real
        Carbon::setTestNow('2026-10-05 09:00:00');

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(5, $snapshot['saldo_acumulado']);
    }

    public function test_desfazer_dia_individual(): void
    {
        Carbon::setTestNow('2026-09-01 09:00:00');

        $this->actingAs($this->admin)->post(route('admin.folgas.dia.store'), [
            'user_id' => $this->motorista->id,
            'data' => '2026-09-12',
            'tipo' => 'folga',
        ])->assertRedirect();

        $dia = FolgaDia::where('user_id', $this->motorista->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('admin.folgas.dia.destroy', $dia->id))
            ->assertRedirect();

        $this->assertSame(0, FolgaDia::where('user_id', $this->motorista->id)->count());
        $this->assertSame(1, FolgaMovimento::where('user_id', $this->motorista->id)->where('tipo', 'estorno')->count());
    }

    public function test_pagina_mostra_programacoes_ativas(): void
    {
        Carbon::setTestNow('2026-09-08 09:00:00');
        $this->programar(['data_inicio' => '2026-09-10', 'data_fim' => '2026-09-15']);

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Programações (7 dias)')
            ->assertSee('10/09/2026 a 15/09/2026');
    }

    public function test_coluna_programacao_mostra_somente_do_mes_exibido(): void
    {
        Carbon::setTestNow('2026-09-08 09:00:00');
        $this->programar(['data_inicio' => '2026-10-05', 'data_fim' => '2026-10-10']);

        // Setembro: nada na coluna (programação é de outubro)
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertViewHas('programacoesPorMotorista', fn ($grupos) => $grupos->flatten()->isEmpty());

        // Outubro: aparece
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertViewHas('programacoesPorMotorista', fn ($grupos) => $grupos->flatten()->count() === 1);
    }

    public function test_endpoint_do_calendario_retorna_programacoes(): void
    {
        Carbon::setTestNow('2026-09-08 09:00:00');
        $this->programar(['data_inicio' => '2026-09-10', 'data_fim' => '2026-09-15']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.folgas.motorista-dados', [
                'user_id' => $this->motorista->id,
                'month' => 9,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertJsonPath('programacoes.0.tipo', 'folga')
            ->assertJsonPath('programacoes.0.motivo', null);
    }
}
