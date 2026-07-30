<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela ss_epi
        if (!Schema::hasTable('ss_epi')) {
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
        }

        // 2. Tabela ss_colaborador
        if (!Schema::hasTable('ss_colaborador')) {
            Schema::create('ss_colaborador', function (Blueprint $table) {
                $table->id('ss_c_nb_id');
                $table->string('ss_c_tx_nome', 255);
                $table->string('ss_c_tx_cpf', 14)->nullable();
                $table->string('ss_c_tx_matricula', 50)->nullable();
                $table->string('ss_c_tx_cargo', 255)->nullable();
                $table->string('ss_c_tx_status', 30)->default('ativo');
                $table->integer('ss_c_nb_empresa_id')->nullable();
            });
        }

        // 3. Tabela ss_epi_estoque
        if (!Schema::hasTable('ss_epi_estoque')) {
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
        }

        // 4. Tabela ss_epi_entrega
        if (!Schema::hasTable('ss_epi_entrega')) {
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
        }

        // 5. Tabela ss_kit
        if (!Schema::hasTable('ss_kit')) {
            Schema::create('ss_kit', function (Blueprint $table) {
                $table->id('ss_k_nb_id');
                $table->string('ss_k_tx_nome', 255);
                $table->string('ss_k_tx_status', 30)->default('ativo');
            });
        }

        // 6. Tabela ss_kit_item
        if (!Schema::hasTable('ss_kit_item')) {
            Schema::create('ss_kit_item', function (Blueprint $table) {
                $table->id('ss_ki_nb_id');
                $table->integer('ss_ki_nb_kit_id');
                $table->integer('ss_ki_nb_epi_id');
                $table->integer('ss_ki_nb_quantidade')->default(1);

                $table->index(['ss_ki_nb_kit_id', 'ss_ki_nb_epi_id']);
            });
        }

        // [DESATIVADO] Execute initial seed for default EPI items
        // $this->seedDefaultEpis();

        // Synchronize collaborators from `users` table if `ss_colaborador` is empty
        $this->syncColaboradoresFromUsers();
    }

    private function seedDefaultEpis(): void
    {
        $defaultItems = [
            // PROTEÇÃO DA CABEÇA
            [
                'ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA',
                'ss_e_tx_subgrupo' => 'Capacetes',
                'ss_e_tx_item' => 'Capacete de Segurança',
                'ss_e_tx_descricao' => 'capacete para proteção contra impactos de objetos sobre o crânio',
                'ss_e_nb_vida_util_dias' => 365,
            ],
            [
                'ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA',
                'ss_e_tx_subgrupo' => 'Capacetes',
                'ss_e_tx_item' => 'Capacete de Segurança Com Carneira',
                'ss_e_tx_descricao' => 'capacete para proteção contra impactos de objetos sobre o crânio',
                'ss_e_nb_vida_util_dias' => 365,
            ],

            // EPI PARA PROTEÇÃO DOS OLHOS E FACE
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS OLHOS E FACE',
                'ss_e_tx_subgrupo' => 'Óculos de Segurança',
                'ss_e_tx_item' => 'Óculos de Segurança Anti-Embaçante',
                'ss_e_tx_descricao' => 'óculos para proteção dos olhos contra impactos de partículas volantes',
                'ss_e_nb_vida_util_dias' => 180,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS OLHOS E FACE',
                'ss_e_tx_subgrupo' => 'Óculos de Segurança',
                'ss_e_tx_item' => 'Óculos Ampla Visão',
                'ss_e_tx_descricao' => 'óculos para proteção dos olhos contra impactos de partículas volantes',
                'ss_e_nb_vida_util_dias' => 180,
            ],

            // EPI PARA PROTEÇÃO AUDITIVA
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO AUDITIVA',
                'ss_e_tx_subgrupo' => 'Protetores Auriculares',
                'ss_e_tx_item' => 'Protetor Auricular de Silicone Tipo Plug',
                'ss_e_tx_descricao' => 'para proteção do sistema auditivo contra níveis de pressão sonora superiores ao estabelecido na NR-15, Anexos nº 1 e 2',
                'ss_e_nb_vida_util_dias' => 90,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO AUDITIVA',
                'ss_e_tx_subgrupo' => 'Protetores Auriculares',
                'ss_e_tx_item' => 'Protetor Auricular Abafador Concha',
                'ss_e_tx_descricao' => 'para proteção do sistema auditivo contra níveis de pressão sonora superiores ao estabelecido na NR-15, Anexos nº 1 e 2; e',
                'ss_e_nb_vida_util_dias' => 365,
            ],

            // EPI PARA PROTEÇÃO RESPIRATÓRIA
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO RESPIRATÓRIA',
                'ss_e_tx_subgrupo' => 'Respiradores e Máscaras',
                'ss_e_tx_item' => 'Respirador purificador de ar não motorizado',
                'ss_e_tx_descricao' => 'com filtros combinados para proteção das vias respiratórias contra gases e vapores',
                'ss_e_nb_vida_util_dias' => 180,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO RESPIRATÓRIA',
                'ss_e_tx_subgrupo' => 'Respiradores e Máscaras',
                'ss_e_tx_item' => 'Máscara Descartável Pff2',
                'ss_e_tx_descricao' => 'peça semifacial filtrante para partículas PFF2 para proteção das vias respiratórias contra poeiras, névoas e fumos',
                'ss_e_nb_vida_util_dias' => 30,
            ],

            // EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES',
                'ss_e_tx_subgrupo' => 'Luvas de Proteção',
                'ss_e_tx_item' => 'luvas pvc',
                'ss_e_tx_descricao' => 'luvas para proteção das mãos contra agentes químicos',
                'ss_e_nb_vida_util_dias' => 90,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES',
                'ss_e_tx_subgrupo' => 'Luvas de Proteção',
                'ss_e_tx_item' => 'luvas nitrilica',
                'ss_e_tx_descricao' => 'luvas para proteção das mãos contra agentes químicos',
                'ss_e_nb_vida_util_dias' => 90,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES',
                'ss_e_tx_subgrupo' => 'Luvas de Proteção',
                'ss_e_tx_item' => 'luvas algodao',
                'ss_e_tx_descricao' => 'luvas para proteção das mãos contra vibrações',
                'ss_e_nb_vida_util_dias' => 60,
            ],

            // EPI PARA PROTEÇÃO DOS MEMBROS INFERIORES
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS INFERIORES',
                'ss_e_tx_subgrupo' => 'Calçados de Segurança',
                'ss_e_tx_item' => 'Bota Botina Bico PVC',
                'ss_e_tx_descricao' => 'calçado para proteção contra impactos de quedas de objetos sobre os artelhos',
                'ss_e_nb_vida_util_dias' => 365,
            ],

            // EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL',
                'ss_e_tx_subgrupo' => 'Cintos e Talabartes',
                'ss_e_tx_item' => 'Cinto Paraquedista',
                'ss_e_tx_descricao' => 'Cinturão de segurança com dispositivo trava-queda para proteção do usuário contra quedas em operações com movimentação vertical ou horizontal',
                'ss_e_nb_vida_util_dias' => 365,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL',
                'ss_e_tx_subgrupo' => 'Cintos e Talabartes',
                'ss_e_tx_item' => 'Cinto Paraquedista + Talabarte',
                'ss_e_tx_descricao' => 'cinturão de segurança com talabarte para proteção do usuário contra riscos de queda no posicionamento em trabalhos em altura',
                'ss_e_nb_vida_util_dias' => 365,
            ],
            [
                'ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL',
                'ss_e_tx_subgrupo' => 'Cintos e Talabartes',
                'ss_e_tx_item' => 'Talabarte',
                'ss_e_tx_descricao' => 'talabarte para proteção do usuário contra riscos de queda no posicionamento em trabalhos em altura',
                'ss_e_nb_vida_util_dias' => 365,
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($defaultItems as $item) {
            $exists = DB::table('ss_epi')
                ->where('ss_e_tx_grupo', $item['ss_e_tx_grupo'])
                ->where('ss_e_tx_item', $item['ss_e_tx_item'])
                ->exists();

            if (!$exists) {
                DB::table('ss_epi')->insert([
                    'ss_e_tx_grupo' => $item['ss_e_tx_grupo'],
                    'ss_e_tx_subgrupo' => $item['ss_e_tx_subgrupo'],
                    'ss_e_tx_item' => $item['ss_e_tx_item'],
                    'ss_e_tx_descricao' => $item['ss_e_tx_descricao'],
                    'ss_e_nb_vida_util_dias' => $item['ss_e_nb_vida_util_dias'],
                    'ss_e_tx_status' => 'ativo',
                    'ss_e_tx_cadastro_tipo' => 'universal',
                    'ss_e_tx_dataCadastro' => $now,
                ]);
            }
        }
    }

    private function syncColaboradoresFromUsers(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('ss_kit_item');
        Schema::dropIfExists('ss_kit');
        Schema::dropIfExists('ss_epi_entrega');
        Schema::dropIfExists('ss_epi_estoque');
        Schema::dropIfExists('ss_colaborador');
        Schema::dropIfExists('ss_epi');
    }
};
