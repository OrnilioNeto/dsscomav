<?php

namespace Tests\Feature;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiDevolucao;
use App\Models\EpiEntrega;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpiDevolucaoTest extends TestCase
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
            'email' => 'gestor-epi@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarEntrega(int $quantidade = 5): EpiEntrega
    {
        $epi = Epi::create([
            'ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA',
            'ss_e_tx_item' => 'Capacete Teste',
            'ss_e_tx_status' => 'ativo',
            'ss_e_nb_vida_util_dias' => 365,
        ]);

        $colaborador = EpiColaborador::create([
            'ss_c_tx_nome' => 'Colaborador Teste',
            'ss_c_tx_cpf' => '11122233344',
            'ss_c_tx_status' => 'ativo',
        ]);

        return EpiEntrega::create([
            'ss_e_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_tx_data_entrega' => now()->toDateString(),
            'ss_e_nb_quantidade' => $quantidade,
            'ss_e_tx_status' => 'ativo',
        ]);
    }

    private function registrarDevolucao(User $user, EpiEntrega $entrega, array $override = []): EpiDevolucao
    {
        $this->actingAs($user)->post(route('epi.devolucao.store'), array_merge([
            'ss_ed_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ed_nb_quantidade' => 5,
            'ss_ed_tx_motivo' => 'avaria',
            'ss_ed_tx_destino' => 'estoque',
            'ss_ed_tx_observacao' => 'Teste automatizado',
        ], $override))->assertRedirect();

        return EpiDevolucao::latest('ss_ed_nb_id')->firstOrFail();
    }

    public function test_registrar_devolucao_ao_estoque_marca_entrega_e_movimento(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntrega(5);

        $devolucao = $this->registrarDevolucao($user, $entrega);

        $this->assertSame('devolvido', $entrega->fresh()->ss_e_tx_status);
        $this->assertDatabaseHas('ss_epi_estoque', [
            'ss_e_nb_devolucao_id' => $devolucao->ss_ed_nb_id,
            'ss_e_tx_tipo' => 'devolucao',
            'ss_e_nb_quantidade' => 5,
        ]);
    }

    public function test_excluir_devolucao_remove_movimento_e_reativa_entrega(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntrega(5);
        $devolucao = $this->registrarDevolucao($user, $entrega);

        $this->actingAs($user)
            ->delete(route('epi.devolucao.destroy', $devolucao->ss_ed_nb_id))
            ->assertRedirect();

        $this->assertDatabaseMissing('ss_epi_devolucao', ['ss_ed_nb_id' => $devolucao->ss_ed_nb_id]);
        $this->assertDatabaseMissing('ss_epi_estoque', ['ss_e_nb_devolucao_id' => $devolucao->ss_ed_nb_id]);

        $entregaAtualizada = $entrega->fresh();
        $this->assertSame('ativo', $entregaAtualizada->ss_e_tx_status);
        $this->assertNull($entregaAtualizada->ss_e_tx_justificativa_exclusao);
    }

    public function test_alterar_devolucao_recalcula_estoque_e_status_da_entrega(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntrega(5);
        $devolucao = $this->registrarDevolucao($user, $entrega);

        $this->actingAs($user)->post(route('epi.devolucao.update', $devolucao->ss_ed_nb_id), [
            'ss_ed_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ed_nb_quantidade' => 2,
            'ss_ed_tx_motivo' => 'perdido',
            'ss_ed_tx_destino' => 'descarte',
            'ss_ed_tx_observacao' => 'Corrigido',
        ])->assertRedirect();

        $devolucaoAtualizada = $devolucao->fresh();
        $this->assertSame(2, (int) $devolucaoAtualizada->ss_ed_nb_quantidade);
        $this->assertSame('perdido', $devolucaoAtualizada->ss_ed_tx_motivo);
        $this->assertSame('descarte', $devolucaoAtualizada->ss_ed_tx_destino);

        // Descarte não retorna nada ao estoque e a devolução parcial mantém a entrega ativa
        $this->assertDatabaseMissing('ss_epi_estoque', ['ss_e_nb_devolucao_id' => $devolucao->ss_ed_nb_id]);
        $this->assertSame('ativo', $entrega->fresh()->ss_e_tx_status);
    }

    public function test_alterar_destino_para_estoque_cria_movimento(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntrega(5);
        $devolucao = $this->registrarDevolucao($user, $entrega, [
            'ss_ed_tx_destino' => 'descarte',
        ]);

        $this->assertDatabaseMissing('ss_epi_estoque', ['ss_e_nb_devolucao_id' => $devolucao->ss_ed_nb_id]);

        $this->actingAs($user)->post(route('epi.devolucao.update', $devolucao->ss_ed_nb_id), [
            'ss_ed_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ed_nb_quantidade' => 3,
            'ss_ed_tx_motivo' => 'devolvido_empresa',
            'ss_ed_tx_destino' => 'estoque',
            'ss_ed_tx_observacao' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('ss_epi_estoque', [
            'ss_e_nb_devolucao_id' => $devolucao->ss_ed_nb_id,
            'ss_e_nb_quantidade' => 3,
            'ss_e_tx_tipo' => 'devolucao',
        ]);
        $this->assertSame('ativo', $entrega->fresh()->ss_e_tx_status);
    }
}
