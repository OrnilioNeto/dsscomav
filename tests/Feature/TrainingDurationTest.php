<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingDurationTest extends TestCase
{
    use RefreshDatabase;

    private function criarSuperAdmin(): User
    {
        $role = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);

        return User::create([
            'nome' => 'Super Teste',
            'cpf' => '10178415430',
            'email' => 'super@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    private function criarUsuario(): User
    {
        return User::create([
            'nome' => 'João Motorista',
            'cpf' => '22222222222',
            'email' => 'motorista@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);
    }

    private function payloadDss(array $overrides = []): array
    {
        return array_merge([
            'titulo' => 'DSS com segundos',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => '20:30',
            'tipo_usuario_permitido' => ['motorista'],
            'avaliacao_pergunta' => 'Qual a resposta?',
            'avaliacao_opcoes' => ['Errada', 'Certa'],
            'avaliacao_resposta_correta' => 1,
        ], $overrides);
    }

    public function test_cadastro_aceita_carga_horaria_com_segundos(): void
    {
        $user = $this->criarSuperAdmin();

        $this->actingAs($user)
            ->post('/treinamentos', $this->payloadDss())
            ->assertRedirect();

        $training = Training::where('titulo', 'DSS com segundos')->firstOrFail();

        $this->assertSame(20, (int) $training->carga_horaria);
        $this->assertSame(30, (int) $training->carga_horaria_segundos);
        $this->assertSame(1230, $training->duracaoSegundos());
        $this->assertSame('20 min 30 s', $training->carga_horaria_formatada);
        $this->assertSame('20 minutos e 30 segundos', $training->carga_horaria_formatada_extenso);
        $this->assertSame('20:30', $training->carga_horaria_input);
    }

    public function test_cadastro_aceita_apenas_segundos(): void
    {
        $user = $this->criarSuperAdmin();

        $this->actingAs($user)
            ->post('/treinamentos', $this->payloadDss([
                'carga_horaria' => '0:45',
            ]))
            ->assertRedirect();

        $training = Training::where('titulo', 'DSS com segundos')->firstOrFail();

        $this->assertSame(0, (int) $training->carga_horaria);
        $this->assertSame(45, (int) $training->carga_horaria_segundos);
        $this->assertSame(45, $training->duracaoSegundos());
        $this->assertSame('45 s', $training->carga_horaria_formatada);
        $this->assertSame('0:45', $training->carga_horaria_input);
    }

    public function test_cadastro_aceita_minutos_sem_segundos(): void
    {
        $user = $this->criarSuperAdmin();

        $this->actingAs($user)
            ->post('/treinamentos', $this->payloadDss([
                'carga_horaria' => '20',
            ]))
            ->assertRedirect();

        $training = Training::where('titulo', 'DSS com segundos')->firstOrFail();

        $this->assertSame(20, (int) $training->carga_horaria);
        $this->assertNull($training->carga_horaria_segundos);
        $this->assertSame(1200, $training->duracaoSegundos());
        $this->assertSame('20 min', $training->carga_horaria_formatada);
        $this->assertSame('20', $training->carga_horaria_input);
    }

    public function test_cadastro_rejeita_duracao_zero(): void
    {
        $user = $this->criarSuperAdmin();

        $this->actingAs($user)
            ->post('/treinamentos', $this->payloadDss([
                'carga_horaria' => '0:00',
            ]))
            ->assertSessionHasErrors('carga_horaria');

        $this->assertDatabaseMissing('trainings', ['titulo' => 'DSS com segundos']);
    }

    public function test_cadastro_rejeita_formato_invalido(): void
    {
        $user = $this->criarSuperAdmin();

        $this->actingAs($user)
            ->post('/treinamentos', $this->payloadDss([
                'carga_horaria' => '20:75',
            ]))
            ->assertSessionHasErrors('carga_horaria');

        $this->assertDatabaseMissing('trainings', ['titulo' => 'DSS com segundos']);
    }

    public function test_registros_antigos_sem_segundos_permanecem_iguais(): void
    {
        $training = Training::create([
            'titulo' => 'Antigo sem segundos',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 15,
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
        ]);

        $this->assertNull($training->carga_horaria_segundos);
        $this->assertSame(900, $training->duracaoSegundos());
        $this->assertSame('15 min', $training->carga_horaria_formatada);
        $this->assertSame('15 minutos', $training->carga_horaria_formatada_extenso);
        $this->assertSame('15', $training->carga_horaria_input);
    }

    public function test_player_usa_duracao_total_com_segundos(): void
    {
        $user = $this->criarUsuario();

        $training = Training::create([
            'titulo' => 'Vídeo de 20:30',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 20,
            'carga_horaria_segundos' => 30,
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($user)->get("/treinamentos/{$training->id}/player");

        $response->assertOk();
        $response->assertSee('const registeredDurationSeconds = 1230', false);
    }
}
