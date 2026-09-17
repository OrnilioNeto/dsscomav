<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function criarRole(string $nome): Role
    {
        return Role::create(['nome' => $nome, 'descricao' => $nome]);
    }

    private function criarUsuario(string $cpf, Role $role, string $nome = 'Usuário Teste'): User
    {
        return User::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
        ]);
    }

    public function test_criacao_de_usuario_gera_log_de_auditoria(): void
    {
        $user = $this->criarUsuario('11111111111', $this->criarRole('usuario'), 'Maria Teste');

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('users', $log->module);
        $this->assertSame('Maria Teste', $log->new_values['nome']);
        $this->assertSame('***', $log->new_values['password']);
    }

    public function test_atualizacao_mascara_campos_sensiveis(): void
    {
        $user = $this->criarUsuario('22222222222', $this->criarRole('usuario'));

        $user->update(['password' => Hash::make('nova-senha-secreta')]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('***', $log->new_values['password']);
        $this->assertStringNotContainsString('nova-senha-secreta', json_encode($log->new_values));
    }

    public function test_login_e_logout_registram_auditoria(): void
    {
        $user = $this->criarUsuario('33333333333', $this->criarRole('usuario'));

        $this->post('/login', ['cpf' => '33333333333', 'password' => 'senha123'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'login',
            'user_id' => $user->id,
            'module' => 'auth',
        ]);

        $this->post('/logout')->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'logout',
            'user_id' => $user->id,
            'module' => 'auth',
        ]);
    }

    public function test_login_falho_registra_auditoria_com_cpf_mascarado(): void
    {
        $this->criarUsuario('44444444444', $this->criarRole('usuario'));

        $this->post('/login', ['cpf' => '44444444444', 'password' => 'errada']);

        $log = AuditLog::where('event', 'login_failed')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('***.444.444-**', $log->new_values['cpf']);
    }

    public function test_tela_de_auditoria_exige_permissao(): void
    {
        $adminRole = $this->criarRole('admin');
        $admin = $this->criarUsuario('55555555555', $adminRole, 'Admin Sem Auditoria');

        $this->actingAs($admin)->get(route('auditoria.index'))->assertStatus(403);

        RolePermission::create([
            'role_id' => $adminRole->id,
            'module' => 'auditoria',
            'can_view' => true,
            'can_edit' => false,
        ]);

        $this->actingAs($admin)->get(route('auditoria.index'))->assertOk();
    }

    public function test_super_admin_acessa_tela_e_exporta_csv(): void
    {
        $super = $this->criarUsuario('66666666666', $this->criarRole('super_admin'), 'Super Teste');

        $this->actingAs($super)->get(route('auditoria.index'))->assertOk();

        $this->actingAs($super)
            ->get(route('auditoria.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'exported',
            'module' => 'auditoria',
            'user_id' => $super->id,
        ]);
    }

    public function test_comando_prune_remove_registros_antigos(): void
    {
        $log = AuditLog::create([
            'event' => 'accessed',
            'module' => 'auth',
            'description' => 'Registro antigo',
        ]);

        $log->forceFill(['created_at' => now()->subDays(400)])->saveQuietly();

        $recente = AuditLog::create([
            'event' => 'accessed',
            'module' => 'auth',
            'description' => 'Registro recente',
        ]);

        $this->artisan('audit:prune', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('audit_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recente->id]);
    }
}
