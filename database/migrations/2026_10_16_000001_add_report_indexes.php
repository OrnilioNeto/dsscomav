<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de apoio aos relatórios gerenciais (auditoria e avaliações),
 * que filtram por data de início/conclusão do progresso e emissão de
 * certificados em bases grandes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->adicionarIndice('user_progress', 'data_inicio', 'user_progress_data_inicio_index');
        $this->adicionarIndice('user_progress', 'data_conclusao', 'user_progress_data_conclusao_index');
        $this->adicionarIndice('certificates', 'data_emissao', 'certificates_data_emissao_index');
    }

    public function down(): void
    {
        $this->removerIndice('user_progress', 'user_progress_data_inicio_index');
        $this->removerIndice('user_progress', 'user_progress_data_conclusao_index');
        $this->removerIndice('certificates', 'certificates_data_emissao_index');
    }

    private function adicionarIndice(string $tabela, string $coluna, string $nome): void
    {
        if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, $coluna)) {
            return;
        }

        if (collect(Schema::getIndexes($tabela))->pluck('name')->contains($nome)) {
            return;
        }

        Schema::table($tabela, function (Blueprint $table) use ($coluna, $nome) {
            $table->index($coluna, $nome);
        });
    }

    private function removerIndice(string $tabela, string $nome): void
    {
        if (! Schema::hasTable($tabela)) {
            return;
        }

        if (! collect(Schema::getIndexes($tabela))->pluck('name')->contains($nome)) {
            return;
        }

        Schema::table($tabela, function (Blueprint $table) use ($nome) {
            $table->dropIndex($nome);
        });
    }
};
