<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Models\FrotaMotorista;

trait FrotaTestHelpers
{
    protected function criarRole(string $nome, array $permissoes = []): Role
    {
        $role = Role::firstOrCreate(['nome' => $nome], ['descricao' => $nome]);

        foreach ($permissoes as $modulo => $acoes) {
            RolePermission::firstOrCreate(
                ['role_id' => $role->id, 'module' => $modulo],
                ['can_view' => (bool) ($acoes['view'] ?? false), 'can_edit' => (bool) ($acoes['edit'] ?? false)]
            );
        }

        return $role;
    }

    protected function criarUsuario(
        string $cpf,
        string $tipo = 'motorista',
        ?Role $role = null,
        string $nome = 'Motorista Teste'
    ): User {
        return User::create([
            'nome' => $nome,
            'cpf' => $cpf,
            'email' => $cpf.'@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => $tipo,
            'status' => 'ativo',
            'role_id' => $role?->id,
        ]);
    }

    protected function configurarFrota(array $overrides = []): FrotaConfiguracao
    {
        return FrotaConfiguracao::create(array_merge([
            'ativo' => true,
            'base_url' => 'https://sandbox.example.test/aticapi',
            'account_code' => '1234',
            'subscription_key' => 'chave-teste',
            'usuario' => 'usuario@companhia',
            'senha' => 'senha-teste',
            'ttl_segundos' => 60,
            'grace_minutos' => 30,
            'jornada_maxima_horas' => 12,
            'tipos_motorista' => ['motorista', 'motorista_monitor'],
            'fail_mode' => 'grace',
        ], $overrides));
    }

    protected function criarMotorista(User $user, array $overrides = []): FrotaMotorista
    {
        return FrotaMotorista::create(array_merge([
            'code' => 100,
            'name' => $user->nome,
            'cpf' => preg_replace('/\D/', '', (string) $user->cpf),
            'user_id' => $user->id,
            'status' => FrotaMotorista::STATUS_DESCONHECIDO,
            'ativo' => true,
        ], $overrides));
    }
}
