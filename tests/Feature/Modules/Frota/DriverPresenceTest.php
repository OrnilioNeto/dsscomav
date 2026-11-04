<?php

namespace Tests\Feature\Modules\Frota;

use App\Modules\Frota\Models\FrotaLiberacao;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Modules\Frota\Services\DriverPresenceService;
use App\Modules\Frota\Services\JornadaSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAutotracClient;
use Tests\Support\FrotaTestHelpers;
use Tests\TestCase;

class DriverPresenceTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function servico(): DriverPresenceService
    {
        return new DriverPresenceService(new JornadaSyncService($this->fake));
    }

    private function statusOnline(string $cpf, int $driverCode = 100, int $vehicleCode = 10): void
    {
        $this->fake->statusData = [[
            'DriverCode' => $driverCode,
            'DriverName' => 'Motorista Teste',
            'DriverCPF' => $cpf,
            'VehicleCode' => $vehicleCode,
            'VehicleName' => 'CAVALO 01',
            'VehicleAddress' => '123456',
            'ExcessJourney' => false,
            'ExcessExtendedJourney' => false,
        ]];
    }

    private function logAberto(int $vehicleCode, string $cpf, string $loginTime): void
    {
        $this->fake->logsData[$vehicleCode] = [[
            'LoginTime' => $loginTime,
            'LogoutTime' => '1970-01-01T00:00:00-03:00',
            'DriverName' => 'Motorista Teste',
            'DriverCPF' => $cpf,
            'VehicleName' => 'CAVALO 01',
            'VehicleAddress' => '123456',
            'VehicleLicensePlate' => 'ABC1D23',
        ]];
    }

    public function test_motorista_online_recebe_acesso_pela_autotrac(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        $this->statusOnline('10763021490');
        $this->logAberto(10, '10763021490', '2026-11-04T10:00:00-03:00');

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('autotrac', $acesso['origem']);
        $this->assertSame(2.0, $acesso['horas_sessao']);
    }

    public function test_motorista_offline_e_bloqueado(): void
    {
        $usuario = $this->criarUsuario('03318717428', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario, ['code' => 200]);

        $this->fake->statusData = [];

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertFalse($acesso['liberado']);
        $this->assertSame('offline', $acesso['origem']);
    }

    public function test_perfil_nao_motorista_nao_e_afetado(): void
    {
        $usuario = $this->criarUsuario('11111111111', 'funcionario', $this->criarRole('usuario'));

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('nao_motorista', $acesso['origem']);
        $this->assertCount(0, $this->fake->statusCalls);
    }

    public function test_gestor_nao_e_afetado(): void
    {
        $gestor = $this->criarUsuario('22222222222', 'motorista', $this->criarRole('admin'));

        $acesso = $this->servico()->avaliar($gestor);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('gestor', $acesso['origem']);
    }

    public function test_liberacao_manual_libera_mesmo_offline(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        FrotaLiberacao::create([
            'user_id' => $usuario->id,
            'liberado_por' => null,
            'motivo' => 'Falha no rastreador',
            'inicio' => now()->subHour(),
            'fim' => now()->addHours(2),
        ]);

        $this->fake->statusData = [];

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('manual', $acesso['origem']);
        $this->assertNotNull($acesso['liberacao']);
    }

    public function test_liberacao_expirada_nao_libera(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        FrotaLiberacao::create([
            'user_id' => $usuario->id,
            'inicio' => now()->subDays(2),
            'fim' => now()->subDay(),
        ]);

        $this->fake->statusData = [];

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertFalse($acesso['liberado']);
        $this->assertSame('offline', $acesso['origem']);
    }

    public function test_tolerancia_mantem_acesso_por_30_minutos(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario, [
            'status' => FrotaMotorista::STATUS_ONLINE,
            'verificado_em' => now()->subMinutes(5),
            'ultimo_online_em' => now()->subMinutes(10),
        ]);

        $this->fake->statusError = 'Timeout';

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('grace', $acesso['origem']);
    }

    public function test_sem_tolerancia_bloqueia_quando_integracao_falha(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario, [
            'status' => FrotaMotorista::STATUS_ONLINE,
            'verificado_em' => now()->subMinutes(5),
            'ultimo_online_em' => now()->subMinutes(45),
        ]);

        $this->fake->statusError = 'Timeout';

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertFalse($acesso['liberado']);
        $this->assertSame('integracao_indisponivel', $acesso['origem']);
    }

    public function test_jornada_acima_de_12_horas_bloqueia(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        $this->statusOnline('10763021490');
        $this->logAberto(10, '10763021490', '2026-11-03T22:00:00-03:00');

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertFalse($acesso['liberado']);
        $this->assertSame('jornada_excedida', $acesso['origem']);
        $this->assertGreaterThanOrEqual(12, $acesso['horas_sessao']);
    }

    public function test_motorista_sem_cadastro_e_bloqueado(): void
    {
        $usuario = $this->criarUsuario('99999999999', 'motorista', $this->criarRole('usuario'));

        $this->fake->statusData = [];

        $acesso = $this->servico()->avaliar($usuario);

        $this->assertFalse($acesso['liberado']);
        $this->assertSame('sem_cadastro', $acesso['origem']);
    }

    public function test_verificacao_desativada_libera_sem_chamar_a_api(): void
    {
        config(['autotrac.gate_enabled' => false]);

        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));
        $this->criarMotorista($usuario);

        $this->fake->statusData = []; // offline

        $acesso = $this->servico()->avaliar($usuario, true);

        $this->assertTrue($acesso['liberado']);
        $this->assertSame('desativado', $acesso['origem']);
        $this->assertCount(0, $this->fake->statusCalls);
        $this->assertCount(0, $this->fake->logsCalls);
    }

    public function test_sincronizacao_cria_motorista_e_vincula_pelo_cpf(): void
    {
        $usuario = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->fake->motoristasData = [[
            'Code' => 100,
            'Name' => 'Motorista Teste',
            'DriverCPF' => '107.630.214-90',
        ]];

        $resultado = (new JornadaSyncService($this->fake))->sincronizarMotoristas();

        $this->assertTrue($resultado['ok']);
        $this->assertSame(1, $resultado['processados']);

        $motorista = FrotaMotorista::where('code', 100)->first();
        $this->assertNotNull($motorista);
        $this->assertSame($usuario->id, $motorista->user_id);
        $this->assertSame('10763021490', $motorista->cpf);
    }
}
