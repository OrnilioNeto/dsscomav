<?php

namespace Tests\Feature;

use App\Models\FolgaProgramacao;
use App\Models\FolgaSetting;
use App\Models\Role;
use App\Models\User;
use App\Models\UserVacation;
use App\Services\FolgaRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaFeriasTest extends TestCase
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

    private function marcoZero(string $data): void
    {
        FolgaSetting::firstOrCreateDefault()->update(['data_inicio_controle' => $data]);
    }

    private function ferias(string $inicio, string $fim): void
    {
        $this->motorista->update(['ferias_inicio' => $inicio, 'ferias_fim' => $fim]);
    }

    public function test_ferias_interrompem_o_credito_efetivo(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');
        $this->marcoZero('2026-09-01');
        $this->ferias('2026-09-10', '2026-09-20');

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 9, 2026);

        // Dias 1-9: crédito no dia 6; férias zeram; dias 21-25 (5) ainda não fecham
        $this->assertSame(1, $snapshot['previstas_ganhas']);
        $this->assertSame(0, $snapshot['tiradas_mes']);
        $this->assertSame(14, $snapshot['dias_trabalhados']); // 9 + 5
        $this->assertSame(5, $snapshot['dias_continuos']); // 21 a 25
        $this->assertSame(1, $snapshot['saldo_acumulado']);
    }

    public function test_dias_continuos_zera_durante_as_ferias(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->marcoZero('2026-09-01');
        $this->ferias('2026-09-10', '2026-09-20');

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 9, 2026);

        $this->assertSame(0, $snapshot['dias_continuos']);
    }

    public function test_contagem_efetiva_continua_entre_meses_com_marco_zero(): void
    {
        Carbon::setTestNow('2026-11-05 09:00:00');
        $this->marcoZero('2026-09-01');

        $rules = app(FolgaRulesService::class);

        // Setembro: 5 créditos (6,12,18,24,30) — dia 30 fecha e zera
        $setembro = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(5, $setembro['previstas_ganhas']);

        // Outubro: 5 créditos (6,12,18,24,30) + 1 dia que sobra
        $outubro = $rules->computeSnapshot($this->motorista->fresh(), 10, 2026);
        $this->assertSame(5, $outubro['previstas_ganhas']);

        // Novembro continua a contagem: dias 1-5 fecham o bloco no dia 5
        $novembro = $rules->computeSnapshot($this->motorista->fresh(), 11, 2026);
        $this->assertSame(1, $novembro['previstas_ganhas']);
    }

    public function test_previsao_mensal_ciclo_6x1(): void
    {
        $rules = app(FolgaRulesService::class);

        $this->assertSame(4, $rules->previsaoMes(9, 2026));
        $this->assertSame(4, $rules->previsaoMes(2, 2026));
        $this->assertSame(4, $rules->previsaoMes(10, 2026));

        // Marco zero no meio do mês: previsão parcial e meses anteriores zerados
        $this->marcoZero('2026-10-16');
        $this->assertSame(2, $rules->previsaoMes(10, 2026));
        $this->assertSame(0, $rules->previsaoMes(9, 2026));
    }

    public function test_programacao_dentro_de_ferias_nao_debita(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->ferias('2026-10-12', '2026-10-20');

        FolgaProgramacao::create([
            'user_id' => $this->motorista->id,
            'data_inicio' => '2026-10-10',
            'data_fim' => '2026-10-15',
            'tipo' => 'folga',
            'status' => 'ativa',
        ]);

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 10, 2026);

        // Só 10 e 11 (antes das férias) debitam
        $this->assertSame(2, $snapshot['tiradas_mes']);
    }

    public function test_pagina_marca_motorista_em_ferias(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->ferias('2026-09-10', '2026-09-20');

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Em férias');
    }

    public function test_relatorio_renderiza_com_previsao(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.folgas.relatorios', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Previsão do mês');

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.relatorios.csv', ['month' => 9, 'year' => 2026]))
            ->assertOk();
    }

    public function test_endpoint_do_calendario_retorna_ferias(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->ferias('2026-09-10', '2026-09-20');

        UserVacation::create([
            'user_id' => $this->motorista->id,
            'data_inicio' => '2026-12-01',
            'data_fim' => '2026-12-15',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.folgas.motorista-dados', [
                'user_id' => $this->motorista->id,
                'month' => 9,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertJsonPath('ferias.0.inicio', '2026-09-10')
            ->assertJsonPath('ferias.0.fim', '2026-09-20')
            ->assertJsonPath('ferias.1.inicio', '2026-12-01');
    }
}
