<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingVacationExemption;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserVacation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(array $overrides = []): User
    {
        static $sequencia = 0;
        $sequencia++;

        return User::create(array_merge([
            'nome' => 'Usuário Teste '.$sequencia,
            'cpf' => str_pad((string) (22222222222 + $sequencia), 11, '0', STR_PAD_LEFT),
            'email' => 'usuario'.$sequencia.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ], $overrides));
    }

    private function criarDss(): Training
    {
        return Training::create([
            'titulo' => 'DSS Segunda',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 10,
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
            'data_liberacao' => '2026-01-05 08:30:00',
        ]);
    }

    private function comCadastroEm(User $user, string $data): User
    {
        $user->forceFill(['created_at' => $data])->save();

        return $user->fresh();
    }

    public function test_scope_exclui_cadastrados_apos_a_semana_de_liberacao(): void
    {
        $training = $this->criarDss();

        $elegivel = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $tardio = $this->comCadastroEm($this->criarUsuario(), '2026-01-20 10:00:00');

        $ids = User::query()->eligibleForContent($training)->pluck('id')->all();

        $this->assertContains($elegivel->id, $ids);
        $this->assertNotContains($tardio->id, $ids);
    }

    public function test_scope_exclui_publico_nao_permitido(): void
    {
        $training = $this->criarDss();

        $motorista = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $funcionario = $this->comCadastroEm(
            $this->criarUsuario(['tipo_usuario' => 'funcionario']),
            '2026-01-01 10:00:00'
        );

        $ids = User::query()->eligibleForContent($training)->pluck('id')->all();

        $this->assertContains($motorista->id, $ids);
        $this->assertNotContains($funcionario->id, $ids);
    }

    public function test_scope_exclui_usuario_isento_por_ferias(): void
    {
        $training = $this->criarDss();

        $normal = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $isento = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');

        TrainingVacationExemption::create([
            'user_id' => $isento->id,
            'training_id' => $training->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-01-31',
        ]);

        $ids = User::query()->eligibleForContent($training)->pluck('id')->all();

        $this->assertContains($normal->id, $ids);
        $this->assertNotContains($isento->id, $ids);
    }

    public function test_scope_exclui_usuario_em_ferias_na_data_de_liberacao(): void
    {
        $training = $this->criarDss();

        $normal = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $ferias = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');

        UserVacation::create([
            'user_id' => $ferias->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-01-10',
        ]);

        $ids = User::query()->eligibleForContent($training)->pluck('id')->all();

        $this->assertContains($normal->id, $ids);
        $this->assertNotContains($ferias->id, $ids);
    }

    public function test_scope_exclui_nao_atribuido_em_treinamento_direcionado(): void
    {
        $training = Training::create([
            'titulo' => 'Treinamento Direcionado',
            'tipo' => 'treinamento',
            'tipo_treinamento' => 'inicial',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 10,
            'status' => 'ativo',
            'data_liberacao' => '2026-01-05 08:30:00',
        ]);

        $atribuido = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $naoAtribuido = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');

        $training->assignedUsers()->attach($atribuido->id);

        $ids = User::query()->eligibleForContent($training)->pluck('id')->all();

        $this->assertContains($atribuido->id, $ids);
        $this->assertNotContains($naoAtribuido->id, $ids);
    }

    public function test_versao_em_memoria_e_equivalente_ao_scope(): void
    {
        $training = $this->criarDss();

        $elegivel = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $tardio = $this->comCadastroEm($this->criarUsuario(), '2026-01-20 10:00:00');
        $ferias = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');

        UserVacation::create([
            'user_id' => $ferias->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-01-10',
        ]);

        $this->assertTrue($elegivel->isEligibleForContent($training));
        $this->assertFalse($tardio->isEligibleForContent($training));
        $this->assertFalse($ferias->isEligibleForContent($training));
    }

    public function test_taxa_de_conclusao_ignora_usuarios_nao_elegiveis(): void
    {
        $training = $this->criarDss();

        $elegivel = $this->comCadastroEm($this->criarUsuario(), '2026-01-01 10:00:00');
        $tardio = $this->comCadastroEm($this->criarUsuario(), '2026-01-20 10:00:00');

        UserProgress::create([
            'user_id' => $elegivel->id,
            'training_id' => $training->id,
            'concluido' => true,
            'porcentagem_assistida' => 100,
        ]);

        UserProgress::create([
            'user_id' => $tardio->id,
            'training_id' => $training->id,
            'concluido' => true,
            'porcentagem_assistida' => 100,
        ]);

        $this->assertSame(100.0, (float) $training->getTaxaConclusao());
    }
}
