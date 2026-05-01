<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Criar role super_admin
$role = Role::firstOrCreate(
    ['nome' => 'super_admin'],
    ['descricao' => 'Super Admin']
);

// Deletar usuário anterior se existir
User::where('email', 'super@admin.com')->delete();

// Criar novo super admin
$user = User::create([
    'nome' => 'Super Admin',
    'cpf' => '12345678901',
    'email' => 'super@admin.com',
    'password' => Hash::make('password'),
    'tipo_usuario' => 'funcionario',
    'status' => 'ativo',
    'role_id' => $role->id,
]);

echo "\n✓ Super Admin criado com sucesso!\n";
echo "Email: " . $user->email . "\n";
echo "CPF: " . $user->cpf . "\n";
echo "Senha: password\n\n";
