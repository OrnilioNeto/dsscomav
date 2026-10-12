<?php

namespace Tests\Feature;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpiVidaUtilTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioComEpi(): User
    {
        $role = Role::create(['nome' => 'admin', 'descricao' => 'admin']);

        RolePermission::create([
            'role_id' => $role->id,
            'module' => 'epi',
            'can_view' => true,
            'can_edit' => true,
        ]);

        return User::create([
            'nome' => 'Gestor EPI',
            'cpf' => '99999999999',
            'email' => 'gestor-vida-util@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarEpi(int $vidaUtilDias = 365): Epi
    {
        return Epi::create([
            'ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA',
            'ss_e_tx_item' => 'Capacete Teste',
            'ss_e_tx_status' => 'ativo',
            'ss_e_nb_vida_util_dias' => $vidaUtilDias,
        ]);
    }

    private function criarColaborador(): EpiColaborador
    {
        return EpiColaborador::create([
            'ss_c_tx_nome' => 'Colaborador Teste',
            'ss_c_tx_cpf' => '11122233344',
            'ss_c_tx_status' => 'ativo',
        ]);
    }

    private function criarEntrega(Epi $epi, EpiColaborador $colaborador, array $override = []): EpiEntrega
    {
        return EpiEntrega::create(array_merge([
            'ss_e_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_tx_data_entrega' => '2026-01-01',
            'ss_e_nb_quantidade' => 1,
            'ss_e_tx_vencimento' => '2027-01-01',
            'ss_e_tx_status' => 'ativo',
        ], $override));
    }

    private function atualizarVidaUtil(User $user, Epi $epi, int $vidaUtilDias): void
    {
        $this->actingAs($user)->post(route('epi.catalogo.store'), [
            'ss_e_nb_id' => $epi->ss_e_nb_id,
            'ss_e_tx_grupo' => $epi->ss_e_tx_grupo,
            'ss_e_tx_item' => $epi->ss_e_tx_item,
            'ss_e_nb_vida_util_dias' => $vidaUtilDias,
        ])->assertRedirect();
    }

    public function test_alterar_vida_util_recalcula_vencimento_das_entregas_ativas(): void
    {
        $user = $this->usuarioComEpi();
        $epi = $this->criarEpi(365);
        $colaborador = $this->criarColaborador();
        $entrega = $this->criarEntrega($epi, $colaborador);

        $this->atualizarVidaUtil($user, $epi, 30);

        $this->assertSame('2026-01-31', $entrega->fresh()->ss_e_tx_vencimento);
    }

    public function test_alterar_vida_util_nao_altera_entregas_encerradas(): void
    {
        $user = $this->usuarioComEpi();
        $epi = $this->criarEpi(365);
        $colaborador = $this->criarColaborador();
        $devolvida = $this->criarEntrega($epi, $colaborador, ['ss_e_tx_status' => 'devolvido']);
        $inativa = $this->criarEntrega($epi, $colaborador, ['ss_e_tx_status' => 'inativo']);

        $this->atualizarVidaUtil($user, $epi, 30);

        $this->assertSame('2027-01-01', $devolvida->fresh()->ss_e_tx_vencimento);
        $this->assertSame('2027-01-01', $inativa->fresh()->ss_e_tx_vencimento);
    }

    public function test_vida_util_zero_remove_vencimento_das_entregas_ativas(): void
    {
        $user = $this->usuarioComEpi();
        $epi = $this->criarEpi(365);
        $colaborador = $this->criarColaborador();
        $entrega = $this->criarEntrega($epi, $colaborador);

        $this->atualizarVidaUtil($user, $epi, 0);

        $this->assertNull($entrega->fresh()->ss_e_tx_vencimento);
    }

    public function test_salvar_epi_sem_alterar_vida_util_sincroniza_vencimento_divergente(): void
    {
        $user = $this->usuarioComEpi();
        $epi = $this->criarEpi(365);
        $colaborador = $this->criarColaborador();
        $entrega = $this->criarEntrega($epi, $colaborador, ['ss_e_tx_vencimento' => '2025-01-01']);

        $this->atualizarVidaUtil($user, $epi, 365);

        $this->assertSame('2027-01-01', $entrega->fresh()->ss_e_tx_vencimento);
    }

    public function test_comando_recalcula_vencimentos_de_todas_as_entregas_ativas(): void
    {
        $epi = $this->criarEpi(100);
        $colaborador = $this->criarColaborador();
        $entrega = $this->criarEntrega($epi, $colaborador, ['ss_e_tx_vencimento' => '2020-01-01']);
        $devolvida = $this->criarEntrega($epi, $colaborador, [
            'ss_e_tx_status' => 'devolvido',
            'ss_e_tx_vencimento' => '2020-01-01',
        ]);

        $this->artisan('epi:recalcular-vencimentos')->assertSuccessful();

        $this->assertSame('2026-04-11', $entrega->fresh()->ss_e_tx_vencimento);
        $this->assertSame('2020-01-01', $devolvida->fresh()->ss_e_tx_vencimento);
    }

    public function test_nova_entrega_ignora_validade_do_lote_e_conta_vida_util_da_data_da_entrega(): void
    {
        $user = $this->usuarioComEpi();
        $epi = $this->criarEpi(100);
        $colaborador = $this->criarColaborador();

        EpiEstoque::create([
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_nb_quantidade' => 5,
            'ss_e_tx_tipo' => 'entrada',
            'ss_e_tx_data' => now(),
            'ss_e_tx_validade' => '2030-01-01',
        ]);

        $this->actingAs($user)->post(route('epi.entrega.store'), [
            'ss_e_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_e_tx_data_entrega' => '2026-01-01',
            'itens' => [
                ['epi_id' => $epi->ss_e_nb_id, 'quantidade' => 1],
            ],
        ])->assertRedirect();

        $entrega = EpiEntrega::where('ss_e_nb_epi_id', $epi->ss_e_nb_id)->latest('ss_e_nb_id')->firstOrFail();

        $this->assertSame('2026-04-11', $entrega->ss_e_tx_vencimento);
    }
}
