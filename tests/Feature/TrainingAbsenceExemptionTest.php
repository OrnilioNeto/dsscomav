<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\FolgaProgramacao;
use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Services\RelatorioAuditoriaService;
use App\Services\TrainingAbsenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrainingAbsenceExemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-19 10:00:00');
        config(['folgas.isencao_treinamento_desde' => '2026-11-19']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function criarUsuario(array $overrides = []): User
    {
        static $sequencia = 0;
        $sequencia++;

        return User::create(array_merge([
            'nome' => 'Motorista Ausência '.$sequencia,
            'cpf' => str_pad((string) (30000000000 + $sequencia), 11, '0', STR_PAD_LEFT),
            'email' => 'ausencia'.$sequencia.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ], $overrides));
    }

    private function criarDss(string $liberacao = '2026-11-16 08:00:00'): Training
    {
        return Training::create([
            'titulo' => 'DSS Semana Completa',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 1,
            'status' => 'ativo',
            'data_liberacao' => $liberacao,
        ]);
    }

    private function lancarDias(User $user, string $inicio, string $fim, string $tipo = 'atestado'): void
    {
        $cursor = Carbon::parse($inicio);
        $limite = Carbon::parse($fim);

        while ($cursor->lte($limite)) {
            FolgaDia::create([
                'user_id' => $user->id,
                'data' => $cursor->format('Y-m-d'),
                'tipo' => $tipo,
                'lancado_em' => now(),
            ]);

            $cursor->addDay();
        }
    }

    public function test_semana_do_conteudo_segue_a_semana_do_prazo(): void
    {
        $segunda = $this->criarDss('2026-11-16 08:00:00');

        $this->assertSame('2026-11-16', $segunda->prazoSemanaInicio()->toDateString());
        $this->assertSame('2026-11-22', $segunda->prazoSemanaFim()->toDateString());

        // Liberação no domingo: o prazo cai no domingo seguinte, então a
        // semana considerada é a de segunda a domingo seguinte.
        $domingo = $this->criarDss('2026-11-22 09:00:00');

        $this->assertSame('2026-11-23', $domingo->prazoSemanaInicio()->toDateString());
        $this->assertSame('2026-11-29', $domingo->prazoSemanaFim()->toDateString());
    }

    public function test_afastamento_parcial_nao_isenta_o_conteudo(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        // Segunda a quinta: ainda sobram sexta, sábado e domingo.
        $this->lancarDias($user, '2026-11-16', '2026-11-19');

        $this->assertTrue(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertTrue($user->fresh()->isEligibleForContent($training));
        $this->assertFalse(TrainingAbsenceService::isento($user->fresh(), $training));
    }

    public function test_afastamento_de_segunda_a_domingo_isenta_o_conteudo(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        $this->assertFalse(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertFalse($user->fresh()->isEligibleForContent($training));
        $this->assertTrue($user->fresh()->isExemptFromTraining($training));
        $this->assertTrue(TrainingAbsenceService::isento($user->fresh(), $training));
    }

    public function test_programacao_ativa_de_semana_completa_isenta(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        FolgaProgramacao::create([
            'user_id' => $user->id,
            'data_inicio' => '2026-11-16',
            'data_fim' => '2026-11-22',
            'tipo' => 'atestado',
            'status' => 'ativa',
        ]);

        $this->assertFalse(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertTrue($user->fresh()->isExemptFromTraining($training));
    }

    public function test_dia_de_trabalho_explicito_anula_programacao(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        FolgaProgramacao::create([
            'user_id' => $user->id,
            'data_inicio' => '2026-11-16',
            'data_fim' => '2026-11-22',
            'tipo' => 'folga',
            'status' => 'ativa',
        ]);

        FolgaDia::create([
            'user_id' => $user->id,
            'data' => '2026-11-19',
            'tipo' => 'trabalho',
            'lancado_em' => now(),
        ]);

        $this->assertTrue(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertTrue($user->fresh()->isEligibleForContent($training));
        $this->assertFalse(TrainingAbsenceService::isento($user->fresh(), $training));
    }

    public function test_programacao_cancelada_antes_do_fim_nao_isenta(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        FolgaProgramacao::create([
            'user_id' => $user->id,
            'data_inicio' => '2026-11-16',
            'data_fim' => '2026-11-22',
            'tipo' => 'atestado',
            'status' => 'cancelada',
            'cancelada_em' => '2026-11-18',
        ]);

        $this->assertTrue(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertTrue($user->fresh()->isEligibleForContent($training));
    }

    public function test_lancamento_antes_da_data_de_corte_nao_isenta(): void
    {
        config(['folgas.isencao_treinamento_desde' => '2026-11-20']);

        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        $this->assertTrue(
            User::query()->eligibleForContent($training)->whereKey($user->id)->exists()
        );
        $this->assertFalse(TrainingAbsenceService::isento($user->fresh(), $training));
    }

    public function test_player_bloqueia_afastamento_de_semana_completa(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        $this->actingAs($user)
            ->get("/treinamentos/{$training->id}/player")
            ->assertStatus(403)
            ->assertSee('afastamento de semana completa');
    }

    public function test_dashboard_nao_lista_conteudo_isento_por_afastamento(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('DSS Semana Completa');
    }

    public function test_api_oculta_e_bloqueia_conteudo_isento_por_afastamento(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/trainings')->assertOk();

        $todos = collect($response->json('data.disponiveis'))
            ->merge($response->json('data.bloqueados'))
            ->merge($response->json('data.direcionados'))
            ->merge($response->json('data.concluidos'));

        $this->assertNull($todos->firstWhere('id', $training->id));

        $this->getJson("/api/v1/trainings/{$training->id}")
            ->assertStatus(403)
            ->assertJson(['message' => 'Este treinamento está isento para este colaborador.']);
    }

    public function test_relatorio_auditoria_conta_afastado_por_semana_completa(): void
    {
        $training = $this->criarDss();
        $user = $this->criarUsuario();

        $this->lancarDias($user, '2026-11-16', '2026-11-22');

        // Outro usuário para o relatório ter um elegível "normal".
        $normal = $this->criarUsuario();
        $normal->forceFill(['created_at' => '2026-11-01 10:00:00'])->save();

        $roleSuper = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $viewer = $this->criarUsuario(['nome' => 'Gestor Auditoria', 'role_id' => $roleSuper->id]);
        $service = new RelatorioAuditoriaService(new Request(), $viewer);

        $stats = $service->statsConteudo($training);

        $this->assertSame(1, $stats['elegiveis']);
        $this->assertSame(1, $stats['afastados_semana']);
        $this->assertSame(0, $stats['afastados_liberacao']);
    }
}
