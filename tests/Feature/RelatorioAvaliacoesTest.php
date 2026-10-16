<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Training;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RelatorioAvaliacoesTest extends TestCase
{
    use RefreshDatabase;

    private Role $roleSuper;

    private Role $roleAdmin;

    private Role $roleUsuario;

    private User $super;

    private Training $training;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-16 10:00:00');

        $this->roleSuper = Role::create(['nome' => 'super_admin', 'descricao' => 'Plataforma']);
        $this->roleAdmin = Role::create(['nome' => 'admin', 'descricao' => 'Admin']);
        $this->roleUsuario = Role::create(['nome' => 'usuario', 'descricao' => 'Usuário']);

        $this->super = $this->criarUsuario('99999999999', $this->roleSuper, 'Dono Plataforma');

        $this->training = Training::create([
            'titulo' => 'DSS de Segurança',
            'tipo' => 'dss',
            'tipo_video' => 'youtube',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'carga_horaria' => 10,
            'tipo_usuario_permitido' => ['todos'],
            'status' => 'ativo',
            'nota_minima_aprovacao' => 70,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function criarUsuario(string $cpf, Role $role, string $nome, array $extra = []): User
    {
        return User::create(array_merge([
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'role_id' => $role->id,
        ], $extra));
    }

    private function criarProgresso(User $usuario, array $dados = []): UserProgress
    {
        return UserProgress::create(array_merge([
            'user_id' => $usuario->id,
            'training_id' => $this->training->id,
            'data_inicio' => now()->subDays(3),
            'data_conclusao' => now()->subDay(),
            'concluido' => true,
            'porcentagem_assistida' => 100,
            'tempo_assistido' => 600,
            'avaliacao_nota' => 80,
            'avaliacao_aprovada' => true,
            'avaliacao_tentativas' => 0,
        ], $dados));
    }

    public function test_base_exclui_apenas_super_admin_e_usuario_teste(): void
    {
        $comum = $this->criarUsuario('11111111111', $this->roleUsuario, 'Comum Aprovada');
        $teste = $this->criarUsuario('22222222222', $this->roleUsuario, 'Usuário Teste', ['usuario_teste' => true]);
        $admin = $this->criarUsuario('33333333333', $this->roleAdmin, 'Admin Participante');

        $this->criarProgresso($this->super);
        $this->criarProgresso($teste);
        $this->criarProgresso($admin);
        $this->criarProgresso($comum);

        $response = $this->actingAs($this->super)->get(route('relatorios.avaliacoes'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('resumo')['total_resultados']);

        $nomes = collect($response->viewData('resultados')->items())->map(fn ($progresso) => $progresso->user->nome);
        $this->assertTrue($nomes->contains('Comum Aprovada'));
        $this->assertTrue($nomes->contains('Admin Participante'));
        $this->assertFalse($nomes->contains('Usuário Teste'));
        $this->assertSame(2, $response->viewData('metricasExclusao')['total']);
    }

    public function test_filtros_de_resultado_nota_periodo_e_tipo(): void
    {
        $aprovado = $this->criarUsuario('11111111111', $this->roleUsuario, 'Aprovado');
        $reprovado = $this->criarUsuario('22222222222', $this->roleUsuario, 'Reprovado');

        $this->criarProgresso($aprovado);
        $this->criarProgresso($reprovado, [
            'avaliacao_nota' => 40,
            'avaliacao_aprovada' => false,
            'avaliacao_tentativas' => 1,
            'concluido' => false,
            'data_conclusao' => null,
        ]);

        $this->actingAs($this->super)
            ->get(route('relatorios.avaliacoes', ['resultado' => 'aprovado']))
            ->assertOk()
            ->assertViewHas('resumo', fn ($resumo) => $resumo['total_resultados'] === 1);

        $this->actingAs($this->super)
            ->get(route('relatorios.avaliacoes', ['nota_max' => 50]))
            ->assertOk()
            ->assertViewHas('resumo', fn ($resumo) => $resumo['total_resultados'] === 1);

        $this->actingAs($this->super)
            ->get(route('relatorios.avaliacoes', ['resultado' => 'sem_nota']))
            ->assertOk()
            ->assertViewHas('resumo', fn ($resumo) => $resumo['total_resultados'] === 0);

        $this->actingAs($this->super)
            ->get(route('relatorios.avaliacoes', ['periodo_inicio' => now()->subDays(5)->format('Y-m-d'), 'periodo_fim' => now()->addDay()->format('Y-m-d')]))
            ->assertOk()
            ->assertViewHas('resumo', fn ($resumo) => $resumo['total_resultados'] >= 1);
    }

    public function test_csv_e_pdf_sao_gerados_com_metadados_e_hash(): void
    {
        $comum = $this->criarUsuario('11111111111', $this->roleUsuario, 'Comum Aprovada');
        $this->criarProgresso($comum);

        TrainingLog::registrar($this->training->id, $comum->id, 'avaliacao_submetida', 'Acertos: 8/10 (80%).');

        $csv = $this->actingAs($this->super)->get(route('relatorios.avaliacoes.csv'));
        $csv->assertOk();
        $conteudo = $csv->streamedContent();
        $this->assertStringContainsString('Usuario;CPF', $conteudo);
        $this->assertStringContainsString('Comum Aprovada', $conteudo);
        $this->assertStringContainsString('# SHA-256 do recorte', $conteudo);

        $csvTentativas = $this->actingAs($this->super)
            ->get(route('relatorios.avaliacoes.csv', ['tipo_exportacao' => 'tentativas']));
        $csvTentativas->assertOk();
        $this->assertStringContainsString('avaliacao_submetida', $csvTentativas->streamedContent());

        $pdf = $this->actingAs($this->super)->get(route('relatorios.avaliacoes.pdf'));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}
