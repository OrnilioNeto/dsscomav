<?php

namespace Tests\Feature;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiDevolucao;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\EpiFiltro;
use App\Models\EpiFiltroTipo;
use App\Models\EpiVariacao;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que os relatórios em PDF das abas de controle do módulo de EPI
 * são gerados com sucesso e devolvem um arquivo PDF (sem persistir nada).
 */
class EpiRelatorioPdfTest extends TestCase
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
            'nome' => 'Gestor EPI PDF',
            'cpf' => '99988877766',
            'email' => 'gestor-epi-pdf@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarDadosEpi(): void
    {
        $epi = Epi::create([
            'ss_e_tx_grupo' => 'FARDAMENTO',
            'ss_e_tx_subgrupo' => 'Uniformes',
            'ss_e_tx_item' => 'Camisa Polo Teste',
            'ss_e_tx_ca' => '12345',
            'ss_e_tx_validade_ca' => now()->addYear()->toDateString(),
            'ss_e_nb_vida_util_dias' => 365,
            'ss_e_tx_status' => 'ativo',
        ]);

        $colaborador = EpiColaborador::create([
            'ss_c_tx_nome' => 'Colaborador PDF',
            'ss_c_tx_cpf' => '55544433322',
            'ss_c_tx_matricula' => 'MAT-00001',
            'ss_c_tx_status' => 'ativo',
        ]);

        $variacao = EpiVariacao::create([
            'ss_ev_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_ev_tx_nome' => 'M',
            'ss_ev_tx_status' => 'ativo',
        ]);

        EpiEstoque::create([
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_variacao_id' => $variacao->ss_ev_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_nb_quantidade' => 7,
            'ss_e_tx_tipo' => 'entrada',
            'ss_e_tx_data' => now()->subDay(),
            'ss_e_tx_fornecedor' => 'Fornecedor Teste',
            'ss_e_tx_chave_nf' => 'NF-123',
        ]);

        $entrega = EpiEntrega::create([
            'ss_e_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_tx_data_entrega' => now()->toDateString(),
            'ss_e_nb_quantidade' => 2,
            'ss_e_tx_vencimento' => now()->addDays(30)->toDateString(),
            'ss_e_tx_status' => 'ativo',
            'ss_e_tx_requer_assinatura' => true,
            'ss_e_tx_status_assinatura' => 'pendente',
        ]);

        EpiDevolucao::create([
            'ss_ed_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ed_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_ed_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_ed_nb_empresa_id' => 0,
            'ss_ed_nb_quantidade' => 1,
            'ss_ed_tx_motivo' => 'avaria',
            'ss_ed_tx_destino' => 'inspecao',
            'ss_ed_tx_status' => 'pendente',
            'ss_ed_tx_data_registro' => now(),
        ]);

        $tipo = EpiFiltroTipo::create([
            'ss_eft_tx_nome' => 'Filtro P3 Teste',
            'ss_eft_tx_ca' => '38569',
            'ss_eft_nb_vida_util_dias' => 90,
            'ss_eft_tx_status' => 'ativo',
        ]);

        EpiFiltro::create([
            'ss_ef_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ef_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_ef_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_ef_nb_tipo_id' => $tipo->ss_eft_nb_id,
            'ss_ef_tx_tipo_nome' => $tipo->ss_eft_tx_nome,
            'ss_ef_tx_ca' => $tipo->ss_eft_tx_ca,
            'ss_ef_tx_data_instalacao' => now()->toDateString(),
            'ss_ef_tx_vencimento' => now()->addDays(60)->toDateString(),
            'ss_ef_nb_quantidade' => 2,
            'ss_ef_tx_status' => 'ativo',
        ]);
    }

    public function test_todos_os_relatorios_pdf_sao_gerados(): void
    {
        $user = $this->usuarioComEpi();
        $this->criarDadosEpi();

        $relatorios = ['catalogo', 'estoque', 'movimentacoes', 'fardamento', 'entregas', 'devolucoes', 'filtros', 'filiais'];

        foreach ($relatorios as $relatorio) {
            $response = $this->actingAs($user)->get(route('epi.relatorio.pdf', [
                'relatorio' => $relatorio,
                'filial_id' => 0,
            ]));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');

            $this->assertStringStartsWith(
                '%PDF',
                (string) $response->getContent(),
                "O relatório {$relatorio} não retornou um PDF válido."
            );
        }
    }

    public function test_relatorio_inexistente_retorna_404(): void
    {
        $user = $this->usuarioComEpi();

        $this->actingAs($user)
            ->get(route('epi.relatorio.pdf', ['relatorio' => 'nao-existe']))
            ->assertNotFound();
    }

    public function test_pdf_respeita_filtros_do_catalogo(): void
    {
        $user = $this->usuarioComEpi();
        $this->criarDadosEpi();

        Epi::create([
            'ss_e_tx_grupo' => 'PROTEÇÃO RESPIRATÓRIA',
            'ss_e_tx_item' => 'Máscara Semifacial',
            'ss_e_tx_status' => 'ativo',
        ]);

        $response = $this->actingAs($user)->get(route('epi.relatorio.pdf', [
            'relatorio' => 'catalogo',
            'grupo_catalogo' => 'FARDAMENTO',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
