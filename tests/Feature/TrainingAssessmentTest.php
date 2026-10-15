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
}
