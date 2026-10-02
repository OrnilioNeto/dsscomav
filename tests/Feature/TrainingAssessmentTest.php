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
