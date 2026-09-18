<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $cpf, ?int $tenantId): User
    {
        return User::create([
            'nome' => 'Usuário '.$cpf,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'tenant_id' => $tenantId,
        ]);
    }

    public function test_backfill_identifica_tenant_raiz_pelo_slug_e_nao_pelo_primeiro(): void
    {
        // Cenário do bug: cliente novo criado ANTES do backfill.
        $clienteNovo = Tenant::create(['nome' => '64bits', 'slug' => '64bits', 'status' => 'ativo']);

        // Dados legados (cliente raiz) ainda sem tenant.
        $userLegado = $this->criarUsuario('11111111111', null);
        $userNovo = $this->criarUsuario('22222222222', $clienteNovo->id);

        $this->artisan('tenant:backfill', ['--name' => 'Cliente Raiz', '--slug' => 'cliente'])
            ->assertExitCode(0);

        $raiz = Tenant::where('slug', 'cliente')->first();

        $this->assertNotNull($raiz, 'O tenant raiz (slug cliente) deve ser criado.');
        $this->assertSame(2, Tenant::count());
        $this->assertSame($raiz->id, $userLegado->fresh()->tenant_id);
        $this->assertSame($clienteNovo->id, $userNovo->fresh()->tenant_id, 'Dados do cliente novo não devem ser alterados.');
    }

    public function test_backfill_dry_run_nao_altera_dados(): void
    {
        $user = $this->criarUsuario('33333333333', null);

        $this->artisan('tenant:backfill', ['--name' => 'Cliente Raiz', '--slug' => 'cliente', '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($user->fresh()->tenant_id, 'Dry-run não deve alterar dados.');
        $this->assertNull(Tenant::where('slug', 'cliente')->first(), 'Dry-run não deve criar tenant.');
    }

    public function test_saas_doctor_roda_com_um_tenant(): void
    {
        Tenant::create(['nome' => 'Cliente Atual', 'slug' => 'cliente', 'status' => 'ativo']);

        $this->artisan('saas:doctor')->assertExitCode(0);
    }

    public function test_saas_doctor_falha_com_flag_desligada_e_varios_tenants(): void
    {
        config(['saas.enabled' => false]);

        Tenant::create(['nome' => 'Empresa A', 'slug' => 'empresa-a', 'status' => 'ativo']);
        Tenant::create(['nome' => 'Empresa B', 'slug' => 'empresa-b', 'status' => 'ativo']);

        $this->artisan('saas:doctor')->assertExitCode(1);
    }
}
