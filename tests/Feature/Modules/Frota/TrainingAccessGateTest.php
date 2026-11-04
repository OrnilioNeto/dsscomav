<?php

namespace Tests\Feature\Modules\Frota;

use App\Models\User;
use App\Modules\Frota\Models\FrotaLiberacao;
use App\Modules\Frota\Services\DriverPresenceService;
use App\Modules\Frota\Services\JornadaSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeAutotracClient;
use Tests\Support\FrotaTestHelpers;
use Tests\TestCase;

class TrainingAccessGateTest extends TestCase
{
    use FrotaTestHelpers;
    use RefreshDatabase;

    private FakeAutotracClient $fake;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-04 12:00:00');
        config(['autotrac.gate_enabled' => true]);
        $this->fake = new FakeAutotracClient;
        $this->configurarFrota();

        $this->app->instance(
            DriverPresenceService::class,
            new DriverPresenceService(new JornadaSyncService($this->fake))
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function driver(string $cpf = '10763021490'): User
    {
        $usuario = $this->criarUsuario($cpf, 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        return $usuario;
    }

    private function deixarOnline(string $cpf = '10763021490'): void
    {
        $this->fake->statusData = [[
            'DriverCode' => 100,
            'DriverName' => 'Motorista Teste',
            'DriverCPF' => $cpf,
            'VehicleCode' => 10,
            'VehicleName' => 'CAVALO 01',
            'VehicleAddress' => '123456',
        ]];

        $this->fake->logsData[10] = [[
            'LoginTime' => '2026-11-04T10:00:00-03:00',
            'LogoutTime' => '1970-01-01T00:00:00-03:00',
            'DriverCPF' => $cpf,
            'DriverName' => 'Motorista Teste',
            'VehicleName' => 'CAVALO 01',
            'VehicleLicensePlate' => 'ABC1D23',
        ]];
    }

    public function test_api_bloqueia_motorista_offline(): void
    {
        $usuario = $this->driver();

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/trainings')
            ->assertForbidden()
            ->assertJsonPath('code', 'DRIVER_TRAINING_BLOCKED');
    }

    public function test_api_libera_motorista_online(): void
    {
        $usuario = $this->driver();
        $this->deixarOnline();

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/trainings')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_api_nao_afeta_funcionario(): void
    {
        $usuario = $this->criarUsuario('11111111111', 'funcionario', $this->criarRole('usuario'));

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/trainings')
            ->assertOk();
    }

    public function test_api_libera_com_liberacao_manual(): void
    {
        $usuario = $this->driver();

        FrotaLiberacao::create([
            'user_id' => $usuario->id,
            'inicio' => now()->subHour(),
            'fim' => now()->addHour(),
            'motivo' => 'Ajuste operacional',
        ]);

        Sanctum::actingAs($usuario);

        $this->getJson('/api/v1/trainings')
            ->assertOk();
    }

    public function test_web_redireciona_motorista_offline_para_pagina_de_bloqueio(): void
    {
        $usuario = $this->driver();

        $this->actingAs($usuario)
            ->get('/treinamentos/1/player')
            ->assertRedirect(route('treinamentos.bloqueado'));
    }

    public function test_pagina_de_bloqueio_nao_entra_em_loop(): void
    {
        $usuario = $this->driver();

        $this->actingAs($usuario)
            ->get(route('treinamentos.bloqueado'))
            ->assertOk()
            ->assertSee('Treinamentos indisponíveis no momento');
    }

    public function test_dashboard_mostra_aviso_de_bloqueio_para_motorista_offline(): void
    {
        $usuario = $this->driver();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Treinamentos bloqueados no momento');
    }

    public function test_dashboard_nao_mostra_bloqueio_para_motorista_online(): void
    {
        $usuario = $this->driver();
        $this->deixarOnline();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Treinamentos bloqueados no momento');
    }

    public function test_login_da_api_devolve_estado_do_acesso_aos_treinamentos(): void
    {
        $this->driver();

        $this->postJson('/api/v1/auth/login', [
            'cpf' => '107.630.214-90',
            'password' => 'senha123',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user.acesso_treinamentos.liberado', false)
            ->assertJsonPath('user.acesso_treinamentos.origem', 'offline');
    }
}
