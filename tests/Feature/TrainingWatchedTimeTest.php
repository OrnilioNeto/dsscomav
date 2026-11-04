<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regressão do registro de tempo assistido.
 *
 * Contexto: o teto antigo ("gap > 600s => máximo 15s") truncava o tempo
 * legítimo quando o navegador/app agrupava requisições (tela bloqueada,
 * background, rede instável). Como os treinamentos do tipo "treinamento"
 * são mais longos, eram os mais afetados.
 */
class TrainingWatchedTimeTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf): User
    {
        $role = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);

        return User::create([
            'nome' => 'Colaborador Teste',
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarTreinamento(int $minutos = 20): Training
    {
        return Training::create([
            'titulo' => 'Treinamento Direcionado',
            'tipo' => 'treinamento',
            'tipo_treinamento' => 'inicial',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => $minutos,
            'carga_horaria_segundos' => null,
            'status' => 'ativo',
            'avaliacao_pergunta' => 'Pergunta?',
            'avaliacao_opcoes' => ['Errada', 'Certa'],
            'avaliacao_resposta_correta' => 1,
        ]);
    }

    private function progresso(User $user, Training $training): UserProgress
    {
        return UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();
    }

    public function test_gap_longo_no_web_registra_o_tempo_real_assistido(): void
    {
        $user = $this->criarUsuario('11122233344');
        $training = $this->criarTreinamento(20);
        $training->assignedUsers()->attach($user->id);

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertOk();

        // 20 minutos assistidos sem nenhum POST intermediário (tela bloqueada /
        // aba em background / rede offline).
        $this->travel(20)->minutes();

        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 1200,
            'porcentagem_assistida' => 100,
        ])->assertOk();

        $progress = $this->progresso($user, $training);

        $this->assertSame(1200, (int) $progress->tempo_assistido);
        $this->assertSame(100, (int) $progress->porcentagem_assistida);
    }

    public function test_gap_longo_na_api_registra_o_tempo_real_assistido(): void
    {
        $user = $this->criarUsuario('55566677788');
        $training = $this->criarTreinamento(20);
        $training->assignedUsers()->attach($user->id);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/trainings/{$training->id}")->assertOk();

        $this->travel(20)->minutes();

        $this->postJson("/api/v1/trainings/{$training->id}/progress", [
            'tempo_assistido' => 1200,
            'porcentagem_assistida' => 100,
        ])->assertOk();

        $progress = $this->progresso($user, $training);

        $this->assertSame(1200, (int) $progress->tempo_assistido);
        $this->assertSame(100, (int) $progress->porcentagem_assistida);
    }

    public function test_salto_imediato_continua_bloqueado(): void
    {
        $user = $this->criarUsuario('99900011122');
        $training = $this->criarTreinamento(20);
        $training->assignedUsers()->attach($user->id);

        $this->actingAs($user)->get("/treinamentos/{$training->id}/player")->assertOk();

        // Sem tempo real decorrido, o cliente não pode "adiantar" o vídeo.
        $this->actingAs($user)->postJson("/treinamentos/{$training->id}/atualizar-progresso", [
            'tempo_assistido' => 1200,
            'porcentagem_assistida' => 100,
        ])->assertOk();

        $progress = $this->progresso($user, $training);

        $this->assertLessThanOrEqual(30, (int) $progress->tempo_assistido);
        $this->assertLessThan(10, (int) $progress->porcentagem_assistida);
    }
}
