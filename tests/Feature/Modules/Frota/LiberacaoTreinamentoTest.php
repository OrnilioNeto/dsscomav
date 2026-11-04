<?php

namespace Tests\Feature\Modules\Frota;

use App\Models\User;
use App\Modules\Frota\Models\FrotaLiberacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FrotaTestHelpers;
use Tests\TestCase;

class LiberacaoTreinamentoTest extends TestCase
{
    use FrotaTestHelpers;
    use RefreshDatabase;

    private function admin(): User
    {
        $role = $this->criarRole('admin', [
            'frota' => ['view' => true, 'edit' => true],
        ]);

        return $this->criarUsuario('11111111111', 'funcionario', $role, 'Gestor Teste');
    }

    public function test_gestor_libera_acesso_com_motivo_e_validade(): void
    {
        $admin = $this->admin();
        $motorista = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $resposta = $this->actingAs($admin)->post(route('admin.frota.liberacoes.store'), [
            'user_ids' => [$motorista->id],
            'motivo' => 'Motorista em viagem sem sinal no rastreador',
            'fim' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $resposta->assertRedirect();

        $this->assertDatabaseHas('frota_liberacoes_treinamento', [
            'user_id' => $motorista->id,
            'liberado_por' => $admin->id,
            'motivo' => 'Motorista em viagem sem sinal no rastreador',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'treinamento_acesso_liberado',
            'module' => 'frota',
        ]);
    }

    public function test_motivo_e_opcional(): void
    {
        $admin = $this->admin();
        $motorista = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->actingAs($admin)->post(route('admin.frota.liberacoes.store'), [
            'user_ids' => [$motorista->id],
            'fim' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('frota_liberacoes_treinamento', [
            'user_id' => $motorista->id,
            'motivo' => null,
        ]);
    }

    public function test_validade_no_passado_e_rejeitada(): void
    {
        $admin = $this->admin();
        $motorista = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->actingAs($admin)->post(route('admin.frota.liberacoes.store'), [
            'user_ids' => [$motorista->id],
            'fim' => now()->subDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('fim');
    }

    public function test_gestor_revoga_liberacao(): void
    {
        $admin = $this->admin();
        $motorista = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $liberacao = FrotaLiberacao::create([
            'user_id' => $motorista->id,
            'liberado_por' => $admin->id,
            'inicio' => now()->subHour(),
            'fim' => now()->addHour(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.frota.liberacoes.revogar', $liberacao))
            ->assertRedirect();

        $liberacao->refresh();

        $this->assertNotNull($liberacao->revogado_em);
        $this->assertSame($admin->id, $liberacao->revogado_por);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'treinamento_acesso_revogado',
            'module' => 'frota',
        ]);
    }

    public function test_usuario_comum_nao_libera_acesso(): void
    {
        $motorista = $this->criarUsuario('10763021490', 'motorista', $this->criarRole('usuario'));

        $this->actingAs($motorista)->post(route('admin.frota.liberacoes.store'), [
            'user_ids' => [$motorista->id],
            'fim' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertForbidden();
    }
}
