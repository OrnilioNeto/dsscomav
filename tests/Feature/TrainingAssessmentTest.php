<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\TrainingQuestion;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf = '22222222222', ?Role $role = null): User
    {
        return User::create([
            'nome' => 'João Motorista',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $role?->id,
        ]);
    }

    private function criarDss(): Training
    {
        return Training::create([
            'titulo' => 'DSS Teste',
            'descricao' => 'Descrição do DSS',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 1,
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Qual a resposta correta?',
            'avaliacao_opcoes' => ['Errada', 'Certa', 'Também errada'],
            'avaliacao_resposta_correta' => 1,
        ]);
    }

    private function criarProgresso(User $user, Training $training, int $porcentagem): UserProgress
    {
        return UserProgress::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'tempo_assistido' => 30,
            'porcentagem_assistida' => $porcentagem,
        ]);
    }

    public function test_player_exibe_questoes_bloqueadas_ate_concluir_o_video(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();

        TrainingQuestion::create([
            'training_id' => $training->id,
            'pergunta' => 'Pergunta do banco?',
            'opcoes' => ['A', 'B', 'C'],
            'resposta_correta' => 1,
            'ordem' => 0,
        ]);

        $response = $this->actingAs($user)->get("/treinamentos/{$training->id}/player");

        $response->assertOk();
        $response->assertSee('será liberada para resposta após você concluir 100% do vídeo', false);
        $response->assertSee('Pergunta do banco?', false);
        $response->assertSee('disabled', false);
    }

    public function test_envio_de_avaliacao_de_treinamento_exige_senha(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $training->update(['tipo' => 'treinamento']);
        $training->assignedUsers()->attach($user->id);
        $this->criarProgresso($user, $training, 100);

        $this->actingAs($user)
            ->postJson("/treinamentos/{$training->id}/avaliacao", ['answer' => 1])
            ->assertStatus(422)
            ->assertJson(['error' => 'Senha incorreta. Confirme sua senha de acesso e tente novamente.']);
    }

    public function test_envio_de_avaliacao_com_senha_correta_aprova(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $training->update(['tipo' => 'treinamento']);
        $training->assignedUsers()->attach($user->id);
        $this->criarProgresso($user, $training, 100);

        $this->actingAs($user)
            ->postJson("/treinamentos/{$training->id}/avaliacao", [
                'answer' => 1,
                'password' => 'senha123',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'training_id' => $training->id,
            'avaliacao_aprovada' => true,
        ]);
    }

    public function test_envio_de_avaliacao_de_dss_dispensa_senha(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $this->criarProgresso($user, $training, 100);

        $this->actingAs($user)
            ->postJson("/treinamentos/{$training->id}/avaliacao", ['answer' => 1])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_cadastro_de_dss_aceita_banco_de_questoes_e_nota_minima(): void
    {
        $superRole = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $super = $this->criarUsuario('55555555555', $superRole);

        $response = $this->actingAs($super)->post('/treinamentos', [
            'titulo' => 'DSS com Banco de Questões',
            'descricao' => 'Descrição do DSS com banco',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => '1',
            'obrigatorio' => '1',
            'questoes' => [
                ['pergunta' => 'Pergunta DSS 1?', 'opcoes' => ['A', 'B', 'C'], 'resposta_correta' => 2],
            ],
            'quantidade_questoes_prova' => 1,
            'nota_minima_aprovacao' => 80,
        ]);

        $response->assertRedirect(route('treinamentos.index'));

        $training = Training::where('titulo', 'DSS com Banco de Questões')->firstOrFail();

        $this->assertSame('dss', $training->tipo);
        $this->assertTrue($training->hasQuestionBank());
        $this->assertSame(80, (int) $training->nota_minima_aprovacao);
        $this->assertSame(1, (int) $training->quantidade_questoes_prova);
        $this->assertDatabaseHas('training_questions', [
            'training_id' => $training->id,
            'pergunta' => 'Pergunta DSS 1?',
        ]);

        // O player monta a prova a partir do banco de questões do DSS
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario('66666666666', $role);

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")
            ->assertOk()
            ->assertSee('Pergunta DSS 1?');
    }

    public function test_edicao_de_dss_legado_precarrega_pergunta_no_banco_de_questoes(): void
    {
        $superRole = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $super = $this->criarUsuario('55555555555', $superRole);
        $training = $this->criarDss();

        $this->actingAs($super)->get("/treinamentos/{$training->id}/edit")
            ->assertOk()
            ->assertSee('Qual a resposta correta?', false)
            ->assertDontSee('name="avaliacao_pergunta"', false);
    }

    public function test_cadastro_de_dss_sem_avaliacao_falha(): void
    {
        $superRole = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $super = $this->criarUsuario('55555555555', $superRole);

        $this->actingAs($super)->post('/treinamentos', [
            'titulo' => 'DSS sem avaliação',
            'tipo' => 'dss',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => '1',
        ])->assertSessionHasErrors('questoes');

        $this->assertDatabaseMissing('trainings', ['titulo' => 'DSS sem avaliação']);
    }

    public function test_servidor_recusa_avaliacao_antes_de_concluir_o_video(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $this->criarProgresso($user, $training, 10);

        $this->actingAs($user)
            ->postJson("/treinamentos/{$training->id}/avaliacao", ['answer' => 1])
            ->assertStatus(422)
            ->assertJson(['error' => 'A avaliação será liberada após você concluir 100% do vídeo.']);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'training_id' => $training->id,
            'avaliacao_aprovada' => false,
        ]);
    }

    public function test_aprovacao_com_50_por_cento_em_2_questoes_aprova_ao_acertar_1(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $training->update(['nota_minima_aprovacao' => 50]);

        $q1 = TrainingQuestion::create([
            'training_id' => $training->id,
            'pergunta' => 'Questão 1?',
            'opcoes' => ['Opção A', 'Opção B'],
            'resposta_correta' => 0,
            'ordem' => 0,
        ]);

        $q2 = TrainingQuestion::create([
            'training_id' => $training->id,
            'pergunta' => 'Questão 2?',
            'opcoes' => ['Opção C', 'Opção D'],
            'resposta_correta' => 1,
            'ordem' => 1,
        ]);

        $this->criarProgresso($user, $training, 100);

        // Simulando o mapa de sessão: q1 sem embaralhar [0, 1], q2 sem embaralhar [0, 1]
        $mapa = [
            $q1->id => [0, 1],
            $q2->id => [0, 1],
        ];

        // Responde Q1 correta (0) e Q2 errada (0, quando o correto é 1)
        $response = $this->actingAs($user)
            ->withSession(["avaliacao_{$training->id}_map" => $mapa])
            ->postJson("/treinamentos/{$training->id}/avaliacao", [
                'respostas' => [
                    $q1->id => 0,
                    $q2->id => 0,
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'nota' => 50,
                'corretas' => 1,
                'total' => 2,
                'minimo_acertos' => 1,
                'nota_minima' => 50,
            ]);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'training_id' => $training->id,
            'avaliacao_aprovada' => true,
            'avaliacao_nota' => 50,
        ]);
    }

    public function test_player_exibe_criterio_de_aprovacao_ao_usuario(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário padrão']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarDss();
        $training->update(['nota_minima_aprovacao' => 50]);

        TrainingQuestion::create([
            'training_id' => $training->id,
            'pergunta' => 'Q1?',
            'opcoes' => ['A', 'B'],
            'resposta_correta' => 0,
            'ordem' => 0,
        ]);

        TrainingQuestion::create([
            'training_id' => $training->id,
            'pergunta' => 'Q2?',
            'opcoes' => ['C', 'D'],
            'resposta_correta' => 1,
            'ordem' => 1,
        ]);

        $this->criarProgresso($user, $training, 100);

        $response = $this->actingAs($user)->get("/treinamentos/{$training->id}/player");

        $response->assertOk();
        $response->assertSee('Critério de Aprovação', false);
        $response->assertSee('1 de 2 questão(ões)', false);
        $response->assertSee('50%', false);
    }
}
