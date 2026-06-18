<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\EmployeeTraining;
use App\Models\EmployeeEpi;
use Illuminate\Support\Str;

echo "================================================\n";
echo "Iniciando Teste da Ficha do Funcionário...\n";
echo "================================================\n\n";

try {
    // 1. Buscar um usuário de teste
    $user = User::first();
    if (!$user) {
        throw new Exception("Nenhum usuário cadastrado no banco de dados para rodar o teste!");
    }
    echo "1. Usuário selecionado: " . $user->nome . "\n";

    // 2. Verificar se o token de QR Code existe
    if (empty($user->qrcode_token)) {
        $user->update(['qrcode_token' => Str::random(32)]);
        echo "   -> Token QR Code estava vazio, gerado com sucesso.\n";
    }
    echo "2. Token QR Code: " . $user->qrcode_token . "\n";
    echo "   URL da Ficha: " . $user->ficha_url . "\n";
    echo "   URL do QR Code: " . $user->ficha_qr_code_url . "\n";

    // 3. Cadastrar um Treinamento Externo de teste
    echo "3. Cadastrando treinamento externo de teste (NR-20)... \n";
    $training = $user->employeeTrainings()->create([
        'nome' => 'NR-20 Reciclagem Teste',
        'data_treinamento' => now()->subYear(),
        'data_validade' => now()->addYear(),
        'observacoes' => 'Teste automatizado de criação'
    ]);
    echo "   -> ID do treinamento: " . $training->id . "\n";
    echo "   -> Status expirado? " . ($training->isExpired() ? 'SIM' : 'NÃO') . "\n";

    // 4. Cadastrar um Treinamento Externo já expirado
    echo "4. Cadastrando treinamento externo expirado (NR-35)... \n";
    $expiredTraining = $user->employeeTrainings()->create([
        'nome' => 'NR-35 Trabalho em Altura Teste',
        'data_treinamento' => now()->subYears(3),
        'data_validade' => now()->subYear(),
        'observacoes' => 'Teste de expiração'
    ]);
    echo "   -> ID do treinamento expirado: " . $expiredTraining->id . "\n";
    echo "   -> Status expirado? " . ($expiredTraining->isExpired() ? 'SIM' : 'NÃO') . "\n";

    if (!$expiredTraining->isExpired()) {
        throw new Exception("Erro: O treinamento de NR-35 deveria estar marcado como expirado!");
    }

    // 5. Cadastrar um EPI de teste
    echo "5. Cadastrando entrega de EPI de teste... \n";
    $epi = $user->employeeEpis()->create([
        'nome' => 'Óculos de Proteção Teste',
        'ca' => '99999',
        'quantidade' => 2,
        'data_entrega' => now(),
        'observacoes' => 'Entrega de teste'
    ]);
    echo "   -> ID do EPI: " . $epi->id . "\n";

    // 6. Verificar listagem de relacionamentos do usuário
    echo "6. Validando relacionamentos no Model User...\n";
    $userReloaded = User::with(['employeeTrainings', 'employeeEpis'])->find($user->id);
    
    $hasTraining = $userReloaded->employeeTrainings->contains('id', $training->id);
    $hasEpi = $userReloaded->employeeEpis->contains('id', $epi->id);

    if (!$hasTraining) {
        throw new Exception("Erro: Relacionamento de treinamentos não retornou o treinamento criado!");
    }
    if (!$hasEpi) {
        throw new Exception("Erro: Relacionamento de EPIs não retornou o EPI criado!");
    }
    echo "   -> Todos os relacionamentos estão corretos!\n";

    // Limpar os dados de teste para não poluir o banco
    $training->delete();
    $expiredTraining->delete();
    $epi->delete();
    echo "7. Dados de teste limpos com sucesso.\n\n";

    echo "================================================\n";
    echo "✓ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERRO NO TESTE: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
