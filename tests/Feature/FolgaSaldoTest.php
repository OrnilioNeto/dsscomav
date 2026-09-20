<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\FolgaSaldoMensal;
use App\Models\User;
use App\Services\FolgaBankService;
use App\Services\FolgaRulesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolgaSaldoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mantém os meses dos cenários no passado, com os créditos já ganhos
        Carbon::setTestNow('2026-11-05 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function motorista(): User
    {
        return User::create([
            'nome' => 'Motorista Teste',
            'cpf' => '99988877766',
            'email' => 'teste@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);
    }

    private function lancarFolgas(User $user, int $mes, int $ano, array $dias): void
    {
        foreach ($dias as $dia) {
            FolgaDia::updateOrCreate(
                ['user_id' => $user->id, 'data' => sprintf('%04d-%02d-%02d', $ano, $mes, $dia)],
                ['tipo' => 'folga']
            );
        }
    }

    private function snapshotSalvo(User $user, int $mes, int $ano): ?FolgaSaldoMensal
    {
        return FolgaSaldoMensal::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->first();
    }

    public function test_saldo_positivo_acumula_para_o_mes_seguinte(): void
    {
        $user = $this->motorista();
        $bank = app(FolgaBankService::class);

        // Sem folgas: agosto gera 5 créditos (31 dias) e setembro mais 5
        $bank->recalcularMes($user, 8, 2026);
        $bank->recalcularMes($user, 9, 2026);

        $this->assertSame(5, $this->snapshotSalvo($user, 8, 2026)->saldo_acumulado);
        $this->assertSame(5, $this->snapshotSalvo($user, 9, 2026)->saldo_anterior);
        $this->assertSame(10, $this->snapshotSalvo($user, 9, 2026)->saldo_acumulado);
    }

    public function test_folgas_pendentes_nao_somem_quando_snapshot_nao_existe(): void
    {
        $user = $this->motorista();

        // Agosto com histórico (1 dia de trabalho registrado), mas sem snapshot gravado
        FolgaDia::create(['user_id' => $user->id, 'data' => '2026-08-01', 'tipo' => 'trabalho']);
        $this->assertNull($this->snapshotSalvo($user, 8, 2026));

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($user->fresh(), 9, 2026);

        // As 5 folgas pendentes de agosto continuam disponíveis em setembro
        $this->assertSame(5, $snapshot['saldo_anterior']);
        $this->assertSame(10, $snapshot['saldo_acumulado']);
    }

    public function test_excesso_de_folgas_zera_o_banco_sem_gerar_divida(): void
    {
        $user = $this->motorista();
        $bank = app(FolgaBankService::class);

        // Agosto sem folgas (5 pendentes) + setembro com 10 folgas (nenhum bloco de 6)
        $this->lancarFolgas($user, 9, 2026, [2, 5, 8, 11, 14, 17, 20, 23, 26, 29]);

        $bank->recalcularMes($user, 8, 2026);

        $setembro = $this->snapshotSalvo($user, 9, 2026);
        $this->assertSame(5, $setembro->saldo_anterior);
        $this->assertSame(0, $setembro->saldo_acumulado);

        // Outubro recomeça do zero (não herda dívida)
        $outubro = app(FolgaRulesService::class)->computeSnapshot($user->fresh(), 10, 2026);
        $this->assertSame(0, $outubro['saldo_anterior']);
        $this->assertSame(5, $outubro['saldo_acumulado']);
    }

    public function test_edicao_retroativa_atualiza_snapshots_seguintes(): void
    {
        $user = $this->motorista();
        $bank = app(FolgaBankService::class);

        $bank->recalcularMes($user, 8, 2026);
        $bank->recalcularMes($user, 9, 2026);
        $this->assertSame(10, $this->snapshotSalvo($user, 9, 2026)->saldo_acumulado);

        // Retroativo: 2 folgas em agosto => agosto 4 créditos - 2 tiradas = 2
        $this->lancarFolgas($user, 8, 2026, [3, 6]);
        $bank->recalcularMes($user, 8, 2026);

        $this->assertSame(2, $this->snapshotSalvo($user, 8, 2026)->saldo_acumulado);
        // Cascata: setembro = 2 + 5 = 7
        $this->assertSame(7, $this->snapshotSalvo($user, 9, 2026)->saldo_acumulado);

        // Outubro usa o snapshot (já corrigido) de setembro
        $outubro = app(FolgaRulesService::class)->computeSnapshot($user->fresh(), 10, 2026);
        $this->assertSame(7, $outubro['saldo_anterior']);
        $this->assertSame(12, $outubro['saldo_acumulado']);
    }

    public function test_recalcular_historico_reconstroi_cadeia_completa(): void
    {
        $user = $this->motorista();
        $bank = app(FolgaBankService::class);

        $this->lancarFolgas($user, 8, 2026, [10]);

        $meses = $bank->recalcularHistorico($user);

        $this->assertGreaterThanOrEqual(2, $meses);
        $this->assertSame(3, $this->snapshotSalvo($user, 8, 2026)->saldo_acumulado);
        $this->assertSame(8, $this->snapshotSalvo($user, 9, 2026)->saldo_acumulado);
    }

    public function test_snapshot_negativo_antigo_e_tratado_como_zero(): void
    {
        $user = $this->motorista();

        // Simula snapshot antigo gravado com dívida (regra anterior)
        FolgaSaldoMensal::create([
            'user_id' => $user->id,
            'mes' => 8,
            'ano' => 2026,
            'previstas' => 4,
            'tiradas' => 10,
            'ajustes' => 0,
            'saldo_anterior' => 0,
            'saldo_acumulado' => -6,
            'domingo_cumprido' => false,
            'dias_trabalhados' => 0,
            'dias_atestado' => 0,
            'dias_licenca' => 0,
        ]);

        $snapshot = app(FolgaRulesService::class)->computeSnapshot($user->fresh(), 9, 2026);

        $this->assertSame(0, $snapshot['saldo_anterior']);
        $this->assertSame(5, $snapshot['saldo_acumulado']);
    }

    public function test_ultima_folga_data_persiste_e_ancora_a_contagem(): void
    {
        $user = $this->motorista();

        $user->update(['ultima_folga_data' => '2026-08-04']);
        $user = $user->fresh();

        $this->assertInstanceOf(Carbon::class, $user->ultima_folga_data);
        $this->assertSame('2026-08-04', $user->ultima_folga_data->format('Y-m-d'));

        // A âncora (04/08) interrompe a sequência: sobram 4 blocos de 6 dias
        $snapshot = app(FolgaRulesService::class)->computeSnapshot($user, 8, 2026);
        $this->assertSame(4, $snapshot['previstas_mes']);
    }
}
