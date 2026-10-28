<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingAntiCheatTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf = '12345678901', ?Role $role = null): User
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

    private function criarTreinamento(int $duracaoMinutos = 10): Training
    {
        return Training::create([
            'titulo' => 'Treinamento Anti-Burlamento',
            'descricao' => 'Descrição do Treinamento',
            'tipo' => 'treinamento',
            'tipo_usuario_permitido' => ['motorista'],
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => $duracaoMinutos,
            'carga_horaria_segundos' => $duracaoMinutos * 60,
            'status' => 'ativo',
            'obrigatorio' => true,
            'avaliacao_pergunta' => 'Pergunta teste?',
            'avaliacao_opcoes' => ['Opção 1', 'Opção 2'],
            'avaliacao_resposta_correta' => 1,
        ]);
    }

    public function test_avanco_instantaneo_e_barrado_pelo_tempo_decorrido(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarTreinamento(10); // 600s
        $training->assignedUsers()->attach($user->id);

        // Usuário acessa o player
        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertOk();

        // Usuário tenta burlar enviando salto de 500s imediatamente
        $response = $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 500,
            'porcentagem_assistida' => 100,
        ]);

        $response->assertOk();

        $progress = UserProgress::where('user_id', $user->id)->where('training_id', $training->id)->first();
        $this->assertNotNull($progress);

        // O tempo assistido NÃO pode ser 500s; deve ser contido dentro da margem de segurança física
        $this->assertLessThan(40, $progress->tempo_assistido);
        $this->assertLessThan(10, $progress->porcentagem_assistida);
    }

    public function test_servidor_recalcula_porcentagem_assistida_e_ignora_porcentagem_forjada(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarTreinamento(10); // 600s
        $training->assignedUsers()->attach($user->id);

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertOk();

        // Cliente tenta forjar 100% com tempo de 10s
        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 10,
            'porcentagem_assistida' => 100,
        ])->assertOk();

        $progress = UserProgress::where('user_id', $user->id)->where('training_id', $training->id)->first();
        $this->assertNotNull($progress);

        // 10s em um vídeo de 600s deve ser ~1-2%, e NUNCA 100%
        $this->assertLessThan(5, $progress->porcentagem_assistida);
        $this->assertNotEquals(100, $progress->porcentagem_assistida);
    }

    public function test_rota_completar_recusa_conclusao_sem_assistir_video(): void
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);
        $user = $this->criarUsuario(role: $role);
        $training = $this->criarTreinamento(10);
        $training->assignedUsers()->attach($user->id);

        UserProgress::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'tempo_assistido' => 50,
            'porcentagem_assistida' => 8,
            'concluido' => false,
        ]);

        // Tentativa de conclusão direta via POST /completar
        $response = $this->actingAs($user)->postJson("/treinamentos/{$training->id}/completar");
        $response->assertStatus(422);

        $progress = UserProgress::where('user_id', $user->id)->where('training_id', $training->id)->first();
        $this->assertFalse((bool) $progress->concluido);
    }
}
