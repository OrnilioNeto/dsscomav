<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\FolgaDomingoSaldo;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\FolgaSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\FolgaRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaMarcoZeroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $motorista;

    private User $motorista2;

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
            'nome' => 'Motorista Um',
            'cpf' => '99988877766',
            'email' => 'um@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);

        $this->motorista2 = User::create([
            'nome' => 'Motorista Dois',
            'cpf' => '88877766655',
            'email' => 'dois@teste.com',
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

    private function salvarMarcoZero(string $data, array $saldos): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.folgas.config.saldos'), [
                'data_inicio_controle' => $data,
                'saldos' => $saldos,
            ])
            ->assertRedirect();
    }

    public function test_salva_data_e_saldos_em_lote(): void
    {
        Carbon::setTestNow('2026-10-01 09:00:00');

        $this->salvarMarcoZero('2026-10-01', [
            $this->motorista->id => 2,
            $this->motorista2->id => 4,
        ]);

        $this->assertSame('2026-10-01', FolgaSetting::firstOrCreateDefault()->data_inicio_controle->format('Y-m-d'));
        $this->assertSame(2, (int) $this->motorista->fresh()->saldo_inicial_folgas);
        $this->assertSame(4, (int) $this->motorista2->fresh()->saldo_inicial_folgas);
    }

    public function test_saldo_inicial_entra_no_mes_inicial_e_zera_antes(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 2]);

        $rules = app(FolgaRulesService::class);

        $outubro = $rules->computeSnapshot($this->motorista->fresh(), 10, 2026);
        $this->assertSame(2, $outubro['saldo_anterior']);
        $this->assertSame(5, $outubro['previstas_mes']); // previsão do mês
        $this->assertSame(0, $outubro['previstas_ganhas']); // dias 1-5: nenhum bloco fechou
        $this->assertSame(2, $outubro['saldo_acumulado']); // saldo real = só o inicial

        $setembro = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(0, $setembro['saldo_anterior']);
        $this->assertSame(0, $setembro['saldo_acumulado']);
        $this->assertSame(0, $setembro['previstas_mes']);
    }

    public function test_inicio_no_meio_do_mes_conta_so_a_partir_da_data(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->salvarMarcoZero('2026-10-16', [$this->motorista->id => 1]);

        $outubro = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 10, 2026);

        // Dias 16 a 20 (hoje): nenhum bloco de 6 fechou ainda
        $this->assertSame(5, $outubro['dias_trabalhados']);
        $this->assertSame(2, $outubro['previstas_mes']); // previsão: blocos em 21 e 27
        $this->assertSame(0, $outubro['previstas_ganhas']);
        $this->assertSame(1, $outubro['saldo_acumulado']); // 1 inicial
        $this->assertSame(5, $outubro['dias_continuos']); // 16 a 20 (hoje)
    }

    public function test_creditos_entram_dia_a_dia_no_saldo_real(): void
    {
        Carbon::setTestNow('2026-09-20 09:00:00');
        $this->salvarMarcoZero('2026-09-15', [$this->motorista->id => 0]);

        $rules = app(FolgaRulesService::class);

        // Dias 15 a 20: bloco fecha no dia 20 => 1 crédito ganho
        $hoje = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(2, $hoje['previstas_mes']); // previsão: 20 e 26
        $this->assertSame(1, $hoje['previstas_ganhas']);
        $this->assertSame(1, $hoje['saldo_acumulado']);

        // Antes do bloco fechar, o saldo real permanece zerado
        Carbon::setTestNow('2026-09-19 09:00:00');
        $antes = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(0, $antes['previstas_ganhas']);
        $this->assertSame(0, $antes['saldo_acumulado']);
    }

    public function test_ajustes_anteriores_ao_marco_zero_sao_ignorados(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 0]);

        // Ajuste de +5 criado antes do marco zero (data 25/09) referenciando outubro
        FolgaMovimento::create([
            'user_id' => $this->motorista->id,
            'data' => '2026-09-25',
            'tipo' => 'ajuste',
            'quantidade' => 5,
            'referencia_mes' => 10,
            'referencia_ano' => 2026,
        ]);

        // Ajuste de +2 dentro do controle (data 02/10)
        FolgaMovimento::create([
            'user_id' => $this->motorista->id,
            'data' => '2026-10-02',
            'tipo' => 'ajuste',
            'quantidade' => 2,
            'referencia_mes' => 10,
            'referencia_ano' => 2026,
        ]);

        $outubro = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 10, 2026);

        $this->assertSame(2, $outubro['ajustes']); // só o ajuste de 02/10
        $this->assertSame(5, $outubro['previstas_mes']);
        $this->assertSame(0, $outubro['previstas_ganhas']);
        $this->assertSame(2, $outubro['saldo_acumulado']); // só o ajuste (créditos ainda não ganhos)
    }

    public function test_banco_de_domingos_zera_no_marco_zero(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');

        // Banco de domingos com saldo acumulado antes do controle
        FolgaDomingoSaldo::create([
            'user_id' => $this->motorista->id,
            'mes' => 9,
            'ano' => 2026,
            'creditos_ganhos' => 1,
            'creditos_usados' => 0,
            'saldo_mes' => 1,
            'saldo_acumulado' => 3,
        ]);

        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 0]);

        $rules = app(FolgaRulesService::class);

        $setembro = $rules->computeSnapshot($this->motorista->fresh(), 9, 2026);
        $this->assertSame(0, $setembro['domingo_saldo']);

        $outubro = $rules->computeSnapshot($this->motorista->fresh(), 10, 2026);
        $this->assertSame(0, $outubro['domingo_saldo_anterior']);
        $this->assertSame(1, $outubro['domingo_saldo']); // +1 ganho no mês
    }

    public function test_snapshots_anteriores_sao_removidos_ao_salvar_marco_zero(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');

        FolgaSaldoMensal::create([
            'user_id' => $this->motorista->id,
            'mes' => 9,
            'ano' => 2026,
            'previstas' => 3,
            'tiradas' => 0,
            'ajustes' => 0,
            'saldo_anterior' => 0,
            'saldo_acumulado' => 3,
        ]);

        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 4]);

        $this->assertSame(
            0,
            FolgaSaldoMensal::where('user_id', $this->motorista->id)
                ->where(fn ($q) => $q->where('ano', '<', 2026)->orWhere(fn ($q2) => $q2->where('ano', 2026)->where('mes', '<', 10)))
                ->count()
        );

        $outubro = FolgaSaldoMensal::where('user_id', $this->motorista->id)->where('mes', 10)->where('ano', 2026)->first();
        $this->assertNotNull($outubro);
        $this->assertSame(4, $outubro->saldo_acumulado); // 4 iniciais (créditos entram com o tempo)
    }

    public function test_saldo_inicial_acumula_para_o_mes_seguinte(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 2]);

        // Quando o mês fecha, os créditos do mês entram cheios no saldo real
        Carbon::setTestNow('2026-12-05 09:00:00');

        $novembro = app(FolgaRulesService::class)->computeSnapshot($this->motorista->fresh(), 11, 2026);
        $this->assertSame(7, $novembro['saldo_anterior']); // 2 + 5 de outubro
        $this->assertSame(12, $novembro['saldo_acumulado']); // 7 + 5 de novembro (mês passado)
    }

    public function test_pagina_de_configuracoes_lista_motoristas_com_saldo(): void
    {
        Carbon::setTestNow('2026-10-01 09:00:00');
        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 3]);

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.config'))
            ->assertOk()
            ->assertSee('Início do Controle')
            ->assertSee('Motorista Um')
            ->assertSee('name="saldos['.$this->motorista->id.']"', false)
            ->assertSee('value="3"', false);
    }

    public function test_alterar_data_para_mais_tarde_zera_meses_anteriores(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 2]);

        // Move o início para dezembro
        $this->salvarMarcoZero('2026-12-01', [$this->motorista->id => 2]);

        $rules = app(FolgaRulesService::class);
        $outubro = $rules->computeSnapshot($this->motorista->fresh(), 10, 2026);
        $this->assertSame(0, $outubro['saldo_acumulado']);

        $dezembro = $rules->computeSnapshot($this->motorista->fresh(), 12, 2026);
        $this->assertSame(2, $dezembro['saldo_anterior']);
    }

    public function test_tabela_respeita_marco_zero_nas_contagens(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');

        // Folga lançada antes do marco zero (25/09)
        FolgaDia::create([
            'user_id' => $this->motorista->id,
            'data' => '2026-09-25',
            'tipo' => 'folga',
        ]);

        $this->salvarMarcoZero('2026-10-01', [$this->motorista->id => 0]);

        $this->actingAs($this->admin)
            ->get(route('admin.folgas.index', ['month' => 9, 'year' => 2026]))
            ->assertOk()
            ->assertViewHas('dados', function ($dados) {
                $s = $dados[$this->motorista->id];

                return $s['tiradas_mes'] === 0 && $s['saldo_acumulado'] === 0;
            });
    }
}
