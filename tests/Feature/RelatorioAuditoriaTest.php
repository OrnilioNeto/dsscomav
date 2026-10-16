<?php

namespace Tests\Feature;

use App\Models\FolgaDia;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Training;
use App\Models\TrainingVacationExemption;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserVacation;
use App\Support\RolePermissionCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RelatorioAuditoriaTest extends TestCase
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
            'titulo' => 'DSS Obrigatório de Segurança',
            'tipo' => 'dss',
            'tipo_video' => 'youtube',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'carga_horaria' => 10,
            'tipo_usuario_permitido' => ['todos'],
            'status' => 'ativo',
            'obrigatorio' => true,
            'data_liberacao' => '2026-10-12 08:00:00',
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
            'data_inicio' => '2026-10-13 08:00:00',
            'data_conclusao' => '2026-10-15 10:00:00',
            'concluido' => true,
            'porcentagem_assistida' => 100,
            'tempo_assistido' => 600,
            'avaliacao_nota' => 90,
            'avaliacao_aprovada' => true,
        ], $dados));
    }

    public function test_base_exclui_somente_super_admin_e_teste_e_mostra_transparencia(): void
    {
        $comum = $this->criarUsuario('11111111111', $this->roleUsuario, 'Comum Em Dia');
        $admin = $this->criarUsuario('33333333333', $this->roleAdmin, 'Admin Participante');
        $inativo = $this->criarUsuario('44444444444', $this->roleUsuario, 'Inativo Sem Progresso', ['status' => 'inativo']);
        $teste = $this->criarUsuario('22222222222', $this->roleUsuario, 'Usuário Teste', ['usuario_teste' => true]);
        $super2 = $this->criarUsuario('88888888888', $this->roleSuper, 'Outro Super');

        $this->criarProgresso($comum);
        $this->criarProgresso($admin);
        $this->criarProgresso($teste);
        $this->criarProgresso($super2);
        $this->criarProgresso($this->super);

        $response = $this->actingAs($this->super)->get(route('relatorios.auditoria'));

        $response->assertOk();

        $resumo = $response->viewData('resumo');
        $this->assertSame(3, $resumo['total_usuarios'], 'Base deve conter comum + admin + inativo');
        $this->assertSame(2, $resumo['concluidos_em_dia']);

        $exclusao = $response->viewData('metricasExclusao');
        $this->assertSame(3, $exclusao['total'], 'Excluídos: 2 super_admin + 1 usuário de teste');
        $this->assertSame(2, $exclusao['super_admin']);
        $this->assertSame(1, $exclusao['teste']);
    }

    public function test_conformidade_considera_prazo_e_ausencias_justificadas(): void
    {
        $emDia = $this->criarUsuario('11111111111', $this->roleUsuario, 'Concluiu Em Dia');
        $emAtraso = $this->criarUsuario('22222222222', $this->roleUsuario, 'Concluiu Atrasado');
        $ausente = $this->criarUsuario('33333333333', $this->roleUsuario, 'Sem Progresso');
        $afastado = $this->criarUsuario('44444444444', $this->roleUsuario, 'De Férias na Liberação');

        $this->criarProgresso($emDia);
        $this->criarProgresso($emAtraso, ['data_conclusao' => '2026-10-20 10:00:00']);

        // Férias cobrindo a data de liberação do conteúdo (12/10) — justifica a ausência
        UserVacation::create([
            'user_id' => $afastado->id,
            'data_inicio' => '2026-10-01',
            'data_fim' => '2026-10-12',
        ]);

        $response = $this->actingAs($this->super)->get(route('relatorios.auditoria'));
        $response->assertOk();

        $resumo = $response->viewData('resumo');
        $this->assertSame(1, $resumo['concluidos_em_dia']);
        $this->assertSame(1, $resumo['concluidos_atraso']);
        $this->assertSame(1, $resumo['afastados_liberacao'], 'Férias na liberação entram como afastamento justificado');
        $this->assertSame(1, $resumo['nao_iniciados'], 'Sem progresso e sem justificativa = não iniciado');

        // Com afastados no denominador, o índice de conformidade cai
        $responseRigido = $this->actingAs($this->super)
            ->get(route('relatorios.auditoria', ['incluir_afastados' => 1]));
        $this->assertLessThan(
            $resumo['indice_conformidade'],
            $responseRigido->viewData('resumo')['indice_conformidade']
        );

        // Detalhe do conteúdo específico lista os usuários elegíveis
        $detalhe = $this->actingAs($this->super)
            ->get(route('relatorios.auditoria', ['training_id' => $this->training->id]));
        $detalhe->assertOk();
        $this->assertNotNull($detalhe->viewData('usuariosDoConteudo'));

        // CSV detalhado pode incluir os elegíveis sem nenhum registro
        $csv = $this->actingAs($this->super)->get(route('relatorios.auditoria.csv', [
            'training_id' => $this->training->id,
            'incluir_nao_iniciados' => 1,
        ]));
        $csv->assertOk();
        $this->assertStringContainsString('Sem Progresso', $csv->getContent());
    }

    public function test_painel_de_ausencias_agrega_ferias_folgas_isencoes_e_prorrogacoes(): void
    {
        $comum = $this->criarUsuario('11111111111', $this->roleUsuario, 'Comum');

        UserVacation::create([
            'user_id' => $comum->id,
            'data_inicio' => '2026-10-01',
            'data_fim' => '2026-10-05',
        ]);

        FolgaDia::create(['user_id' => $comum->id, 'data' => '2026-10-10', 'tipo' => 'atestado']);
        FolgaDia::create(['user_id' => $comum->id, 'data' => '2026-10-11', 'tipo' => 'licenca']);

        TrainingVacationExemption::create([
            'user_id' => $comum->id,
            'training_id' => $this->training->id,
            'data_inicio' => '2026-10-02',
            'data_fim' => '2026-10-04',
            'motivo' => 'Férias programadas',
            'created_by' => $this->super->id,
        ]);

        $this->criarProgresso($comum, [
            'concluido' => false,
            'data_conclusao' => null,
            'prazo_estendido_ate' => '2026-10-30 23:00:00',
            'prazo_estendido_por' => $this->super->id,
            'prazo_estendido_em' => '2026-10-10 12:00:00',
            'prazo_estendido_motivo' => 'Ajuste operacional com o cliente',
        ]);

        $response = $this->actingAs($this->super)->get(route('relatorios.auditoria', [
            'periodo_inicio' => '2026-10-01',
            'periodo_fim' => '2026-10-15',
        ]));

        $response->assertOk();

        $ausencias = $response->viewData('ausencias');
        $this->assertSame(1, $ausencias['totais']['ferias_pessoas']);
        $this->assertSame(5, $ausencias['totais']['ferias_dias']);
        $this->assertSame(1, $ausencias['totais']['atestados_dias']);
        $this->assertSame(1, $ausencias['totais']['licencas_dias']);
        $this->assertSame(1, $ausencias['totais']['isencoes_total']);
        $this->assertSame(1, $ausencias['totais']['prorrogacoes_total']);
    }

    public function test_exportacoes_csv_e_pdf_com_hash_e_metadados(): void
    {
        $comum = $this->criarUsuario('11111111111', $this->roleUsuario, 'Comum Exportação');
        $this->criarProgresso($comum);

        $csv = $this->actingAs($this->super)->get(route('relatorios.auditoria.csv'));
        $csv->assertOk();
        $conteudo = $csv->getContent();
        $this->assertStringContainsString('Usuario;CPF', $conteudo);
        $this->assertStringContainsString('Comum Exportação', $conteudo);
        $this->assertStringContainsString('# SHA-256 do recorte', $conteudo);
        $this->assertStringContainsString('super_admin e usuários marcados como teste', $conteudo);

        $csvAusencias = $this->actingAs($this->super)
            ->get(route('relatorios.auditoria.csv', ['tipo_exportacao' => 'ausencias']));
        $csvAusencias->assertOk();
        $this->assertStringContainsString('Usuario;CPF;Tipo', $csvAusencias->getContent());

        $pdf = $this->actingAs($this->super)->get(route('relatorios.auditoria.pdf'));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_escopo_do_admin_nao_exibe_usuarios_administradores(): void
    {
        $adminViewer = $this->criarUsuario('55555555555', $this->roleAdmin, 'Admin Visualizador');
        $this->criarUsuario('33333333333', $this->roleAdmin, 'Outro Admin');
        $this->criarUsuario('11111111111', $this->roleUsuario, 'Usuário Comum');

        $response = $this->actingAs($adminViewer)->get(route('relatorios.auditoria'));
        $response->assertOk();

        $this->assertSame(1, $response->viewData('resumo')['total_usuarios']);
    }

    public function test_trilha_de_auditoria_apenas_com_permissao(): void
    {
        $adminViewer = $this->criarUsuario('55555555555', $this->roleAdmin, 'Admin Sem Permissão');

        $semPermissao = $this->actingAs($adminViewer)->get(route('relatorios.auditoria'));
        $semPermissao->assertOk();
        $this->assertNull($semPermissao->viewData('trilha'));
        $this->assertFalse($semPermissao->viewData('podeVerTrilha'));

        RolePermission::create([
            'role_id' => $this->roleAdmin->id,
            'module' => 'auditoria',
            'can_view' => true,
            'can_edit' => false,
        ]);

        app(RolePermissionCache::class)->forget();

        $comPermissao = $this->actingAs($adminViewer)->get(route('relatorios.auditoria'));
        $comPermissao->assertOk();
        $this->assertTrue($comPermissao->viewData('podeVerTrilha'));
        $this->assertNotNull($comPermissao->viewData('trilha'));
    }
}
