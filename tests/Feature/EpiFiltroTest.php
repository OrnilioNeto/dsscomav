<?php

namespace Tests\Feature;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\EpiFiltro;
use App\Models\EpiFiltroTipo;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EpiFiltroTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioComEpi(string $cpf = '99999999999'): User
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
            'cpf' => $cpf,
            'email' => 'gestor-epi@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarEntregaMascara(): EpiEntrega
    {
        $epi = Epi::create([
            'ss_e_tx_grupo' => 'PROTEÇÃO RESPIRATÓRIA',
            'ss_e_tx_subgrupo' => 'Respiradores e Máscaras',
            'ss_e_tx_item' => 'Máscara Semifacial Teste',
            'ss_e_tx_status' => 'ativo',
            'ss_e_nb_vida_util_dias' => 365,
        ]);

        $colaborador = EpiColaborador::create([
            'ss_c_tx_nome' => 'Colaborador Máscara',
            'ss_c_tx_cpf' => '11122233344',
            'ss_c_tx_status' => 'ativo',
        ]);

        return EpiEntrega::create([
            'ss_e_nb_colaborador_id' => $colaborador->ss_c_nb_id,
            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
            'ss_e_nb_empresa_id' => 0,
            'ss_e_tx_data_entrega' => now()->toDateString(),
            'ss_e_nb_quantidade' => 1,
            'ss_e_tx_status' => 'ativo',
        ]);
    }

    private function criarTipo(): EpiFiltroTipo
    {
        return EpiFiltroTipo::create([
            'ss_eft_tx_nome' => 'Filtro P3 Teste',
            'ss_eft_tx_ca' => '38569',
            'ss_eft_nb_vida_util_dias' => 90,
            'ss_eft_tx_status' => 'ativo',
        ]);
    }

    private function criarFiltro(EpiEntrega $entrega, EpiFiltroTipo $tipo, array $override = []): EpiFiltro
    {
        return EpiFiltro::create(array_merge([
            'ss_ef_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ef_nb_colaborador_id' => $entrega->ss_e_nb_colaborador_id,
            'ss_ef_nb_epi_id' => $entrega->ss_e_nb_epi_id,
            'ss_ef_nb_tipo_id' => $tipo->ss_eft_nb_id,
            'ss_ef_tx_tipo_nome' => $tipo->ss_eft_tx_nome,
            'ss_ef_tx_ca' => $tipo->ss_eft_tx_ca,
            'ss_ef_nb_vida_util_dias' => $tipo->ss_eft_nb_vida_util_dias,
            'ss_ef_nb_quantidade' => 2,
            'ss_ef_tx_data_instalacao' => now()->toDateString(),
            'ss_ef_tx_vencimento' => now()->addDays(90)->toDateString(),
            'ss_ef_tx_status' => 'ativo',
            'ss_ef_tx_requer_assinatura' => false,
            'ss_ef_tx_status_assinatura' => 'assinada',
            'ss_ef_tx_data_assinatura' => now(),
        ], $override));
    }

    public function test_gestor_vincula_filtro_com_assinatura(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();

        $this->actingAs($user)->post(route('epi.filtros.store'), [
            'ss_ef_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ef_nb_tipo_id' => $tipo->ss_eft_nb_id,
            'ss_ef_nb_quantidade' => 2,
            'ss_ef_tx_data_instalacao' => now()->toDateString(),
            'ss_ef_tx_vencimento' => now()->addDays(90)->toDateString(),
            'ss_ef_tx_assinatura' => 'data:image/png;base64,AAAA',
        ])->assertRedirect();

        $filtro = EpiFiltro::latest('ss_ef_nb_id')->firstOrFail();

        $this->assertSame('ativo', $filtro->ss_ef_tx_status);
        $this->assertSame('assinada', $filtro->ss_ef_tx_status_assinatura);
        $this->assertFalse((bool) $filtro->ss_ef_tx_requer_assinatura);
        $this->assertSame('Filtro P3 Teste', $filtro->ss_ef_tx_tipo_nome);
        $this->assertSame(2, (int) $filtro->ss_ef_nb_quantidade);

        // Filtro não movimenta estoque e não cria devolução
        $this->assertDatabaseCount('ss_epi_estoque', 0);
        $this->assertDatabaseCount('ss_epi_devolucao', 0);
    }

    public function test_vinculo_sem_assinatura_fica_pendente_e_colaborador_assina(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();

        $this->actingAs($user)->post(route('epi.filtros.store'), [
            'ss_ef_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ef_nb_tipo_id' => $tipo->ss_eft_nb_id,
            'ss_ef_nb_quantidade' => 2,
            'ss_ef_tx_data_instalacao' => now()->toDateString(),
            'ss_ef_tx_vencimento' => now()->addDays(90)->toDateString(),
        ])->assertRedirect();

        $filtro = EpiFiltro::latest('ss_ef_nb_id')->firstOrFail();
        $this->assertSame('pendente', $filtro->ss_ef_tx_status_assinatura);
        $this->assertTrue((bool) $filtro->ss_ef_tx_requer_assinatura);
        $this->assertNotEmpty($filtro->ss_ef_tx_grupo_assinatura);

        // Colaborador (com CPF correspondente) assina o filtro pendente
        $colaboradorUser = User::create([
            'nome' => 'Colaborador Máscara',
            'cpf' => '11122233344',
            'email' => 'colaborador-mascara@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
        ]);

        $this->actingAs($colaboradorUser)->post(route('epi.assinaturas.filtro.assinar', $filtro->ss_ef_nb_id), [
            'ss_ef_tx_assinatura' => 'data:image/png;base64,BBBB',
        ])->assertRedirect(route('epi.assinaturas'));

        $filtroAtualizado = $filtro->fresh();
        $this->assertSame('assinada', $filtroAtualizado->ss_ef_tx_status_assinatura);
        $this->assertNotNull($filtroAtualizado->ss_ef_tx_data_assinatura);
    }

    public function test_descarte_encerra_apenas_o_filtro_sem_alterar_a_mascara(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $filtro = $this->criarFiltro($entrega, $this->criarTipo());

        $this->actingAs($user)->post(route('epi.filtros.descartar', $filtro->ss_ef_nb_id), [
            'ss_ef_tx_motivo_descarte' => 'vencido',
            'ss_ef_tx_data_descarte' => now()->toDateString(),
            'ss_ef_tx_observacao' => 'Descartado no teste',
        ])->assertRedirect();

        $filtroAtualizado = $filtro->fresh();
        $this->assertSame('descartado', $filtroAtualizado->ss_ef_tx_status);
        $this->assertSame('vencido', $filtroAtualizado->ss_ef_tx_motivo_descarte);
        $this->assertNotNull($filtroAtualizado->ss_ef_tx_data_descarte);

        // A máscara (entrega) permanece intacta e nada vai para estoque/devolução
        $this->assertSame('ativo', $entrega->fresh()->ss_e_tx_status);
        $this->assertDatabaseCount('ss_epi_estoque', 0);
        $this->assertDatabaseCount('ss_epi_devolucao', 0);
    }

    public function test_editar_vencimento_do_filtro(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $filtro = $this->criarFiltro($entrega, $this->criarTipo());
        $novaData = now()->addDays(120)->toDateString();

        $this->actingAs($user)->post(route('epi.filtros.vencimento', $filtro->ss_ef_nb_id), [
            'ss_ef_tx_vencimento' => $novaData,
        ])->assertRedirect();

        $this->assertSame($novaData, $filtro->fresh()->ss_ef_tx_vencimento);
    }

    public function test_excluir_lancamento_do_filtro(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $filtro = $this->criarFiltro($entrega, $this->criarTipo());

        $this->actingAs($user)
            ->delete(route('epi.filtros.destroy', $filtro->ss_ef_nb_id))
            ->assertRedirect();

        $this->assertDatabaseMissing('ss_epi_filtro', ['ss_ef_nb_id' => $filtro->ss_ef_nb_id]);
        $this->assertSame('ativo', $entrega->fresh()->ss_e_tx_status);
    }

    public function test_tipos_de_filtro_crud_e_bloqueio_de_exclusao_em_uso(): void
    {
        $user = $this->usuarioComEpi();

        $this->actingAs($user)->post(route('epi.filtros.tipos.store'), [
            'ss_eft_tx_nome' => 'Filtro VO',
            'ss_eft_tx_ca' => '12345',
            'ss_eft_nb_vida_util_dias' => 60,
        ])->assertRedirect();

        $tipo = EpiFiltroTipo::latest('ss_eft_nb_id')->firstOrFail();
        $this->assertSame('ativo', $tipo->ss_eft_tx_status);

        // Inativar
        $this->actingAs($user)->post(route('epi.filtros.tipos.toggle', $tipo->ss_eft_nb_id))->assertRedirect();
        $this->assertSame('inativo', $tipo->fresh()->ss_eft_tx_status);

        // Não deve aparecer como ativo no mini-catálogo
        $this->assertSame(0, EpiFiltroTipo::ativos()->where('ss_eft_nb_id', $tipo->ss_eft_nb_id)->count());

        // Reativar e usar em um lançamento
        $this->actingAs($user)->post(route('epi.filtros.tipos.toggle', $tipo->ss_eft_nb_id))->assertRedirect();
        $entrega = $this->criarEntregaMascara();
        $filtro = $this->criarFiltro($entrega, $tipo->fresh());

        // Exclusão bloqueada quando o tipo está em uso
        $this->actingAs($user)
            ->deleteJson(route('epi.filtros.tipos.destroy', $tipo->ss_eft_nb_id))
            ->assertStatus(422);
        $this->assertDatabaseHas('ss_epi_filtro_tipo', ['ss_eft_nb_id' => $tipo->ss_eft_nb_id]);

        // Sem uso, a exclusão funciona
        $filtro->delete();
        $this->actingAs($user)
            ->delete(route('epi.filtros.tipos.destroy', $tipo->ss_eft_nb_id))
            ->assertRedirect();
        $this->assertDatabaseMissing('ss_epi_filtro_tipo', ['ss_eft_nb_id' => $tipo->ss_eft_nb_id]);
    }

    public function test_ficha_do_colaborador_lista_filtros(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();
        $this->criarFiltro($entrega, $tipo);

        $this->actingAs($user)
            ->get(route('epi.ficha', $entrega->ss_e_nb_colaborador_id))
            ->assertOk()
            ->assertSee('Filtros de Reposição')
            ->assertSee('Filtro P3 Teste');
    }

    public function test_paginas_do_modulo_renderizam_com_filtros(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();
        $this->criarFiltro($entrega, $tipo, [
            'ss_ef_tx_requer_assinatura' => true,
            'ss_ef_tx_status_assinatura' => 'pendente',
            'ss_ef_tx_assinatura' => null,
            'ss_ef_tx_data_assinatura' => null,
            'ss_ef_tx_grupo_assinatura' => (string) Str::uuid(),
        ]);

        // Página principal do módulo (aba Filtros de Máscara + alertas)
        $this->actingAs($user)
            ->get(route('epi.index'))
            ->assertOk()
            ->assertSee('Filtros de Máscara')
            ->assertSee('Filtro P3 Teste');

        // Página de assinaturas do colaborador (seção de filtros pendentes)
        $colaboradorUser = User::create([
            'nome' => 'Colaborador Máscara',
            'cpf' => '11122233344',
            'email' => 'colaborador-assinaturas@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
        ]);

        $this->actingAs($colaboradorUser)
            ->get(route('epi.assinaturas'))
            ->assertOk()
            ->assertSee('Filtros de Máscara');

        // Gestão de assinaturas (seção de filtros pendentes)
        $this->actingAs($user)
            ->get(route('epi.gestao-assinaturas'))
            ->assertOk()
            ->assertSee('Filtro P3 Teste');
    }

    public function test_ficha_publica_lista_filtros(): void
    {
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();
        $this->criarFiltro($entrega, $tipo);

        $usuario = User::create([
            'nome' => 'Colaborador Máscara',
            'cpf' => '11122233344',
            'email' => 'colaborador-ficha-publica@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'qrcode_token' => Str::random(32),
        ]);

        $this->get(route('ficha.publica', $usuario->qrcode_token))
            ->assertOk()
            ->assertSee('Filtros de máscara instalados')
            ->assertSee('Filtro P3 Teste');
    }

    public function test_colaborador_duplicado_nao_gera_opcoes_repetidas_no_modulo(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();

        // Registro original (com entrega) ganha matrícula identificável
        $original = EpiColaborador::findOrFail($entrega->ss_e_nb_colaborador_id);
        $original->update(['ss_c_tx_matricula' => 'MAT-ORIG']);

        // Duplicado legado: mesmo CPF, sem vínculos
        EpiColaborador::create([
            'ss_c_tx_nome' => $original->ss_c_tx_nome,
            'ss_c_tx_cpf' => $original->ss_c_tx_cpf,
            'ss_c_tx_matricula' => 'MAT-DUP',
            'ss_c_tx_status' => 'ativo',
        ]);

        $this->actingAs($user)
            ->get(route('epi.index'))
            ->assertOk()
            ->assertSee('MAT-ORIG')
            ->assertDontSee('MAT-DUP');
    }

    public function test_alerta_de_validade_dos_filtros_cobre_janela_de_30_dias(): void
    {
        $user = $this->usuarioComEpi();
        $entrega = $this->criarEntregaMascara();
        $tipo = $this->criarTipo();

        // 20 dias: dentro da janela de 30
        $this->criarFiltro($entrega, $tipo, [
            'ss_ef_tx_vencimento' => now()->addDays(20)->toDateString(),
        ]);

        // Vencido entra no alerta
        $this->criarFiltro($entrega, $tipo, [
            'ss_ef_tx_vencimento' => now()->subDays(3)->toDateString(),
        ]);

        // Mais de 30 dias fica fora do alerta
        $this->criarFiltro($entrega, $tipo, [
            'ss_ef_tx_vencimento' => now()->addDays(45)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('epi.index'))
            ->assertOk()
            ->assertViewHas('filtrosVencidosCount', 1)
            ->assertViewHas('filtrosVencendoCount', 1)
            ->assertViewHas('filtrosAlerta', fn ($filtros) => $filtros->count() === 2)
            ->assertSee('vencendo em 30 dias');
    }

    public function test_usuario_sem_permissao_nao_acessa_filtros(): void
    {
        $semPermissao = User::create([
            'nome' => 'Sem Permissão',
            'cpf' => '88888888888',
            'email' => 'sem-permissao@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
        ]);

        $this->actingAs($semPermissao)->post(route('epi.filtros.store'), [])->assertForbidden();
        $this->actingAs($semPermissao)->post(route('epi.filtros.tipos.store'), [])->assertForbidden();
    }
}
