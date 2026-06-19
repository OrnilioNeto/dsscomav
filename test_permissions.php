<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Str;

echo "================================================\n";
echo "Iniciando Teste do Sistema de Permissões (RBAC)...\n";
echo "================================================\n\n";

try {
    // 1. Limpar registros de teste antigos se existirem
    Role::where('nome', 'test_supervisor')->delete();
    User::where('email', 'test_supervisor@example.com')->delete();

    // 2. Criar perfil de teste: Supervisor
    echo "1. Criando perfil de teste 'test_supervisor'...\n";
    $role = Role::create([
        'nome' => 'test_supervisor',
        'descricao' => 'Supervisor de Teste'
    ]);
    echo "   -> Perfil criado com ID: " . $role->id . "\n";

    // 3. Inicializar permissões vazias (como feito no PermissionController)
    $modules = ['users', 'trainings', 'certificates', 'rankings', 'splash', 'permissions'];
    foreach ($modules as $module) {
        $role->permissions()->create([
            'module' => $module,
            'can_view' => false,
            'can_edit' => false,
        ]);
    }
    echo "   -> Permissões inicializadas como falso.\n";

    // 4. Criar um usuário vinculado ao perfil de teste
    echo "2. Criando usuário de teste com perfil 'test_supervisor'...\n";
    $user = User::create([
        'nome' => 'Supervisor Teste',
        'cpf' => '99999999999',
        'email' => 'test_supervisor@example.com',
        'password' => bcrypt('password123'),
        'tipo_usuario' => 'funcionario',
        'role_id' => $role->id,
        'status' => 'ativo',
    ]);
    echo "   -> Usuário criado com ID: " . $user->id . "\n";

    // 5. Testar que por padrão o usuário não tem nenhuma permissão
    echo "3. Validando permissões padrão (devem ser todas falsas)...\n";
    foreach ($modules as $module) {
        if ($user->hasPermission($module, 'view')) {
            throw new Exception("Erro: Usuário tem permissão de visualização no módulo '$module', mas deveria ser falsa!");
        }
        if ($user->hasPermission($module, 'edit')) {
            throw new Exception("Erro: Usuário tem permissão de edição no módulo '$module', mas deveria ser falsa!");
        }
    }
    echo "   -> Sucesso: Permissões padrão estão todas bloqueadas.\n";

    // 6. Testar isAdmin()
    echo "4. Validando método isAdmin() para o perfil customizado...\n";
    if (!$user->isAdmin()) {
        throw new Exception("Erro: Usuário com perfil customizado diferente de 'usuario' deveria ser considerado admin!");
    }
    echo "   -> Sucesso: isAdmin() retornou true para o supervisor de teste.\n";

    // 7. Atualizar permissões: Habilitar visualização de usuários e edição de treinamentos
    echo "5. Atualizando permissões do perfil (visualizar usuários, editar treinamentos)...\n";
    $role->permissions()->where('module', 'users')->update(['can_view' => true]);
    $role->permissions()->where('module', 'trainings')->update(['can_view' => true, 'can_edit' => true]);

    // Recarregar os relacionamentos do usuário
    $user->load('role.permissions');

    // 8. Validar novas permissões
    echo "6. Validando novas permissões aplicadas...\n";
    
    // Módulo users: deve poder visualizar, mas NÃO editar
    if (!$user->hasPermission('users', 'view')) {
        throw new Exception("Erro: Usuário deveria poder visualizar usuários!");
    }
    if ($user->hasPermission('users', 'edit')) {
        throw new Exception("Erro: Usuário NÃO deveria poder editar usuários!");
    }

    // Módulo trainings: deve poder visualizar E editar
    if (!$user->hasPermission('trainings', 'view')) {
        throw new Exception("Erro: Usuário deveria poder visualizar treinamentos!");
    }
    if (!$user->hasPermission('trainings', 'edit')) {
        throw new Exception("Erro: Usuário deveria poder editar treinamentos!");
    }

    // Outros módulos: continuam bloqueados
    if ($user->hasPermission('certificates', 'view')) {
        throw new Exception("Erro: Usuário não deveria acessar certificados!");
    }
    echo "   -> Sucesso: Permissões dinâmicas validadas com sucesso.\n";

    // 9. Validar Super Admin bypass
    echo "7. Validando bypass do Super Admin...\n";
    // Buscar ou criar um super admin
    $superAdminRole = Role::firstOrCreate(['nome' => 'super_admin'], ['descricao' => 'Super Administrador']);
    $superAdmin = User::whereHas('role', function($q) { $q->where('nome', 'super_admin'); })->first();
    
    if (!$superAdmin) {
        $superAdmin = User::create([
            'nome' => 'Super Admin Teste',
            'cpf' => '88888888888',
            'email' => 'superadmin_teste@example.com',
            'password' => bcrypt('password123'),
            'tipo_usuario' => 'funcionario',
            'role_id' => $superAdminRole->id,
            'status' => 'ativo',
        ]);
        $createdSuperAdmin = true;
    } else {
        $createdSuperAdmin = false;
    }

    // Verificar se super admin tem permissão para tudo
    foreach ($modules as $module) {
        if (!$superAdmin->hasPermission($module, 'view') || !$superAdmin->hasPermission($module, 'edit')) {
            throw new Exception("Erro: Super Admin foi bloqueado no módulo '$module'!");
        }
    }
    echo "   -> Sucesso: Super Admin possui acesso irrestrito a todos os módulos.\n";

    // 10. Limpeza
    echo "8. Limpando dados de teste...\n";
    $user->delete();
    $role->delete(); // Deleta em cascata na tabela role_permissions
    if ($createdSuperAdmin) {
        $superAdmin->delete();
    }
    echo "   -> Banco de dados limpo.\n\n";

    echo "================================================\n";
    echo "✓ TODOS OS TESTES PASSARAM COM SUCESSO!\n";
    echo "================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERRO NO TESTE: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    
    // Tentar limpar em caso de falha
    try {
        User::where('email', 'test_supervisor@example.com')->delete();
        Role::where('nome', 'test_supervisor')->delete();
        if (isset($createdSuperAdmin) && $createdSuperAdmin) {
            User::where('email', 'superadmin_teste@example.com')->delete();
        }
    } catch (Exception $e2) {}
    
    exit(1);
}
