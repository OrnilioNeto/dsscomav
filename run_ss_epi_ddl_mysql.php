<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "================================================\n";
echo "EXECUTANDO CRIAÇÃO DE TABELAS SS_* NO MYSQL...\n";
echo "================================================\n";

try {
    // 1. Tabela ss_epi
    if (!Schema::hasTable('ss_epi')) {
        echo "Criação da tabela 'ss_epi'...\n";
        Schema::create('ss_epi', function (Blueprint $table) {
            $table->id('ss_e_nb_id');
            $table->string('ss_e_tx_grupo', 255);
            $table->string('ss_e_tx_subgrupo', 255)->nullable();
            $table->string('ss_e_tx_item', 255)->nullable();
            $table->text('ss_e_tx_descricao')->nullable();
            $table->string('ss_e_tx_fabricante', 255)->nullable();
            $table->string('ss_e_tx_ca', 50)->nullable();
            $table->date('ss_e_tx_validade_ca')->nullable();
            $table->integer('ss_e_nb_vida_util_dias')->default(0);
            $table->string('ss_e_tx_status', 30)->default('ativo');
            $table->string('ss_e_tx_cadastro_tipo', 30)->default('estoque');
            $table->text('ss_e_tx_foto')->nullable();
            $table->string('ss_e_tx_modelo', 255)->nullable();
            $table->integer('ss_e_nb_userCadastro')->nullable();
            $table->dateTime('ss_e_tx_dataCadastro')->nullable();
        });
        echo "   -> Tabela 'ss_epi' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_epi' já existe.\n";
    }

    // 2. Tabela ss_colaborador
    if (!Schema::hasTable('ss_colaborador')) {
        echo "Criação da tabela 'ss_colaborador'...\n";
        Schema::create('ss_colaborador', function (Blueprint $table) {
            $table->id('ss_c_nb_id');
            $table->string('ss_c_tx_nome', 255);
            $table->string('ss_c_tx_cpf', 14)->nullable();
            $table->string('ss_c_tx_matricula', 50)->nullable();
            $table->string('ss_c_tx_cargo', 255)->nullable();
            $table->string('ss_c_tx_status', 30)->default('ativo');
            $table->integer('ss_c_nb_empresa_id')->nullable();
        });
        echo "   -> Tabela 'ss_colaborador' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_colaborador' já existe.\n";
    }

    // 3. Tabela ss_epi_estoque
    if (!Schema::hasTable('ss_epi_estoque')) {
        echo "Criação da tabela 'ss_epi_estoque'...\n";
        Schema::create('ss_epi_estoque', function (Blueprint $table) {
            $table->id('ss_e_nb_id');
            $table->integer('ss_e_nb_epi_id');
            $table->integer('ss_e_nb_empresa_id')->nullable();
            $table->integer('ss_e_nb_quantidade');
            $table->string('ss_e_tx_tipo', 30)->default('entrada');
            $table->decimal('ss_e_db_valor_unitario', 10, 2)->nullable();
            $table->decimal('ss_e_db_valor_total', 10, 2)->nullable();
            $table->date('ss_e_tx_data_recebimento')->nullable();
            $table->date('ss_e_tx_validade')->nullable();
            $table->string('ss_e_tx_chave_nf', 100)->nullable();
            $table->string('ss_e_tx_fornecedor', 255)->nullable();
            $table->dateTime('ss_e_tx_data');
            $table->text('ss_e_tx_motivo')->nullable();
            $table->text('ss_e_tx_foto')->nullable();
            $table->integer('ss_e_nb_userCadastro')->nullable();

            $table->index(['ss_e_nb_epi_id', 'ss_e_nb_empresa_id']);
        });
        echo "   -> Tabela 'ss_epi_estoque' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_epi_estoque' já existe.\n";
    }

    // 4. Tabela ss_epi_entrega
    if (!Schema::hasTable('ss_epi_entrega')) {
        echo "Criação da tabela 'ss_epi_entrega'...\n";
        Schema::create('ss_epi_entrega', function (Blueprint $table) {
            $table->id('ss_e_nb_id');
            $table->integer('ss_e_nb_colaborador_id');
            $table->integer('ss_e_nb_epi_id');
            $table->integer('ss_e_nb_empresa_id')->nullable();
            $table->date('ss_e_tx_data_entrega');
            $table->integer('ss_e_nb_quantidade');
            $table->date('ss_e_tx_vencimento')->nullable();
            $table->string('ss_e_tx_status', 30)->default('ativo');
            $table->longText('ss_e_tx_assinatura')->nullable();
            $table->text('ss_e_tx_foto')->nullable();
            $table->text('ss_e_tx_observacao')->nullable();
            $table->string('ss_e_tx_justificativa_exclusao', 255)->nullable();
            $table->integer('ss_e_nb_userCadastro')->nullable();
            $table->dateTime('ss_e_tx_dataCadastro')->nullable();

            $table->index(['ss_e_nb_colaborador_id', 'ss_e_nb_epi_id']);
        });
        echo "   -> Tabela 'ss_epi_entrega' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_epi_entrega' já existe.\n";
    }

    // 5. Tabela ss_kit
    if (!Schema::hasTable('ss_kit')) {
        echo "Criação da tabela 'ss_kit'...\n";
        Schema::create('ss_kit', function (Blueprint $table) {
            $table->id('ss_k_nb_id');
            $table->string('ss_k_tx_nome', 255);
            $table->string('ss_k_tx_status', 30)->default('ativo');
        });
        echo "   -> Tabela 'ss_kit' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_kit' já existe.\n";
    }

    // 6. Tabela ss_kit_item
    if (!Schema::hasTable('ss_kit_item')) {
        echo "Criação da tabela 'ss_kit_item'...\n";
        Schema::create('ss_kit_item', function (Blueprint $table) {
            $table->id('ss_ki_nb_id');
            $table->integer('ss_ki_nb_kit_id');
            $table->integer('ss_ki_nb_epi_id');
            $table->integer('ss_ki_nb_quantidade')->default(1);

            $table->index(['ss_ki_nb_kit_id', 'ss_ki_nb_epi_id']);
        });
        echo "   -> Tabela 'ss_kit_item' criada com SUCESSO!\n";
    } else {
        echo "   -> Tabela 'ss_kit_item' já existe.\n";
    }

    // [DESATIVADO] População de EPIs Universais Padrão
    // echo "\nExecutando Carga Inicial (Seed) dos EPIs Padrão...\n";
    // $defaultItems = [...];
    // foreach (...)

    // Sincronizar colaboradores a partir da tabela `users`
    if (Schema::hasTable('users')) {
        $users = DB::table('users')->get();
        foreach ($users as $u) {
            $exists = DB::table('ss_colaborador')->where('ss_c_tx_cpf', $u->cpf)->exists();
            if (!$exists) {
                DB::table('ss_colaborador')->insert([
                    'ss_c_tx_nome' => $u->nome,
                    'ss_c_tx_cpf' => $u->cpf,
                    'ss_c_tx_matricula' => 'MAT-' . str_pad($u->id, 5, '0', STR_PAD_LEFT),
                    'ss_c_tx_cargo' => $u->cargo ?? 'Funcionário',
                    'ss_c_tx_status' => $u->status ?? 'ativo',
                    'ss_c_nb_empresa_id' => 0,
                ]);
            }
        }
    }

    echo "\n================================================\n";
    echo "✓ TABELAS E SEEDS CRIADOS COM SUCESSO NO MYSQL!\n";
    echo "================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
