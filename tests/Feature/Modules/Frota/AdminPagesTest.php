<?php

namespace Tests\Feature\Modules\Frota;

use App\Models\User;
use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Models\FrotaJornada;
use App\Modules\Frota\Models\FrotaLiberacao;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Modules\Frota\Models\FrotaVeiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FrotaTestHelpers;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use FrotaTestHelpers;
    use RefreshDatabase;

    private function admin(): User
    {
        $role = $this->criarRole('admin', [
            'frota' => ['view' => true, 'edit' => true],
            'integracao_autotrac' => ['view' => true, 'edit' => true],
        ]);

        return $this->criarUsuario('11111111111', 'funcionario', $role, 'Gestor Teste');
    }

    public function test_dashboard_de_frota_renderiza(): void
    {
        $admin = $this->admin();
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->criarMotorista($usuario, [
            'status' => FrotaMotorista::STATUS_ONLINE,
            'verificado_em' => now(),
            'ultimo_login_em' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.frota.index'))
            ->assertOk()
            ->assertSee('Motoristas Online')
            ->assertSee('Motorista Teste');
    }

    public function test_paginas_de_veiculos_historico_e_liberacoes_renderizam(): void
    {
        $admin = $this->admin();
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $motorista = $this->criarMotorista($usuario);

        $veiculo = FrotaVeiculo::create([
            'code' => 10,
            'name' => 'CAVALO 01',
            'license_plate' => 'ABC1D23',
            'ativo' => true,
        ]);

        FrotaJornada::create([
            'frota_veiculo_id' => $veiculo->id,
            'frota_motorista_id' => $motorista->id,
            'driver_name' => 'Motorista Teste',
            'driver_cpf' => '10763021490',
            'login_time' => now()->subHours(2),
            'logout_time' => null,
            'aberta' => true,
        ]);

        FrotaLiberacao::create([
            'user_id' => $usuario->id,
            'liberado_por' => $admin->id,
            'motivo' => 'Teste de renderização',
            'inicio' => now()->subHour(),
            'fim' => now()->addHour(),
        ]);

        $this->actingAs($admin)->get(route('admin.frota.veiculos'))->assertOk()->assertSee('CAVALO 01');
        $this->actingAs($admin)->get(route('admin.frota.historico'))->assertOk()->assertSee('Motorista Teste');
        $this->actingAs($admin)->get(route('admin.frota.liberacoes.index'))->assertOk()->assertSee('Teste de renderização');
    }

    public function test_configuracao_renderiza(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.frota.configuracao'))
            ->assertOk()
            ->assertSee('Integração Autotrac');
    }

    public function test_salva_configuracao_e_preserva_credenciais_em_branco(): void
    {
        $admin = $this->admin();

        $payload = [
            'ativo' => '1',
            'base_url' => 'https://aapi3.autotrac-online.com.br/aticapi',
            'account_code' => '1234',
            'subscription_key' => 'chave-super-secreta',
            'usuario' => 'usuario@companhia',
            'senha' => 'senha-super-secreta',
            'ttl_segundos' => 60,
            'grace_minutos' => 30,
            'jornada_maxima_horas' => 12,
            'fail_mode' => 'grace',
            'tipos_motorista' => ['motorista', 'motorista_monitor'],
        ];

        $this->actingAs($admin)
            ->post(route('admin.frota.configuracao.salvar'), $payload)
            ->assertRedirect(route('admin.frota.configuracao'));

        $config = FrotaConfiguracao::first();
        $this->assertNotNull($config);
        $this->assertTrue($config->ativo);
        $this->assertSame('chave-super-secreta', $config->subscription_key);
        $this->assertSame('senha-super-secreta', $config->senha);

        $payload['subscription_key'] = '';
        $payload['senha'] = '';

        $this->actingAs($admin)
            ->post(route('admin.frota.configuracao.salvar'), $payload)
            ->assertRedirect(route('admin.frota.configuracao'));

        $config->refresh();
        $this->assertSame('chave-super-secreta', $config->subscription_key);
        $this->assertSame('senha-super-secreta', $config->senha);
    }

    public function test_usuario_sem_permissao_nao_acessa_painel_da_frota(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->actingAs($usuario)
            ->get(route('admin.frota.index'))
            ->assertForbidden();
    }
}
