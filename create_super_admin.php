<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create super_admin role if not exists
$role = Role::firstOrCreate(
    ['nome' => 'super_admin'],
    ['descricao' => 'Super Admin']
);

// Create super admin user
User::create([
    'nome' => 'Super Admin',
    'cpf' => '00000000000',
    'email' => 'super@admin.com',
    'password' => Hash::make('password'),
    'tipo_usuario' => 'funcionario',
    'status' => 'ativo',
    'role_id' => $role->id,
]);

echo "✓ Super Admin criado com sucesso!\n";
echo "Email: super@admin.com\n";
echo "Senha: password\n";
