<?php

namespace Tests\Feature\Modules\LembretesWhatsapp;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Modules\LembretesWhatsapp\Services\PendingTrainingService;
use App\Modules\LembretesWhatsapp\Services\WhatsappGatewayClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private int $sequencia = 0;

    private function criarRole(string $nome, array $permissoes = []): Role
    {
        $role = Role::create(['nome' => $nome, 'descricao' => $nome]);

        foreach ($permissoes as $module => $flags) {
            RolePermission::create([
                'role_id' => $role->id,
                'module' => $module,
                'can_view' => $flags['view'] ?? true,
                'can_edit' => $flags['edit'] ?? false,
            ]);
        }

        return $role;
    }

    private function criarUsuario(array $overrides = []): User
    {
        $this->sequencia++;

        $user = User::create(array_merge([
            'nome' => 'Usuário Teste '.$this->sequencia,
            'cpf' => str_pad((string) (10000000000 + $this->sequencia), 11, '0', STR_PAD_LEFT),
            'email' => 'usuario'.$this->sequencia.'@teste.com',
            'password' => bcrypt('senha123'),
            'telefone' => '11999999999',
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ], $overrides));

        $user->forceFill(['created_at' => '2026-09-01 10:00:00'])->save();

        return $user->fresh();
    }

    private function criarTreinamento(array $overrides = []): Training
    {
        return Training::create(array_merge([
            'titulo' => 'DSS Teste WhatsApp',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 10,
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
            'data_liberacao' => '2026-09-28 08:00:00',
        ], $overrides));
    }

    private function criarGestor(): User
    {
        $role = $this->criarRole('admin', [
            'lembretes_whatsapp' => ['view' => true, 'edit' => true],
        ]);

        return $this->criarUsuario(['role_id' => $role->id, 'tipo_usuario' => 'funcionario']);
    }

    /**
     * Substitui o transporte HTTP do gateway por respostas simuladas,
     * mantendo a montagem de URL/payload real do cliente.
     */
    private function fakeGateway(array $sendBody, int $sendStatus = 200, string $sessionStatus = 'CONNECTED'): WhatsappGatewayClient
    {
        $fake = new class extends WhatsappGatewayClient
        {
            /** @var array<int, array{method: string, path: string, payload: array<string, mixed>}> */
            public array $requests = [];

            /** @var array<string, mixed> */
            public array $sendBody = [];

            /** @var array<int, array{number: string, exists: bool, jid: ?string}> */
            public array $checkResults = [];

            public int $sendStatus = 200;

            public string $sessionStatus = 'CONNECTED';

            protected function request(string $method, string $path, array $payload = []): array
            {
                $this->requests[] = ['method' => $method, 'path' => $path, 'payload' => $payload];

                if (str_contains($path, '/api/sessions/')) {
                    return [
                        'status' => 200,
                        'body' => ['status' => true, 'data' => ['status' => $this->sessionStatus]],
                        'error' => null,
                    ];
                }

                if (str_contains($path, '/check')) {
                    $numbers = $payload['numbers'] ?? [];
                    $results = $this->checkResults !== []
                        ? $this->checkResults
                        : array_map(fn ($number) => [
                            'number' => $number,
                            'exists' => true,
                            'jid' => $number.'@s.whatsapp.net',
                        ], $numbers);

                    return [
                        'status' => 200,
                        'body' => ['status' => true, 'data' => ['results' => $results]],
                        'error' => null,
                    ];
                }

                return ['status' => $this->sendStatus, 'body' => $this->sendBody, 'error' => null];
            }
        };

        $fake->sendBody = $sendBody;
        $fake->sendStatus = $sendStatus;
        $fake->sessionStatus = $sessionStatus;

        $this->app->instance(WhatsappGatewayClient::class, $fake);

        return $fake;
    }

    public function test_rotas_do_modulo_estao_registradas(): void
    {
        $this->assertTrue(Route::has('admin.lembretes.index'));
        $this->assertTrue(Route::has('admin.lembretes.disparo'));
        $this->assertTrue(Route::has('admin.lembretes.store'));
        $this->assertTrue(Route::has('admin.lembretes.cancelar'));
        $this->assertTrue(Route::has('admin.lembretes.sessao'));
        $this->assertFalse(Route::has('admin.lembretes.pendentes'));
    }

    public function test_tela_disparo_lista_pendentes_do_treinamento(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();

        $naoIniciado = $this->criarUsuario();
        $pendente = $this->criarUsuario();
        $concluido = $this->criarUsuario();

        UserProgress::create([
            'user_id' => $pendente->id,
            'training_id' => $training->id,
            'concluido' => false,
            'porcentagem_assistida' => 40,
        ]);

        UserProgress::create([
            'user_id' => $concluido->id,
            'training_id' => $training->id,
            'concluido' => true,
            'porcentagem_assistida' => 100,
        ]);

        $response = $this->actingAs($gestor)->get(route('admin.lembretes.disparo', [
            'training_id' => $training->id,
        ]));

        $response->assertOk()
            ->assertSee($training->titulo)
            ->assertSee($naoIniciado->nome)
            ->assertSee($pendente->nome)
            ->assertDontSee($concluido->nome)
            ->assertSee('Elegíveis')
            ->assertSee('Já concluíram');
    }

    public function test_tela_disparo_panorama_lista_todos_os_treinamentos(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        $comPendentes = $this->criarTreinamento(['titulo' => 'Treinamento Com Pendentes']);
        $semProgresso = $this->criarTreinamento(['titulo' => 'Treinamento Sem Progresso']);
        $gestor = $this->criarGestor();

        $this->criarUsuario();

        UserProgress::create([
            'user_id' => $this->criarUsuario()->id,
            'training_id' => $comPendentes->id,
            'concluido' => true,
            'porcentagem_assistida' => 100,
        ]);

        $response = $this->actingAs($gestor)->get(route('admin.lembretes.disparo'));

        $response->assertOk()
            ->assertSee('Panorama por treinamento')
            ->assertSee($comPendentes->titulo)
            ->assertSee($semProgresso->titulo)
            ->assertSee('Lembrar');
    }

    public function test_phone_to_jid_normaliza_numeros_brasileiros(): void
    {
        $this->assertSame('5511999999999@s.whatsapp.net', phone_to_jid('(11) 99999-9999'));
        $this->assertSame('5511999999999@s.whatsapp.net', phone_to_jid('11999999999'));
        $this->assertSame('5511999999999@s.whatsapp.net', phone_to_jid('+55 11 99999-9999'));
        $this->assertSame('551133334444@s.whatsapp.net', phone_to_jid('(11) 3333-4444'));
        $this->assertSame('5551999999999@s.whatsapp.net', phone_to_jid('(51) 99999-9999'));

        $this->assertNull(phone_to_jid(null));
        $this->assertNull(phone_to_jid(''));
        $this->assertNull(phone_to_jid('119999999'));
        $this->assertNull(phone_to_jid('11123456789'));
    }

    public function test_pendentes_exclui_concluidos_e_inelegiveis(): void
    {
        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();

        $naoIniciado = $this->criarUsuario();
        $pendente = $this->criarUsuario();
        $concluido = $this->criarUsuario();

        UserProgress::create([
            'user_id' => $pendente->id,
            'training_id' => $training->id,
            'concluido' => false,
            'porcentagem_assistida' => 40,
        ]);

        UserProgress::create([
            'user_id' => $concluido->id,
            'training_id' => $training->id,
            'concluido' => true,
            'porcentagem_assistida' => 100,
        ]);

        $pendentes = app(PendingTrainingService::class)->pendentes($training, $gestor);

        $ids = $pendentes->pluck('id')->all();

        $this->assertContains($naoIniciado->id, $ids);
        $this->assertContains($pendente->id, $ids);
        $this->assertNotContains($concluido->id, $ids);
    }

    public function test_store_enfileira_com_escalonamento_e_registra_sem_telefone(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();

        $primeiro = $this->criarUsuario();
        $segundo = $this->criarUsuario(['telefone' => '(11) 98888-7777']);
        $semTelefone = $this->criarUsuario(['telefone' => null]);

        $response = $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$primeiro->id, $segundo->id, $semTelefone->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'enfileirados' => 2, 'pulados' => 1]);

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $primeiro->id,
            'status' => TrainingReminder::STATUS_FILA,
            'jid' => '5511999999999@s.whatsapp.net',
        ]);

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $semTelefone->id,
            'status' => TrainingReminder::STATUS_PULADO,
        ]);

        $r1 = TrainingReminder::where('user_id', $primeiro->id)->firstOrFail();
        $r2 = TrainingReminder::where('user_id', $segundo->id)->firstOrFail();

        $this->assertStringContainsString('Usuário Teste', $r1->mensagem);
        $this->assertStringContainsString('DSS Teste WhatsApp', $r2->mensagem);
        $this->assertTrue($r2->agendado_para->gt($r1->agendado_para), 'O segundo envio deve ser agendado após o primeiro.');
        $this->assertTrue($r1->agendado_para->between(
            Carbon::parse('2026-10-05 09:00:00'),
            Carbon::parse('2026-10-05 09:00:30')
        ));
    }

    public function test_store_bloqueia_usuario_sem_permissao_de_edicao(): void
    {
        $training = $this->criarTreinamento();
        $role = $this->criarRole('admin', ['lembretes_whatsapp' => ['view' => true, 'edit' => false]]);
        $gestor = $this->criarUsuario(['role_id' => $role->id]);
        $alvo = $this->criarUsuario();

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ])->assertForbidden();
    }

    public function test_store_redireciona_com_flash_de_sucesso(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $response = $this->actingAs($gestor)->post(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ]);

        $response->assertRedirect(route('admin.lembretes.disparo', ['training_id' => $training->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $alvo->id,
            'status' => TrainingReminder::STATUS_FILA,
        ]);
    }

    public function test_store_recusa_treinamento_nao_liberado(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        $training = $this->criarTreinamento(['data_liberacao' => '2026-12-01 08:00:00']);
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ])->assertStatus(422)->assertJsonValidationErrors('training_id');
    }

    public function test_enfileira_usando_jid_canonico_do_whatsapp(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
        ]);

        $fake = $this->fakeGateway(['status' => true]);
        $fake->checkResults = [
            ['number' => '5584994017097', 'exists' => true, 'jid' => '558494017097@s.whatsapp.net'],
        ];

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario(['telefone' => '84 994017097']);

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ])->assertOk()->assertJson(['enfileirados' => 1, 'pulados' => 0]);

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $alvo->id,
            'jid' => '558494017097@s.whatsapp.net',
            'status' => TrainingReminder::STATUS_FILA,
        ]);

        $this->assertNotNull(
            collect($fake->requests)->first(fn (array $request) => str_contains($request['path'], '/check')),
            'O enfileiramento deveria validar os números no WhatsApp.'
        );
    }

    public function test_enfileira_pula_numero_nao_encontrado_no_whatsapp(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
        ]);

        $fake = $this->fakeGateway(['status' => true]);
        $fake->checkResults = [
            ['number' => '5511999999999', 'exists' => false, 'jid' => null],
        ];

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
        ])->assertOk()->assertJson(['enfileirados' => 0, 'pulados' => 1]);

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $alvo->id,
            'status' => TrainingReminder::STATUS_PULADO,
        ]);
    }

    public function test_tela_disparo_bloqueia_sem_permissao_de_visualizacao(): void
    {
        $role = $this->criarRole('admin', ['lembretes_whatsapp' => ['view' => false, 'edit' => true]]);
        $gestor = $this->criarUsuario(['role_id' => $role->id]);

        $this->actingAs($gestor)
            ->get(route('admin.lembretes.disparo'))
            ->assertForbidden();
    }

    public function test_enviar_agora_dispara_imediatamente(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
            'whatsapp.session_id' => 'dss',
        ]);

        $fake = $this->fakeGateway(['status' => true, 'data' => ['key' => ['id' => 'ABC123']]]);

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $response = $this->actingAs($gestor)->post(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
            'enviar_agora' => '1',
        ]);

        $response->assertRedirect(route('admin.lembretes.disparo', ['training_id' => $training->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('training_reminders', [
            'user_id' => $alvo->id,
            'status' => TrainingReminder::STATUS_ENVIADO,
            'gateway_message_id' => 'ABC123',
        ]);

        $envio = collect($fake->requests)->first(function (array $request) {
            return str_contains($request['path'], '/api/messages/') && str_ends_with($request['path'], '/send');
        });

        $this->assertNotNull($envio, 'O envio imediato deveria chamar o gateway na mesma requisição.');
    }

    public function test_enviar_agora_exige_sessao_conectada_e_nao_enfileira(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
        ]);

        $this->fakeGateway(['status' => true], 200, 'DISCONNECTED');

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
            'enviar_agora' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('enviar_agora');

        $this->assertDatabaseCount('training_reminders', 0);
    }

    public function test_enviar_agora_exige_integracao_ativa(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config(['whatsapp.enabled' => false]);

        $training = $this->criarTreinamento();
        $gestor = $this->criarGestor();
        $alvo = $this->criarUsuario();

        $this->actingAs($gestor)->postJson(route('admin.lembretes.store'), [
            'training_id' => $training->id,
            'user_ids' => [$alvo->id],
            'mensagem' => 'Olá, {nome}! Falta finalizar o treinamento "{treinamento}".',
            'enviar_agora' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('enviar_agora');

        $this->assertDatabaseCount('training_reminders', 0);
    }

    public function test_comando_diagnostico_mostra_status_da_sessao(): void
    {
        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'wag_chave-teste',
            'whatsapp.session_id' => 'dss',
        ]);

        $this->fakeGateway(['status' => true]);

        $this->artisan('lembretes:diagnostico')
            ->expectsOutputToContain('Status da sessão: CONNECTED')
            ->expectsOutputToContain('Fila:')
            ->assertSuccessful();
    }

    public function test_comando_diagnostico_avisa_gateway_nao_configurado(): void
    {
        config([
            'whatsapp.enabled' => false,
            'whatsapp.api_key' => '',
        ]);

        $this->artisan('lembretes:diagnostico')
            ->expectsOutputToContain('Gateway não configurado')
            ->assertSuccessful();
    }

    public function test_comando_envia_mensagem_e_atualiza_status(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
            'whatsapp.session_id' => 'dss',
        ]);

        $fake = $this->fakeGateway(['status' => true, 'data' => ['key' => ['id' => 'ABC123']]]);

        $training = $this->criarTreinamento();
        $alvo = $this->criarUsuario();

        $reminder = TrainingReminder::create([
            'user_id' => $alvo->id,
            'training_id' => $training->id,
            'telefone' => $alvo->telefone,
            'jid' => '5511999999999@s.whatsapp.net',
            'mensagem' => 'Olá! Falta finalizar o treinamento.',
            'status' => TrainingReminder::STATUS_FILA,
            'agendado_para' => Carbon::now()->subMinute(),
        ]);

        $this->artisan('lembretes:processar')->assertSuccessful();

        $reminder->refresh();

        $this->assertSame(TrainingReminder::STATUS_ENVIADO, $reminder->status);
        $this->assertSame('ABC123', $reminder->gateway_message_id);
        $this->assertNotNull($reminder->enviado_em);

        $envio = collect($fake->requests)->first(function (array $request) {
            return str_contains($request['path'], '/api/messages/') && str_ends_with($request['path'], '/send');
        });

        $this->assertNotNull($envio, 'O comando deveria chamar o endpoint de envio.');
        $this->assertSame('POST', $envio['method']);
        $this->assertStringContainsString('/api/messages/dss/', $envio['path']);
        $this->assertStringContainsString('5511999999999%40s.whatsapp.net', $envio['path']);
        $this->assertSame('Olá! Falta finalizar o treinamento.', $envio['payload']['message']['text']);
    }

    public function test_comando_respeita_teto_diario(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
            'whatsapp.daily_cap' => 1,
        ]);

        $this->fakeGateway(['status' => true, 'data' => ['key' => ['id' => 'ABC123']]]);

        $training = $this->criarTreinamento();

        foreach ([$this->criarUsuario(), $this->criarUsuario()] as $alvo) {
            TrainingReminder::create([
                'user_id' => $alvo->id,
                'training_id' => $training->id,
                'telefone' => $alvo->telefone,
                'jid' => '5511999999999@s.whatsapp.net',
                'mensagem' => 'Olá! Falta finalizar o treinamento.',
                'status' => TrainingReminder::STATUS_FILA,
                'agendado_para' => Carbon::now()->subMinute(),
            ]);
        }

        $this->artisan('lembretes:processar')->assertSuccessful();
        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_ENVIADO)->count());

        $this->travel(10)->minutes();
        $this->artisan('lembretes:processar')->assertSuccessful();

        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_ENVIADO)->count());
        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_FILA)->count());
    }

    public function test_comando_pausa_apos_falhas_consecutivas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
            'whatsapp.max_consecutive_failures' => 1,
        ]);

        $this->fakeGateway(['status' => false, 'message' => 'Número inválido'], 500);

        $training = $this->criarTreinamento();

        foreach ([$this->criarUsuario(), $this->criarUsuario()] as $alvo) {
            TrainingReminder::create([
                'user_id' => $alvo->id,
                'training_id' => $training->id,
                'telefone' => $alvo->telefone,
                'jid' => '5511999999999@s.whatsapp.net',
                'mensagem' => 'Olá! Falta finalizar o treinamento.',
                'status' => TrainingReminder::STATUS_FILA,
                'agendado_para' => Carbon::now()->subMinute(),
            ]);
        }

        $this->artisan('lembretes:processar')->assertSuccessful();
        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_FALHOU)->count());

        $this->artisan('lembretes:processar')->assertSuccessful();

        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_FILA)->count());
        $this->assertSame(1, TrainingReminder::where('status', TrainingReminder::STATUS_FALHOU)->count());
    }

    public function test_comando_com_fila_vazia_nao_chama_gateway(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 09:00:00'));

        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'chave-teste',
        ]);

        $fake = $this->fakeGateway(['status' => true, 'data' => ['key' => ['id' => 'ABC123']]]);

        $this->artisan('lembretes:processar')->assertSuccessful();

        $this->assertSame([], $fake->requests, 'Fila vazia não deve consultar sessão nem enviar nada.');
    }
}
