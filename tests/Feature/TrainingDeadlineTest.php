<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrainingDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function criarUsuario(string $cpf = '33333333333', ?Role $role = null): User
    {
        return User::create([
            'nome' => 'Maria Colaboradora',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $role?->id,
        ]);
    }

    private function criarDss(string $dataLiberacao): Training
    {
        return Training::create([
            'titulo' => 'DSS Prazo',
            'descricao' => 'Descrição do DSS de prazo',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 1,
            'status' => 'ativo',
            'obrigatorio' => true,
            'data_liberacao' => $dataLiberacao,
            'avaliacao_pergunta' => 'Qual a resposta correta?',
            'avaliacao_opcoes' => ['Errada', 'Certa'],
            'avaliacao_resposta_correta' => 1,
        ]);
    }

    public function test_prazo_padrao_e_as_23h_do_domingo_subsequente(): void
    {
        // Liberação na segunda-feira: vence no domingo da mesma semana.
        $treinamentoSegunda = $this->criarDss('2026-10-05 08:00');
        $this->assertSame('2026-10-11 23:00', $treinamentoSegunda->prazoBaseConclusao()->format('Y-m-d H:i'));

        // Liberação no domingo: vence no domingo seguinte.
        $treinamentoDomingo = $this->criarDss('2026-10-11 09:00');
        $this->assertSame('2026-10-18 23:00', $treinamentoDomingo->prazoBaseConclusao()->format('Y-m-d H:i'));
    }

    public function test_player_bloqueia_acesso_apos_o_prazo(): void
    {
        Carbon::setTestNow('2026-01-05 08:00'); // segunda-feira do cadastro
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);

        Carbon::setTestNow('2026-10-14 10:00'); // depois de 11/10 23:00
        $training = $this->criarDss('2026-10-05 08:00');

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertStatus(403);

        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 10,
            'porcentagem_assistida' => 5,
        ])->assertStatus(403)->assertJson(['prazo_expirado' => true]);

        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/avaliacao", ['answer' => 1])
            ->assertStatus(403)->assertJson(['prazo_expirado' => true]);

        $this->assertDatabaseMissing('user_progress', [
            'user_id' => $user->id,
            'training_id' => $training->id,
        ]);
    }

    public function test_prazo_estendido_pelo_gestor_permite_concluir(): void
    {
        Carbon::setTestNow('2026-01-05 08:00');
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);

        Carbon::setTestNow('2026-10-14 10:00');
        $training = $this->criarDss('2026-10-05 08:00');

        UserProgress::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'tempo_assistido' => 10,
            'porcentagem_assistida' => 5,
            'prazo_estendido_ate' => '2026-10-18 23:00',
            'prazo_estendido_em' => now(),
            'prazo_estendido_motivo' => 'Prorrogação de teste',
        ]);

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")
            ->assertOk()
            ->assertSee('05/10 08:00 a 18/10 23:00');

        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 20,
            'porcentagem_assistida' => 10,
        ])->assertOk();
    }

    public function test_dashboard_move_expirado_para_secao_propria_e_volta_com_prorrogacao(): void
    {
        Carbon::setTestNow('2026-01-05 08:00');
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);

        Carbon::setTestNow('2026-10-14 10:00');
        $training = $this->criarDss('2026-10-05 08:00');

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Prazo Expirado');

        UserProgress::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'tempo_assistido' => 5,
            'porcentagem_assistida' => 1,
            'prazo_estendido_ate' => '2026-10-18 23:00',
            'prazo_estendido_em' => now(),
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Prazo Expirado')
            ->assertSee('Em Andamento')
            ->assertSee('05/10 08:00 a 18/10 23:00');
    }

    public function test_api_lista_treinamento_expirado_em_bloqueados(): void
    {
        Carbon::setTestNow('2026-01-05 08:00');
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);

        Carbon::setTestNow('2026-10-14 10:00');
        $training = $this->criarDss('2026-10-05 08:00');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/trainings')->assertOk();

        $bloqueado = collect($response->json('data.bloqueados'))->firstWhere('id', $training->id);
        $this->assertNotNull($bloqueado);
        $this->assertTrue($bloqueado['prazo_expirado']);
        $this->assertStringContainsString('2026-10-12T02:00:00', $bloqueado['prazo_conclusao']);

        $this->getJson("/api/v1/trainings/{$training->id}")
            ->assertStatus(403)
            ->assertJson(['prazo_expirado' => true]);
    }

    public function test_gestor_libera_novo_prazo_e_pagina_lista_pendencias(): void
    {
        Carbon::setTestNow('2026-01-05 08:00');
        $roleUsuario = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $roleSuper = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $super = $this->criarUsuario('44444444444', $roleSuper);

        $user = $this->criarUsuario(role: $roleUsuario);

        Carbon::setTestNow('2026-10-14 10:00');
        $training = $this->criarDss('2026-10-05 08:00');

        // Página do gestor lista o usuário que nunca iniciou
        $this->actingAs($super)->get('/admin/prazos-treinamentos')
            ->assertOk()
            ->assertSee('Prazos de Treinamentos')
            ->assertSee('Maria Colaboradora')
            ->assertSee('DSS Prazo');

        // Usuário comum não acessa
        $this->actingAs($user)->get('/admin/prazos-treinamentos')->assertStatus(403);

        // Libera novo prazo
        $this->actingAs($super)->post('/admin/prazos-treinamentos', [
            'user_id' => $user->id,
            'training_id' => $training->id,
            'prazo' => '2026-10-25T23:00',
            'justificativa' => 'Usuário ausente no período de liberação.',
        ])->assertRedirect();

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'training_id' => $training->id,
            'prazo_estendido_ate' => '2026-10-25 23:00:00',
        ]);

        // Com o novo prazo o player volta a funcionar
        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertOk();

        // Remover a prorrogação volta ao prazo padrão
        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->first();

        $this->actingAs($super)->delete("/admin/prazos-treinamentos/{$progress->id}")->assertRedirect();

        $this->assertNull($progress->fresh()->prazo_estendido_ate);
    }
}
