<?php

namespace Tests\Feature;

use App\Console\Commands\TenantCreate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_cria_tenant_admin_e_modulos(): void
    {
        $this->artisan('tenant:create', [
            '--name' => 'Transportes XYZ',
            '--admin-cpf' => '55555555555',
            '--admin-senha' => 'Senha123',
            '--admin-email' => 'admin@xyz.com',
        ])->assertSuccessful();

        $tenant = Tenant::where('slug', 'transportes-xyz')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Transportes XYZ', $tenant->nome);
        $this->assertEquals('ativo', $tenant->status);
        $this->assertTrue($tenant->hasModule('trainings'));
        $this->assertTrue($tenant->hasModule('epi'));
        $this->assertEquals(count(config('modules', [])), $tenant->modules()->where('enabled', true)->count());

        $admin = User::where('cpf', '55555555555')->first();
        $this->assertNotNull($admin);
        $this->assertEquals($tenant->id, $admin->tenant_id);
        $this->assertEquals('admin', $admin->role?->nome);
    }

    public function test_comando_requer_cpf_e_senha(): void
    {
        $this->artisan('tenant:create', [
            '--name' => 'Sem Dados',
        ])->assertFailed();
    }

    public function test_comando_gera_slug_unico_em_conflito(): void
    {
        Tenant::create(['nome' => 'ABC', 'slug' => 'abc', 'status' => 'ativo']);

        $this->artisan('tenant:create', [
            '--name' => 'ABC',
            '--admin-cpf' => '55555555556',
            '--admin-senha' => 'Senha123',
        ])->assertSuccessful();

        $this->assertNotNull(Tenant::where('slug', 'abc')->first());
        $this->assertNotNull(Tenant::where('slug', 'abc-2')->first());
    }
}