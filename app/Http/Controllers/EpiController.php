<?php

namespace App\Http\Controllers;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiDevolucao;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\EpiKit;
use App\Models\EpiKitItem;
use App\Models\EpiVariacao;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EpiController extends Controller
{
    private static $tablesEnsured = false;

    /**
     * Garante auto-criação e seeder automatizado das 6 tabelas em qualquer ambiente/banco.
     */
    private function ensureTablesExist(): void
    {
        if (self::$tablesEnsured) {
            return;
        }
        self::$tablesEnsured = true;

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

        if (!Schema::hasTable('ss_kit')) {
            Schema::create('ss_kit', function (Blueprint $table) {
                $table->id('ss_k_nb_id');
                $table->string('ss_k_tx_nome', 255);
                $table->string('ss_k_tx_status', 30)->default('ativo');
            });
        }

        if (!Schema::hasTable('ss_kit_item')) {
            Schema::create('ss_kit_item', function (Blueprint $table) {
                $table->id('ss_ki_nb_id');
                $table->integer('ss_ki_nb_kit_id');
                $table->integer('ss_ki_nb_epi_id');
                $table->integer('ss_ki_nb_quantidade')->default(1);

                $table->index(['ss_ki_nb_kit_id', 'ss_ki_nb_epi_id']);
            });
        }

        if (!Schema::hasTable('ss_filial')) {
            Schema::create('ss_filial', function (Blueprint $table) {
                $table->id('ss_f_nb_id');
                $table->string('ss_f_tx_nome', 255);
                $table->string('ss_f_tx_codigo', 50)->nullable();
                $table->string('ss_f_tx_cidade', 255)->nullable();
                $table->string('ss_f_tx_status', 30)->default('ativo');
            });
        }

        // 8. Tabela ss_epi_variacao (Variações de EPI: tamanhos, cores, etc.)
        if (!Schema::hasTable('ss_epi_variacao')) {
            Schema::create('ss_epi_variacao', function (Blueprint $table) {
                $table->id('ss_ev_nb_id');
                $table->integer('ss_ev_nb_epi_id');
                $table->string('ss_ev_tx_nome', 255);
                $table->string('ss_ev_tx_status', 30)->default('ativo');

                $table->index(['ss_ev_nb_epi_id']);
            });
        }

        // 9. Campos de fardamento no cadastro de colaboradores (users)
        if (Schema::hasTable('users')) {
            foreach (['camisa_tamanho', 'calca_tamanho', 'bota_numero'] as $coluna) {
                if (!Schema::hasColumn('users', $coluna)) {
                    Schema::table('users', function (Blueprint $table) use ($coluna) {
                        $table->string($coluna, 20)->nullable()->after('cargo');
                    });
                }
            }
        }

        // Adicionar coluna de variação nas tabelas existentes (se não existir)
        if (Schema::hasTable('ss_epi_estoque') && !Schema::hasColumn('ss_epi_estoque', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_estoque', function (Blueprint $table) {
                $table->integer('ss_e_nb_variacao_id')->nullable()->after('ss_e_nb_empresa_id');
            });
        }

        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_nb_variacao_id')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->integer('ss_e_nb_variacao_id')->nullable()->after('ss_e_nb_epi_id');
            });
        }

        // Colunas de workflow de assinatura
        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_requer_assinatura')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->boolean('ss_e_tx_requer_assinatura')->default(true)->after('ss_e_tx_status');
                $table->string('ss_e_tx_status_assinatura', 30)->default('pendente')->after('ss_e_tx_requer_assinatura');
                $table->text('ss_e_tx_justificativa_negacao')->nullable()->after('ss_e_tx_status_assinatura');
                $table->dateTime('ss_e_tx_data_assinatura')->nullable()->after('ss_e_tx_justificativa_negacao');
                $table->string('ss_e_tx_grupo_assinatura', 36)->nullable()->after('ss_e_tx_data_assinatura');
            });
        }
        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_grupo_assinatura')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->string('ss_e_tx_grupo_assinatura', 36)->nullable()->after('ss_e_tx_data_assinatura');
            });
        }

        // Flag de entrega retroativa (atualização de ficha sem baixa de estoque)
        if (Schema::hasTable('ss_epi_entrega') && !Schema::hasColumn('ss_epi_entrega', 'ss_e_tx_retroativo')) {
            Schema::table('ss_epi_entrega', function (Blueprint $table) {
                $table->boolean('ss_e_tx_retroativo')->default(false)->after('ss_e_tx_grupo_assinatura');
            });
        }

        // Trilha de devoluções / encerramento de EPIs (auditoria)
        if (!Schema::hasTable('ss_epi_devolucao')) {
            Schema::create('ss_epi_devolucao', function (Blueprint $table) {
                $table->id('ss_ed_nb_id');
                $table->unsignedBigInteger('ss_ed_nb_entrega_id')->nullable();
                $table->unsignedBigInteger('ss_ed_nb_epi_id');
                $table->unsignedBigInteger('ss_ed_nb_colaborador_id')->nullable();
                $table->unsignedBigInteger('ss_ed_nb_empresa_id')->nullable();
                $table->unsignedBigInteger('ss_ed_nb_variacao_id')->nullable();
                $table->unsignedInteger('ss_ed_nb_quantidade')->default(1);
                $table->string('ss_ed_tx_motivo', 50);
                $table->string('ss_ed_tx_destino', 20)->default('descarte');
                $table->string('ss_ed_tx_status', 20)->default('concluida');
                $table->string('ss_ed_tx_resultado_inspecao', 20)->nullable();
                $table->text('ss_ed_tx_observacao')->nullable();
                $table->unsignedBigInteger('ss_ed_nb_userRegistro')->nullable();
                $table->dateTime('ss_ed_tx_data_registro')->nullable();
                $table->unsignedBigInteger('ss_ed_nb_userDecisao')->nullable();
                $table->dateTime('ss_ed_tx_data_decisao')->nullable();
                $table->index(['ss_ed_nb_entrega_id']);
                $table->index(['ss_ed_nb_epi_id']);
                $table->index(['ss_ed_tx_status']);
            });
        }

        // [DESATIVADO] Popular EPIs universais se ss_epi estiver vazia
        // if (DB::table('ss_epi')->count() === 0) { ... }

        // Sincronização incremental de colaboradores a partir dos usuários:
        // cria novos funcionários que ainda não existem em ss_colaborador (por CPF)
        // e atualiza nome/cargo/status dos já existentes. Sempre que o módulo abrir.
        if (Schema::hasTable('users') && Schema::hasTable('ss_colaborador')) {
            $users = DB::table('users')->get(['id', 'nome', 'cpf', 'cargo', 'status']);

            foreach ($users as $u) {
                $dados = [
                    'ss_c_tx_nome' => $u->nome,
                    'ss_c_tx_cargo' => $u->cargo ?: 'Funcionário',
                    'ss_c_tx_status' => $u->status ?: 'ativo',
                ];

                $existente = DB::table('ss_colaborador')
                    ->where('ss_c_tx_cpf', $u->cpf)
                    ->first();

                if ($existente) {
                    DB::table('ss_colaborador')
                        ->where('ss_c_nb_id', $existente->ss_c_nb_id)
                        ->update($dados);
                } else {
                    DB::table('ss_colaborador')->insert(array_merge($dados, [
                        'ss_c_tx_cpf' => $u->cpf,
                        'ss_c_tx_matricula' => 'MAT-' . str_pad($u->id, 5, '0', STR_PAD_LEFT),
                        'ss_c_nb_empresa_id' => 0,
                    ]));
                }
            }
        }
    }

    /**
     * Dashboard principal do módulo de Saúde e Segurança (EPIs).
     */
    public function index(Request $request)
    {
        $this->ensureTablesExist();

        $filialSelecionada = $request->input('filial_id', 0); // 0 = Matriz
        $buscaCatalogo = $request->input('busca_catalogo');
        $grupoCatalogo = $request->input('grupo_catalogo');
        $statusCatalogo = $request->input('status_catalogo');

        // 1. Estatísticas Rápidas
        $totalCatalogo = Epi::where('ss_e_tx_status', 'ativo')->count();
        
        $totalEntradasEstoque = DB::table('ss_epi_estoque')->whereIn('ss_e_tx_tipo', ['entrada', 'devolucao'])->sum('ss_e_nb_quantidade');
        $totalSaidasEstoque = DB::table('ss_epi_estoque')->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');
        $saldoEstoqueTotal = max(0, $totalEntradasEstoque - $totalSaidasEstoque);

        $totalEntregasAtivas = EpiEntrega::whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])->count();
        $totalColaboradoresElegiveis = EpiColaborador::elegiveisParaEntrega()->count();

        // 1.1 Monitoramento de validade: CAs (vencido / vencendo / sem data)
        $caStats = Epi::contarCaCriticos(30);

        $ordemCa = ['vencido' => 0, 'expirando_30' => 1, 'expirando_60' => 2, 'expirando_90' => 3, 'sem_data' => 4];
        $episCaProblema = Epi::with('variacoes')
            ->where('ss_e_tx_status', 'ativo')
            ->get()
            ->filter(function ($epi) {
                return in_array($epi->status_ca, ['vencido', 'expirando_30', 'expirando_60', 'expirando_90', 'sem_data']);
            })
            ->sortBy(function ($epi) use ($ordemCa) {
                return $ordemCa[$epi->status_ca];
            })
            ->values();

        // 1.2 Monitoramento de validade: vida útil em dias não configurada
        $episVidaUtilProblema = Epi::where('ss_e_tx_status', 'ativo')
            ->where('ss_e_nb_vida_util_dias', '<=', 0)
            ->orderBy('ss_e_tx_grupo')
            ->orderBy('ss_e_tx_item')
            ->get();

        $vidaUtilStats = [
            'total_ativo'     => (int) $totalCatalogo,
            'nao_configurada' => $episVidaUtilProblema->count(),
            'com_valor'       => max(0, (int) $totalCatalogo - $episVidaUtilProblema->count()),
        ];

        // 1.3 Monitoramento de validade: entregas ativas vencidas ou vencendo em 30 dias
        $hojeStr = now()->format('Y-m-d');
        $vencimentoLimite = now()->addDays(30)->format('Y-m-d');

        $entregasVencidasCount = EpiEntrega::whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->whereNotNull('ss_e_tx_vencimento')
            ->where('ss_e_tx_vencimento', '<', $hojeStr)
            ->count();

        $entregasVencendoCount = EpiEntrega::whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->whereNotNull('ss_e_tx_vencimento')
            ->where('ss_e_tx_vencimento', '>=', $hojeStr)
            ->where('ss_e_tx_vencimento', '<=', $vencimentoLimite)
            ->count();

        $entregasAlerta = EpiEntrega::with(['colaborador', 'epi'])
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->whereNotNull('ss_e_tx_vencimento')
            ->where('ss_e_tx_vencimento', '<=', $vencimentoLimite)
            ->orderBy('ss_e_tx_vencimento')
            ->limit(100)
            ->get();

        // 1.4 Devoluções: pendências de inspeção e histórico auditável
        $devolucoesPendentes = EpiDevolucao::with(['colaborador', 'epi', 'variacao'])
            ->where('ss_ed_tx_status', 'pendente')
            ->orderBy('ss_ed_tx_data_registro', 'asc')
            ->get();

        $devolucoesPendentesCount = $devolucoesPendentes->count();

        $devolucoesHistorico = EpiDevolucao::with(['colaborador', 'epi', 'variacao', 'usuarioRegistro', 'usuarioDecisao'])
            ->orderBy('ss_ed_tx_data_registro', 'desc')
            ->limit(150)
            ->get();

        // 2. Consulta do Catálogo de EPIs
        $queryCatalogo = Epi::query();
        if (!empty($buscaCatalogo)) {
            $queryCatalogo->where(function ($q) use ($buscaCatalogo) {
                $q->where('ss_e_tx_item', 'LIKE', "%{$buscaCatalogo}%")
                  ->orWhere('ss_e_tx_grupo', 'LIKE', "%{$buscaCatalogo}%")
                  ->orWhere('ss_e_tx_subgrupo', 'LIKE', "%{$buscaCatalogo}%")
                  ->orWhere('ss_e_tx_ca', 'LIKE', "%{$buscaCatalogo}%")
                  ->orWhere('ss_e_tx_fabricante', 'LIKE', "%{$buscaCatalogo}%");
            });
        }
        if (!empty($grupoCatalogo)) {
            $queryCatalogo->where('ss_e_tx_grupo', $grupoCatalogo);
        }
        if (!empty($statusCatalogo)) {
            $queryCatalogo->where('ss_e_tx_status', $statusCatalogo);
        }
        $episCatalogo = $queryCatalogo->with('variacoes')->orderBy('ss_e_tx_grupo')->orderBy('ss_e_tx_item')->get();

        // Grupos únicos para os filtros
        $gruposUnicos = Epi::distinct()->pluck('ss_e_tx_grupo')->filter()->values();

        // 2.1 Saldo por Variação (para exibir no catálogo e no estoque)
        $saldosVariacao = [];
        foreach ($episCatalogo as $epi) {
            foreach ($epi->variacoes->where('ss_ev_tx_status', 'ativo') as $v) {
                $saldosVariacao[$epi->ss_e_nb_id][$v->ss_ev_nb_id] = $epi->getSaldoTotalRede($v->ss_ev_nb_id);
            }
        }

        // 3. Colaboradores Elegíveis (Regra 4: sem diretores)
        $colaboradores = EpiColaborador::elegiveisParaEntrega()->orderBy('ss_c_tx_nome')->get();

        // 4. Kits Cadastrados
        $kits = EpiKit::with('itens.epi')->where('ss_k_tx_status', 'ativo')->get();

        // 5. Movimentações de Estoque Recentes
        $estoqueMovimentos = EpiEstoque::with(['epi', 'variacao'])
            ->orderBy('ss_e_tx_data', 'desc')
            ->limit(100)
            ->get();

        // 6. Entregas Recentes (Omitindo inativos e devolvidos conforme regra)
        // Ordenado pela data de LANÇAMENTO (cadastro), mais recentes no topo,
        // independentemente da data da entrega informada
        $entregasRecentes = EpiEntrega::with(['colaborador', 'epi'])
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->orderBy('ss_e_tx_dataCadastro', 'desc')
            ->orderBy('ss_e_nb_id', 'desc')
            ->limit(100)
            ->get();

        // Lista de Filiais dinâmicas (Matriz + Filiais cadastradas ativas)
        $filiais = $this->getFiliaisList();
        $filiaisCadastradas = DB::table('ss_filial')->orderBy('ss_f_nb_id', 'desc')->get();

        // 7. Fardamento: distribuição de tamanhos cadastrados nos colaboradores
        $fardamentoDados = $this->getFardamentoDistribuicao();
        $fardamentoFuncionarios = $fardamentoDados['funcionarios'] ?? [];

        // 8. Fardamento: saldo em banco via lançamentos do Controle de Estoque
        // Somente itens de fardamento: grupo/subgrupo de fardamento ou peças camisa/calça/bota
        $fardamentoEpis = Epi::where('ss_e_tx_status', 'ativo')
            ->where(function ($q) {
                $q->where(function ($r) {
                    $r->where('ss_e_tx_grupo', 'LIKE', '%fardamento%')
                        ->orWhere('ss_e_tx_grupo', 'LIKE', '%uniforme%')
                        ->orWhere('ss_e_tx_subgrupo', 'LIKE', '%fardamento%')
                        ->orWhere('ss_e_tx_subgrupo', 'LIKE', '%uniforme%');
                })
                ->orWhere(function ($r) {
                    $r->where('ss_e_tx_item', 'LIKE', '%camisa%')
                        ->orWhere('ss_e_tx_item', 'LIKE', '%calça%')
                        ->orWhere('ss_e_tx_item', 'LIKE', '%calca%')
                        ->orWhere('ss_e_tx_item', 'LIKE', '%bota%');
                });
            })
            ->with(['variacoes' => function ($q) {
                $q->where('ss_ev_tx_status', 'ativo');
            }])
            ->orderBy('ss_e_tx_grupo')
            ->orderBy('ss_e_tx_item')
            ->get();

        $fardamentoEstoqueLinhas = [];
        $fardamentoSaldoPorTipo = ['camisa' => 0, 'calca' => 0, 'bota' => 0];

        foreach ($fardamentoEpis as $epi) {
            $tipo = $this->detectarTipoFardamento($epi);
            $epiTemVariacoes = $epi->variacoes->isNotEmpty();

            if ($epiTemVariacoes) {
                foreach ($epi->variacoes as $v) {
                    $saldoLocal = $epi->getSaldoPorFilial($filialSelecionada, $v->ss_ev_nb_id);
                    $saldoRede = $epi->getSaldoTotalRede($v->ss_ev_nb_id);

                    if ($tipo !== null) {
                        $fardamentoSaldoPorTipo[$tipo] += $saldoRede;
                    }

                    $fardamentoEstoqueLinhas[] = [
                        'epi_id' => $epi->ss_e_nb_id,
                        'grupo' => $epi->ss_e_tx_grupo,
                        'item' => $epi->ss_e_tx_item,
                        'ca' => $epi->ss_e_tx_ca,
                        'variacao_id' => $v->ss_ev_nb_id,
                        'variacao_nome' => $v->ss_ev_tx_nome,
                        'saldo_local' => $saldoLocal,
                        'saldo_rede' => $saldoRede,
                        'disponibilidade' => $saldoLocal > 0 ? 'local' : ($saldoRede > 0 ? 'externo' : 'esgotado'),
                    ];
                }
            } else {
                $saldoLocal = $epi->getSaldoPorFilial($filialSelecionada);
                $saldoRede = $epi->getSaldoTotalRede();

                if ($tipo !== null) {
                    $fardamentoSaldoPorTipo[$tipo] += $saldoRede;
                }

                $fardamentoEstoqueLinhas[] = [
                    'epi_id' => $epi->ss_e_nb_id,
                    'grupo' => $epi->ss_e_tx_grupo,
                    'item' => $epi->ss_e_tx_item,
                    'ca' => $epi->ss_e_tx_ca,
                    'variacao_id' => null,
                    'variacao_nome' => null,
                    'saldo_local' => $saldoLocal,
                    'saldo_rede' => $saldoRede,
                    'disponibilidade' => $saldoLocal > 0 ? 'local' : ($saldoRede > 0 ? 'externo' : 'esgotado'),
                ];
            }
        }

        $fardamentoEstoqueTotal = (int) array_sum(array_column($fardamentoEstoqueLinhas, 'saldo_rede'));

        // Demanda cadastrada vs saldo em banco (apoio ao pedido de fardamento)
        $fardamentoDemanda = [
            'camisa' => $fardamentoDados['camisa']['total'] ?? 0,
            'calca' => $fardamentoDados['calca']['total'] ?? 0,
            'bota' => $fardamentoDados['bota']['total'] ?? 0,
        ];
        $fardamentoDeficit = [];
        foreach ($fardamentoDemanda as $tipo => $demanda) {
            $saldo = $fardamentoSaldoPorTipo[$tipo] ?? 0;
            $fardamentoDeficit[$tipo] = [
                'demanda' => $demanda,
                'saldo' => $saldo,
                'cobertura' => $demanda > 0 ? min(100, (int) round($saldo / $demanda * 100)) : 0,
                'deficit' => max(0, $demanda - $saldo),
            ];
        }

        return view('epi.index', compact(
            'totalCatalogo',
            'saldoEstoqueTotal',
            'totalEntregasAtivas',
            'totalColaboradoresElegiveis',
            'caStats',
            'episCaProblema',
            'episVidaUtilProblema',
            'vidaUtilStats',
            'entregasVencidasCount',
            'entregasVencendoCount',
            'entregasAlerta',
            'devolucoesPendentes',
            'devolucoesPendentesCount',
            'devolucoesHistorico',
            'episCatalogo',
            'gruposUnicos',
            'colaboradores',
            'kits',
            'estoqueMovimentos',
            'entregasRecentes',
            'filiais',
            'filialSelecionada',
            'filiaisCadastradas',
            'saldosVariacao',
            'fardamentoDados',
            'fardamentoFuncionarios',
            'fardamentoEstoqueLinhas',
            'fardamentoEstoqueTotal',
            'fardamentoDemanda',
            'fardamentoSaldoPorTipo',
            'fardamentoDeficit'
        ));
    }

    /**
     * Identifica a categoria de fardamento (camisa, calça ou bota) de um EPI
     * analisando grupo, subgrupo e nome do item no catálogo.
     */
    private function detectarTipoFardamento(Epi $epi): ?string
    {
        $texto = mb_strtolower(trim(($epi->ss_e_tx_grupo ?? '') . ' ' . ($epi->ss_e_tx_subgrupo ?? '') . ' ' . ($epi->ss_e_tx_item ?? '')));

        if (str_contains($texto, 'camisa')) {
            return 'camisa';
        }

        if (str_contains($texto, 'calça') || str_contains($texto, 'calca')) {
            return 'calca';
        }

        if (str_contains($texto, 'bota')) {
            return 'bota';
        }

        return null;
    }

    /**
     * Monta a distribuição de tamanhos de fardamento (camisa, calça e bota)
     * a partir dos colaboradores ativos cadastrados no sistema.
     */
    private function getFardamentoDistribuicao(): array
    {
        $funcionarios = User::where('status', 'ativo')
            ->where('usuario_teste', false)
            ->where(function ($q) {
                $q->whereNull('role_id')
                    ->orWhereHas('role', function ($r) {
                        $r->where('nome', '<>', 'super_admin');
                    });
            })
            ->get(['id', 'nome', 'cargo', 'setor', 'empresa', 'camisa_tamanho', 'calca_tamanho', 'bota_numero']);

        $categorias = [
            'camisa' => 'camisa_tamanho',
            'calca' => 'calca_tamanho',
            'bota' => 'bota_numero',
        ];

        $dados = [];
        foreach ($categorias as $chave => $coluna) {
            $grupos = [];
            $totalComMedida = 0;

            foreach ($funcionarios as $f) {
                $tamanho = trim((string) ($f->{$coluna} ?? ''));
                if ($tamanho === '') {
                    continue;
                }
                $totalComMedida++;
                if (!isset($grupos[$tamanho])) {
                    $grupos[$tamanho] = ['qtd' => 0, 'funcionarios' => []];
                }
                $grupos[$tamanho]['qtd']++;
                $grupos[$tamanho]['funcionarios'][] = [
                    'nome' => $f->nome,
                    'cargo' => $f->cargo,
                    'setor' => $f->setor,
                    'empresa' => $f->empresa,
                ];
            }

            // Ordenação numérica sensível (36, 38, 40... e PP, P, M, G...)
            uksort($grupos, function ($a, $b) {
                $numericA = preg_replace('/\D/', '', (string) $a);
                $numericB = preg_replace('/\D/', '', (string) $b);
                if ($numericA !== '' && $numericB !== '' && $numericA !== $numericB) {
                    return (int) $numericA <=> (int) $numericB;
                }
                return strnatcmp((string) $a, (string) $b);
            });

            $dados[$chave] = [
                'total' => $totalComMedida,
                'grupos' => $grupos,
            ];
        }

        // Lista plana de todos os funcionários com os 3 tamanhos (modal de consulta)
        $dados['funcionarios'] = $funcionarios->map(function ($f) {
            return [
                'id' => $f->id,
                'nome' => $f->nome,
                'cargo' => $f->cargo,
                'setor' => $f->setor,
                'empresa' => $f->empresa,
                'camisa' => $f->camisa_tamanho,
                'calca' => $f->calca_tamanho,
                'bota' => $f->bota_numero,
            ];
        })->values()->all();

        return $dados;
    }

    /**
     * Obter lista de Filiais (Matriz + Cadastradas Ativas).
     */
    public function getFiliaisList(): array
    {
        $this->ensureTablesExist();

        $filiais = [
            0 => 'Matriz / Sede Principal'
        ];

        if (Schema::hasTable('ss_filial')) {
            $registros = DB::table('ss_filial')
                ->where('ss_f_tx_status', 'ativo')
                ->orderBy('ss_f_tx_nome')
                ->get();

            foreach ($registros as $f) {
                $filiais[$f->ss_f_nb_id] = $f->ss_f_tx_nome . ($f->ss_f_tx_cidade ? " ({$f->ss_f_tx_cidade})" : "");
            }
        }

        return $filiais;
    }

    /**
     * API: Retorna JSON com os saldos de estoque de todos os EPIs por filial.
     */
    public function getEstoqueDisponivel(Request $request)
    {
        $this->ensureTablesExist();

        $filialAtual = $request->input('filial_id', 0);
        $epis = Epi::where('ss_e_tx_status', 'ativo')->get();
        $filiais = $this->getFiliaisList();

        $resultado = [];

        foreach ($epis as $epi) {
            $variacoes = $epi->variacoes()->where('ss_ev_tx_status', 'ativo')->get();

            $variacoesData = [];
            foreach ($variacoes as $v) {
                $saldoVarLocal = $epi->getSaldoPorFilial($filialAtual, $v->ss_ev_nb_id);
                $saldoVarRede = $epi->getSaldoTotalRede($v->ss_ev_nb_id);
                $variacoesData[] = [
                    'id' => $v->ss_ev_nb_id,
                    'nome' => $v->ss_ev_tx_nome,
                    'saldo_atual' => $saldoVarLocal,
                    'saldo_rede' => $saldoVarRede,
                ];
            }

            $saldoAtual = $epi->getSaldoPorFilial($filialAtual);
            $saldoRede = $epi->getSaldoTotalRede();

            $saldosOutrasFiliais = [];
            foreach ($filiais as $fId => $fNome) {
                if ((int)$fId !== (int)$filialAtual) {
                    $s = $epi->getSaldoPorFilial($fId);
                    if ($s > 0) {
                        $saldosOutrasFiliais[] = [
                            'filial_id' => $fId,
                            'filial_nome' => $fNome,
                            'saldo' => $s,
                        ];
                    }
                }
            }

            // Define status visual conforme Regra de Negócio 1
            $disponibilidade = 'local';
            if ($saldoAtual <= 0 && $saldoRede > 0) {
                $disponibilidade = 'externo';
            } elseif ($saldoRede <= 0) {
                $disponibilidade = 'esgotado';
            }

            $resultado[] = [
                'id' => $epi->ss_e_nb_id,
                'grupo' => $epi->ss_e_tx_grupo,
                'subgrupo' => $epi->ss_e_tx_subgrupo,
                'item' => $epi->ss_e_tx_item,
                'ca' => $epi->ss_e_tx_ca,
                'vida_util_dias' => (int) $epi->ss_e_nb_vida_util_dias,
                'saldo_atual' => $saldoAtual,
                'saldo_rede' => $saldoRede,
                'disponibilidade' => $disponibilidade,
                'outras_filiais' => $saldosOutrasFiliais,
                'variacoes' => $variacoesData,
            ];
        }

        return response()->json([
            'status' => 'success',
            'filial_id' => $filialAtual,
            'data' => $resultado,
        ]);
    }

    /**
     * API: Retorna as variações ativas de um EPI.
     */
    public function getVariacoes($epiId)
    {
        $this->ensureTablesExist();

        $variacoes = EpiVariacao::where('ss_ev_nb_epi_id', $epiId)
            ->where('ss_ev_tx_status', 'ativo')
            ->get(['ss_ev_nb_id', 'ss_ev_tx_nome']);

        return response()->json([
            'status' => 'success',
            'data' => $variacoes,
        ]);
    }

    /**
     * Cadastro / Atualização de EPI no Catálogo.
     */
    public function catalogoStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_e_tx_grupo' => 'required|string|max:255',
            'ss_e_tx_item' => 'required|string|max:255',
            'ss_e_nb_vida_util_dias' => 'nullable|integer|min:0',
        ]);

        $id = $request->input('ss_e_nb_id');
        $dados = [
            'ss_e_tx_grupo' => $request->input('ss_e_tx_grupo'),
            'ss_e_tx_subgrupo' => $request->input('ss_e_tx_subgrupo'),
            'ss_e_tx_item' => $request->input('ss_e_tx_item'),
            'ss_e_tx_descricao' => $request->input('ss_e_tx_descricao'),
            'ss_e_tx_fabricante' => $request->input('ss_e_tx_fabricante'),
            'ss_e_tx_ca' => $request->input('ss_e_tx_ca'),
            'ss_e_tx_validade_ca' => $request->input('ss_e_tx_validade_ca'),
            'ss_e_nb_vida_util_dias' => (int)$request->input('ss_e_nb_vida_util_dias', 0),
            'ss_e_tx_status' => $request->input('ss_e_tx_status', 'ativo'),
            'ss_e_tx_modelo' => $request->input('ss_e_tx_modelo'),
        ];

        // Upload de foto se fornecida
        if ($request->hasFile('ss_e_tx_foto')) {
            $path = $request->file('ss_e_tx_foto')->store('epis_fotos', 'public');
            $dados['ss_e_tx_foto'] = '/storage/' . $path;
        }

        if (!empty($id)) {
            Epi::where('ss_e_nb_id', $id)->update($dados);
            $msg = 'EPI atualizado com sucesso!';
        } else {
            $dados['ss_e_tx_cadastro_tipo'] = 'estoque';
            $dados['ss_e_nb_userCadastro'] = Auth::id();
            $dados['ss_e_tx_dataCadastro'] = now();
            $epi = Epi::create($dados);
            $id = $epi->ss_e_nb_id;
            $msg = 'EPI cadastrado com sucesso!';
        }

        // Gerenciar variações
        if ($request->has('variacoes') && is_array($request->input('variacoes'))) {
            $nomesVariacoes = $request->input('variacoes');
            $idsManter = [];
            foreach ($nomesVariacoes as $nome) {
                $nome = trim($nome);
                if (empty($nome)) continue;
                $existente = EpiVariacao::where('ss_ev_nb_epi_id', $id)
                    ->where('ss_ev_tx_nome', $nome)
                    ->first();
                if ($existente) {
                    $idsManter[] = $existente->ss_ev_nb_id;
                } else {
                    $var = EpiVariacao::create([
                        'ss_ev_nb_epi_id' => $id,
                        'ss_ev_tx_nome' => $nome,
                        'ss_ev_tx_status' => 'ativo',
                    ]);
                    $idsManter[] = $var->ss_ev_nb_id;
                }
            }
            // Remover variações que não estão mais na lista
            EpiVariacao::where('ss_ev_nb_epi_id', $id)
                ->whereNotIn('ss_ev_nb_id', $idsManter)
                ->delete();
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Alternar Status do EPI.
     */
    public function catalogoToggleStatus($id)
    {
        $this->ensureTablesExist();

        $epi = Epi::findOrFail($id);
        $novoStatus = ($epi->ss_e_tx_status === 'ativo') ? 'inativo' : 'ativo';
        $epi->update(['ss_e_tx_status' => $novoStatus]);

        return redirect()->back()->with('success', "Status do EPI alterado para {$novoStatus}!");
    }

    /**
     * Lançamento de Movimentação de Estoque (Entrada / Compra).
     */
    public function estoqueStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_e_nb_epi_id' => 'required|integer',
            'ss_e_tx_tipo' => 'required|in:entrada,saida,substituicao',
        ]);

        $epiId = $request->input('ss_e_nb_epi_id');
        $tipo = $request->input('ss_e_tx_tipo');
        $empresaId = $request->input('ss_e_nb_empresa_id', 0);
        $valorUnitario = $request->filled('ss_e_db_valor_unitario') ? (float)$request->input('ss_e_db_valor_unitario') : null;
        $chaveNf = $request->input('ss_e_tx_chave_nf');
        $fornecedor = $request->input('ss_e_tx_fornecedor');
        $motivo = $request->input('ss_e_tx_motivo');
        $dataRecebimento = $request->input('ss_e_tx_data_recebimento');
        $validade = $request->input('ss_e_tx_validade');

        $fotoCaminho = null;
        if ($request->hasFile('ss_e_tx_foto')) {
            $path = $request->file('ss_e_tx_foto')->store('estoque_comprovantes', 'public');
            $fotoCaminho = '/storage/' . $path;
        }

        $variacoesData = $request->input('variacoes');
        $totalGeral = 0;

        // Modo 1: Lançamento com múltiplas variações
        if (!empty($variacoesData) && is_array($variacoesData)) {
            $temQtd = false;
            $entries = [];
            foreach ($variacoesData as $varId => $varData) {
                $qtd = isset($varData['qtd']) ? (int)$varData['qtd'] : 0;
                if ($qtd <= 0) continue;
                $temQtd = true;
                $entries[] = [
                    'variacao_id' => (int)$varId,
                    'quantidade' => $qtd,
                ];
            }

            if (!$temQtd) {
                return redirect()->back()->with('error', 'Informe a quantidade para pelo menos uma variação!');
            }

            DB::transaction(function () use ($entries, $epiId, $empresaId, $tipo, $valorUnitario, $chaveNf, $fornecedor, $motivo, $dataRecebimento, $validade, $fotoCaminho, &$totalGeral) {
                foreach ($entries as $entry) {
                    $qtd = $entry['quantidade'];
                    $valorTotal = $valorUnitario !== null ? ($valorUnitario * $qtd) : null;
                    $totalGeral += $qtd;

                    EpiEstoque::create([
                        'ss_e_nb_epi_id' => $epiId,
                        'ss_e_nb_empresa_id' => $empresaId,
                        'ss_e_nb_variacao_id' => $entry['variacao_id'],
                        'ss_e_nb_quantidade' => $qtd,
                        'ss_e_tx_tipo' => $tipo,
                        'ss_e_db_valor_unitario' => $valorUnitario,
                        'ss_e_db_valor_total' => $valorTotal,
                        'ss_e_tx_data_recebimento' => $dataRecebimento,
                        'ss_e_tx_validade' => $validade,
                        'ss_e_tx_chave_nf' => $chaveNf,
                        'ss_e_tx_fornecedor' => $fornecedor,
                        'ss_e_tx_data' => now(),
                        'ss_e_tx_motivo' => $motivo,
                        'ss_e_tx_foto' => $fotoCaminho,
                        'ss_e_nb_userCadastro' => Auth::id(),
                    ]);
                }
            });

            $msg = "Movimentação de estoque registrada com sucesso! Total de {$totalGeral} itens em " . count($entries) . " variação(ões).";
            return redirect()->back()->with('success', $msg);
        }

        // Modo 2: Lançamento único (sem variações ou variação única)
        $request->validate([
            'ss_e_nb_quantidade' => 'required|integer|min:1',
        ]);

        $quantidade = (int)$request->input('ss_e_nb_quantidade');
        $valorTotal = $valorUnitario !== null ? ($valorUnitario * $quantidade) : null;

        $dados = [
            'ss_e_nb_epi_id' => $epiId,
            'ss_e_nb_empresa_id' => $empresaId,
            'ss_e_nb_variacao_id' => $request->input('ss_e_nb_variacao_id'),
            'ss_e_nb_quantidade' => $quantidade,
            'ss_e_tx_tipo' => $tipo,
            'ss_e_db_valor_unitario' => $valorUnitario,
            'ss_e_db_valor_total' => $valorTotal,
            'ss_e_tx_data_recebimento' => $dataRecebimento,
            'ss_e_tx_validade' => $validade,
            'ss_e_tx_chave_nf' => $chaveNf,
            'ss_e_tx_fornecedor' => $fornecedor,
            'ss_e_tx_data' => now(),
            'ss_e_tx_motivo' => $motivo,
            'ss_e_tx_foto' => $fotoCaminho,
            'ss_e_nb_userCadastro' => Auth::id(),
        ];

        EpiEstoque::create($dados);

        return redirect()->back()->with('success', 'Movimentação de estoque registrada com sucesso!');
    }

    /**
     * Cadastro de Kits de EPI.
     */
    public function kitStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_k_tx_nome' => 'required|string|max:255',
            'itens' => 'required|array|min:1',
            'itens.*.epi_id' => 'required|integer',
            'itens.*.quantidade' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $kit = EpiKit::create([
                'ss_k_tx_nome' => $request->input('ss_k_tx_nome'),
                'ss_k_tx_status' => 'ativo',
            ]);

            foreach ($request->input('itens') as $item) {
                EpiKitItem::create([
                    'ss_ki_nb_kit_id' => $kit->ss_k_nb_id,
                    'ss_ki_nb_epi_id' => $item['epi_id'],
                    'ss_ki_nb_quantidade' => (int)$item['quantidade'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Kit de EPI cadastrado com sucesso!');
    }

    /**
     * Excluir Kit de EPI.
     */
    public function kitDestroy($id)
    {
        $this->ensureTablesExist();

        DB::transaction(function () use ($id) {
            EpiKitItem::where('ss_ki_nb_kit_id', $id)->delete();
            EpiKit::where('ss_k_nb_id', $id)->delete();
        });

        return redirect()->back()->with('success', 'Kit excluído com sucesso!');
    }

    /**
     * Processamento de Entrega em Lote (Sacola de Entregas - Motorista Único ou Multi-Motorista).
     */
    public function entregaStore(Request $request)
    {
        $this->ensureTablesExist();

        // Entrega retroativa: apenas atualiza a ficha do colaborador, sem validar
        // saldo em estoque e sem gerar movimentação de saída/baixa.
        $retroativo = $request->boolean('retroativo');

        // 1. Processamento Multi-Motorista (Lote Completo)
        if ($request->has('entregas') && is_array($request->input('entregas'))) {
            $entregasLote = $request->input('entregas');
            $totalProcessados = 0;

            // Validação estrita de saldo de estoque para cada item do lote
            // (ignorada quando a entrega é retroativa)
            if (!$retroativo) {
                foreach ($entregasLote as $entData) {
                    $empId = $entData['ss_e_nb_empresa_id'] ?? $request->input('ss_e_nb_empresa_id', 0);
                    foreach ($entData['itens'] ?? [] as $itemData) {
                        $epi = Epi::find($itemData['epi_id']);
                        if (!$epi) continue;
                        $qtd = (int)$itemData['quantidade'];
                        $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empId;
                        $variacaoId = isset($itemData['variacao_id']) ? (int)$itemData['variacao_id'] : null;
                        $saldoLocal = $epi->getSaldoPorFilial($filialOrigem, $variacaoId);

                        $infoVariacao = '';
                        if ($variacaoId) {
                            $var = EpiVariacao::find($variacaoId);
                            $infoVariacao = $var ? " ({$var->ss_ev_tx_nome})" : '';
                        }

                        if ($saldoLocal < $qtd) {
                            return response()->json([
                                'status' => 'error',
                                'message' => "O item '{$epi->ss_e_tx_item}{$infoVariacao}' não possui saldo suficiente em estoque para concluir a entrega! (Necessário: {$qtd}, Saldo na filial: {$saldoLocal})"
                            ], 422);
                        }
                    }
                }
            }

            DB::transaction(function () use ($entregasLote, $request, $retroativo, &$totalProcessados) {
                $grupoAssinatura = (string) \Illuminate\Support\Str::uuid();
                foreach ($entregasLote as $entData) {
                    $colabId = $entData['ss_e_nb_colaborador_id'] ?? null;
                    $dtEntrega = $entData['ss_e_tx_data_entrega'] ?? date('Y-m-d');
                    $empId = $entData['ss_e_nb_empresa_id'] ?? $request->input('ss_e_nb_empresa_id', 0);
                    $sig = $entData['ss_e_tx_assinatura'] ?? null;
                    $obs = $entData['ss_e_tx_observacao'] ?? null;
                    $itensList = $entData['itens'] ?? [];

                    if (!$colabId || empty($itensList)) continue;

                    foreach ($itensList as $itemData) {
                        $epi = Epi::find($itemData['epi_id']);
                        if (!$epi) continue;

                        $qtd = (int)$itemData['quantidade'];
                        $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empId;
                        $variacaoId = isset($itemData['variacao_id']) ? (int)$itemData['variacao_id'] : null;

                        $diasVidaUtil = (int)$epi->ss_e_nb_vida_util_dias;
                        $vencimento = null;
                        if ($diasVidaUtil > 0) {
                            $vencimento = date('Y-m-d', strtotime("{$dtEntrega} + {$diasVidaUtil} days"));
                        }

                        $requerAssinatura = $sig ? false : true;

                        EpiEntrega::create([
                            'ss_e_nb_colaborador_id' => $colabId,
                            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                            'ss_e_nb_variacao_id' => $variacaoId,
                            'ss_e_nb_empresa_id' => $empId,
                            'ss_e_tx_data_entrega' => $dtEntrega,
                            'ss_e_nb_quantidade' => $qtd,
                            'ss_e_tx_vencimento' => $vencimento,
                            'ss_e_tx_status' => 'ativo',
                            'ss_e_tx_assinatura' => $sig,
                            'ss_e_tx_observacao' => $obs,
                            'ss_e_nb_userCadastro' => Auth::id(),
                            'ss_e_tx_dataCadastro' => now(),
                            'ss_e_tx_requer_assinatura' => $requerAssinatura,
                            'ss_e_tx_status_assinatura' => $sig ? 'assinada' : 'pendente',
                            'ss_e_tx_data_assinatura' => $sig ? now() : null,
                            'ss_e_tx_grupo_assinatura' => $requerAssinatura ? $grupoAssinatura : null,
                            'ss_e_tx_retroativo' => $retroativo,
                        ]);

                        // Entrega retroativa não gera baixa/saída de estoque
                        if (!$retroativo) {
                            EpiEstoque::create([
                                'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                                'ss_e_nb_variacao_id' => $variacaoId,
                                'ss_e_nb_empresa_id' => $filialOrigem,
                                'ss_e_nb_quantidade' => $qtd,
                                'ss_e_tx_tipo' => 'saida',
                                'ss_e_tx_data' => now(),
                                'ss_e_tx_motivo' => "Entrega em lote para Colaborador ID #{$colabId}" . ($filialOrigem != $empId ? " (Transferido da Filial #{$filialOrigem})" : ""),
                                'ss_e_nb_userCadastro' => Auth::id(),
                            ]);
                        }

                        $totalProcessados++;
                    }
                }
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Todas as entregas foram registradas com sucesso! Total de itens entregues: {$totalProcessados}",
                ]);
            }

            return redirect()->back()->with('success', 'Entregas em lote registradas com sucesso!');
        }

        // 2. Processamento Individual de Colaborador/Motorista
        $request->validate([
            'ss_e_nb_colaborador_id' => 'required|integer',
            'ss_e_tx_data_entrega' => 'required|date',
            'itens' => 'required|array|min:1',
            'itens.*.epi_id' => 'required|integer',
            'itens.*.quantidade' => 'required|integer|min:1',
        ]);

        $colaboradorId = $request->input('ss_e_nb_colaborador_id');
        $dataEntrega = $request->input('ss_e_tx_data_entrega');
        $empresaId = $request->input('ss_e_nb_empresa_id', 0);
        $assinatura = $request->input('ss_e_tx_assinatura');
        $observacao = $request->input('ss_e_tx_observacao');

        // Validação estrita de saldo de estoque individual
        // (ignorada quando a entrega é retroativa)
        if (!$retroativo) {
            foreach ($request->input('itens') as $itemData) {
                $epi = Epi::find($itemData['epi_id']);
                if (!$epi) continue;
                $qtd = (int)$itemData['quantidade'];
                $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empresaId;
                $variacaoId = isset($itemData['variacao_id']) ? (int)$itemData['variacao_id'] : null;
                $saldoLocal = $epi->getSaldoPorFilial($filialOrigem, $variacaoId);

                $infoVariacao = '';
                if ($variacaoId) {
                    $var = EpiVariacao::find($variacaoId);
                    $infoVariacao = $var ? " ({$var->ss_ev_tx_nome})" : '';
                }

                if ($saldoLocal < $qtd) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "O item '{$epi->ss_e_tx_item}{$infoVariacao}' não possui saldo suficiente em estoque para concluir a entrega! (Necessário: {$qtd}, Saldo na filial: {$saldoLocal})"
                    ], 422);
                }
            }
        }

        $fotoCaminho = null;
        if ($request->hasFile('ss_e_tx_foto')) {
            $path = $request->file('ss_e_tx_foto')->store('recibos_entregas', 'public');
            $fotoCaminho = '/storage/' . $path;
        }

        DB::transaction(function () use ($colaboradorId, $dataEntrega, $empresaId, $assinatura, $fotoCaminho, $observacao, $retroativo, $request) {
            $grupoAssinatura = (string) \Illuminate\Support\Str::uuid();
            foreach ($request->input('itens') as $itemData) {
                $epi = Epi::findOrFail($itemData['epi_id']);
                $qtd = (int)$itemData['quantidade'];
                $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empresaId;
                $variacaoId = isset($itemData['variacao_id']) ? (int)$itemData['variacao_id'] : null;

                $diasVidaUtil = (int)$epi->ss_e_nb_vida_util_dias;
                $vencimento = null;
                if ($diasVidaUtil > 0) {
                    $vencimento = date('Y-m-d', strtotime("{$dataEntrega} + {$diasVidaUtil} days"));
                }

                $reqAss = $assinatura ? false : true;

                EpiEntrega::create([
                    'ss_e_nb_colaborador_id' => $colaboradorId,
                    'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                    'ss_e_nb_variacao_id' => $variacaoId,
                    'ss_e_nb_empresa_id' => $empresaId,
                    'ss_e_tx_data_entrega' => $dataEntrega,
                    'ss_e_nb_quantidade' => $qtd,
                    'ss_e_tx_vencimento' => $vencimento,
                    'ss_e_tx_status' => 'ativo',
                    'ss_e_tx_assinatura' => $assinatura,
                    'ss_e_tx_foto' => $fotoCaminho,
                    'ss_e_tx_observacao' => $observacao,
                    'ss_e_nb_userCadastro' => Auth::id(),
                    'ss_e_tx_dataCadastro' => now(),
                    'ss_e_tx_requer_assinatura' => $reqAss,
                    'ss_e_tx_status_assinatura' => $assinatura ? 'assinada' : 'pendente',
                    'ss_e_tx_data_assinatura' => $assinatura ? now() : null,
                    'ss_e_tx_grupo_assinatura' => $reqAss ? $grupoAssinatura : null,
                    'ss_e_tx_retroativo' => $retroativo,
                ]);

                // Entrega retroativa não gera baixa/saída de estoque
                if (!$retroativo) {
                    EpiEstoque::create([
                        'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                        'ss_e_nb_variacao_id' => $variacaoId,
                        'ss_e_nb_empresa_id' => $filialOrigem,
                        'ss_e_nb_quantidade' => $qtd,
                        'ss_e_tx_tipo' => 'saida',
                        'ss_e_tx_data' => now(),
                        'ss_e_tx_motivo' => "Entrega para Colaborador ID #{$colaboradorId}" . ($filialOrigem != $empresaId ? " (Transferido da Filial #{$filialOrigem})" : ""),
                        'ss_e_nb_userCadastro' => Auth::id(),
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Entrega(s) registrada(s) com sucesso!',
            ]);
        }

        return redirect()->back()->with('success', 'Entrega(s) de EPI realizada(s) com sucesso!');
    }

    /**
     * Inativação / Cancelamento de Entrega com Justificativa e Estorno Opcional (Regra 6).
     */
    public function entregaCancelar(Request $request, $id)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_e_tx_justificativa_exclusao' => 'required|string|max:255',
        ]);

        $entrega = EpiEntrega::findOrFail($id);
        $justificativa = $request->input('ss_e_tx_justificativa_exclusao');
        $estornarEstoque = $request->boolean('estornar_estoque');

        DB::transaction(function () use ($entrega, $justificativa, $estornarEstoque) {
            // 1. Atualizar entrega para 'inativo' e salvar justificativa
            $entrega->update([
                'ss_e_tx_status' => 'inativo',
                'ss_e_tx_justificativa_exclusao' => $justificativa,
            ]);

            // 2. Se marcado checkbox, estornar item gerando movimentação de 'entrada'
            // (entregas retroativas nunca deram baixa, então não há estoque a estornar)
            if ($estornarEstoque && !$entrega->ss_e_tx_retroativo) {
                EpiEstoque::create([
                    'ss_e_nb_epi_id' => $entrega->ss_e_nb_epi_id,
                    'ss_e_nb_variacao_id' => $entrega->ss_e_nb_variacao_id,
                    'ss_e_nb_empresa_id' => $entrega->ss_e_nb_empresa_id ?? 0,
                    'ss_e_nb_quantidade' => $entrega->ss_e_nb_quantidade,
                    'ss_e_tx_tipo' => 'entrada',
                    'ss_e_tx_data' => now(),
                    'ss_e_tx_motivo' => "Estorno por cancelamento da entrega #{$entrega->ss_e_nb_id}. Justificativa: {$justificativa}",
                    'ss_e_nb_userCadastro' => Auth::id(),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Entrega cancelada e inativada com sucesso!');
    }

    /**
     * Ajusta manualmente a data de vencimento de uma entrega de EPI
     * (regularização de validade quando a vida útil foi corrigida no catálogo).
     */
    public function editarVencimentoEntrega(Request $request, $id)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_e_tx_vencimento' => 'required|date',
        ]);

        $entrega = EpiEntrega::findOrFail($id);
        $novaData = date('d/m/Y', strtotime($request->input('ss_e_tx_vencimento')));
        $entrega->update(['ss_e_tx_vencimento' => $request->input('ss_e_tx_vencimento')]);

        return redirect()->back()->with('success', "Vencimento da entrega #{$entrega->ss_e_nb_id} atualizado para {$novaData}!");
    }

    /**
     * API: Retorna as entregas ativas de um colaborador (para o modal de devolução).
     */
    public function getEntregasColaborador($colaboradorId)
    {
        $this->ensureTablesExist();

        $entregas = EpiEntrega::with(['epi', 'variacao'])
            ->where('ss_e_nb_colaborador_id', $colaboradorId)
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->get()
            ->map(function ($ent) {
                return [
                    'id' => $ent->ss_e_nb_id,
                    'item' => $ent->epi->ss_e_tx_item ?? 'EPI N/D',
                    'variacao' => $ent->variacao->ss_ev_tx_nome ?? null,
                    'quantidade' => (int) $ent->ss_e_nb_quantidade,
                    'data_entrega' => $ent->ss_e_tx_data_entrega ? date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) : null,
                    'vencimento' => $ent->ss_e_tx_vencimento ? date('d/m/Y', strtotime($ent->ss_e_tx_vencimento)) : null,
                    'ca' => $ent->epi->ss_e_tx_ca ?? null,
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $entregas]);
    }

    /**
     * API: Retorna os funcionários com entregas ativas de um EPI específico.
     */
    public function getEntregasPorEpi($epiId)
    {
        $this->ensureTablesExist();

        $epi = Epi::findOrFail($epiId);

        $entregas = EpiEntrega::with(['colaborador', 'variacao'])
            ->where('ss_e_nb_epi_id', $epiId)
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->get()
            ->map(function ($ent) {
                return [
                    'id' => $ent->ss_e_nb_id,
                    'colaborador' => $ent->colaborador->ss_c_tx_nome ?? 'N/D',
                    'matricula' => $ent->colaborador->ss_c_tx_matricula ?? '-',
                    'cargo' => $ent->colaborador->ss_c_tx_cargo ?? '-',
                    'variacao' => $ent->variacao->ss_ev_tx_nome ?? null,
                    'quantidade' => (int) $ent->ss_e_nb_quantidade,
                    'data_entrega' => $ent->ss_e_tx_data_entrega ? date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) : null,
                    'vencimento' => $ent->ss_e_tx_vencimento ? date('d/m/Y', strtotime($ent->ss_e_tx_vencimento)) : null,
                    'status_assinatura' => $ent->ss_e_tx_status_assinatura ?? null,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'epi' => $epi->ss_e_tx_item,
            'data' => $entregas,
        ]);
    }

    /**
     * Registra uma devolução/encerramento de EPI entregue.
     * Destinos: estoque (retorna ao estoque), descarte (encerra) ou inspecao (pendência do gestor).
     */
    public function devolucaoStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_ed_nb_entrega_id' => 'required|integer',
            'ss_ed_nb_quantidade' => 'required|integer|min:1',
            'ss_ed_tx_motivo' => 'required|in:avaria,perdido,extraviado,devolvido_empresa,vencido,outro',
            'ss_ed_tx_destino' => 'required|in:estoque,descarte,inspecao',
        ], [
            'ss_ed_nb_entrega_id.required' => 'Selecione a entrega que será devolvida.',
            'ss_ed_nb_quantidade.required' => 'Informe a quantidade devolvida.',
            'ss_ed_nb_quantidade.min' => 'A quantidade devolvida deve ser maior que zero.',
            'ss_ed_tx_motivo.required' => 'Selecione o motivo da devolução.',
            'ss_ed_tx_destino.required' => 'Selecione o destino do item (estoque, descarte ou inspeção).',
        ]);

        $entrega = EpiEntrega::findOrFail($request->input('ss_ed_nb_entrega_id'));

        if (in_array($entrega->ss_e_tx_status, ['inativo', 'devolvido'])) {
            return redirect()->back()->with('error', 'Esta entrega já foi encerrada ou inativada!');
        }

        $quantidade = (int) $request->input('ss_ed_nb_quantidade');

        if ($quantidade > (int) $entrega->ss_e_nb_quantidade) {
            return redirect()->back()->with('error', 'A quantidade devolvida não pode ser maior que a quantidade entregue (' . $entrega->ss_e_nb_quantidade . ')!');
        }

        $motivo = $request->input('ss_ed_tx_motivo');
        $destino = $request->input('ss_ed_tx_destino');
        $observacao = $request->input('ss_ed_tx_observacao');

        $labelsMotivo = [
            'avaria' => 'Avaria / Danificado',
            'perdido' => 'Perdido',
            'extraviado' => 'Extraviado',
            'devolvido_empresa' => 'Devolvido à empresa',
            'vencido' => 'Vencido',
            'outro' => 'Outro',
        ];
        $motivoLabel = $labelsMotivo[$motivo] ?? $motivo;

        DB::transaction(function () use ($entrega, $quantidade, $motivo, $motivoLabel, $destino, $observacao) {
            $devolucao = EpiDevolucao::create([
                'ss_ed_nb_entrega_id' => $entrega->ss_e_nb_id,
                'ss_ed_nb_epi_id' => $entrega->ss_e_nb_epi_id,
                'ss_ed_nb_colaborador_id' => $entrega->ss_e_nb_colaborador_id,
                'ss_ed_nb_empresa_id' => $entrega->ss_e_nb_empresa_id,
                'ss_ed_nb_variacao_id' => $entrega->ss_e_nb_variacao_id,
                'ss_ed_nb_quantidade' => $quantidade,
                'ss_ed_tx_motivo' => $motivo,
                'ss_ed_tx_destino' => $destino,
                'ss_ed_tx_status' => $destino === 'inspecao' ? 'pendente' : 'concluida',
                'ss_ed_tx_observacao' => $observacao,
                'ss_ed_nb_userRegistro' => Auth::id(),
                'ss_ed_tx_data_registro' => now(),
            ]);

            $devolucaoId = $devolucao->ss_ed_nb_id;

            // Retorno ao estoque (imediato ou após inspeção)
            if ($destino === 'estoque') {
                EpiEstoque::create([
                    'ss_e_nb_epi_id' => $entrega->ss_e_nb_epi_id,
                    'ss_e_nb_empresa_id' => $entrega->ss_e_nb_empresa_id ?? 0,
                    'ss_e_nb_variacao_id' => $entrega->ss_e_nb_variacao_id,
                    'ss_e_nb_quantidade' => $quantidade,
                    'ss_e_tx_tipo' => 'devolucao',
                    'ss_e_tx_data' => now(),
                    'ss_e_tx_motivo' => "Devolução #{$devolucaoId} - {$motivoLabel}: retorno ao estoque" . ($observacao ? " - {$observacao}" : ''),
                    'ss_e_nb_userCadastro' => Auth::id(),
                ]);
            }

            // Encerra o monitoramento de validade da entrega quando devolvido o total
            if ($quantidade >= (int) $entrega->ss_e_nb_quantidade) {
                $entrega->update([
                    'ss_e_tx_status' => 'devolvido',
                    'ss_e_tx_justificativa_exclusao' => "Devolução #{$devolucaoId} - {$motivoLabel}" . ($observacao ? " - {$observacao}" : ''),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Devolução registrada com sucesso!');
    }

    /**
     * Decisão do gestor sobre devoluções em pendência de inspeção:
     * estornar (volta ao estoque) ou descartar (encerra o item).
     */
    public function inspecaoDecidir(Request $request, $id)
    {
        $this->ensureTablesExist();

        $request->validate([
            'action' => 'required|in:estornar,descartar',
        ]);

        $devolucao = EpiDevolucao::findOrFail($id);

        if ($devolucao->ss_ed_tx_status !== 'pendente') {
            return redirect()->back()->with('error', 'Esta devolução já foi decidida!');
        }

        $action = $request->input('action');
        $resultado = $action === 'estornar' ? 'estornado' : 'descartado';

        DB::transaction(function () use ($devolucao, $action, $resultado) {
            $devolucao->update([
                'ss_ed_tx_status' => 'concluida',
                'ss_ed_tx_resultado_inspecao' => $resultado,
                'ss_ed_nb_userDecisao' => Auth::id(),
                'ss_ed_tx_data_decisao' => now(),
            ]);

            if ($action === 'estornar') {
                EpiEstoque::create([
                    'ss_e_nb_epi_id' => $devolucao->ss_ed_nb_epi_id,
                    'ss_e_nb_empresa_id' => $devolucao->ss_ed_nb_empresa_id ?? 0,
                    'ss_e_nb_variacao_id' => $devolucao->ss_ed_nb_variacao_id,
                    'ss_e_nb_quantidade' => $devolucao->ss_ed_nb_quantidade,
                    'ss_e_tx_tipo' => 'devolucao',
                    'ss_e_tx_data' => now(),
                    'ss_e_tx_motivo' => "Aprovado na inspeção (devolução #{$devolucao->ss_ed_nb_id}): item estornado para o estoque",
                    'ss_e_nb_userCadastro' => Auth::id(),
                ]);
            }
        });

        return redirect()->back()->with('success', $action === 'estornar'
            ? "Item da devolução #{$devolucao->ss_ed_nb_id} estornado para o estoque com sucesso!"
            : "Item da devolução #{$devolucao->ss_ed_nb_id} confirmado como descarte!");
    }

    /**
     * Ficha Individual do Colaborador (Regra 3 & Visualização/Impressão PDF).
     */
    public function fichaColaborador($colaborador_id)
    {
        $this->ensureTablesExist();

        $colaborador = EpiColaborador::findOrFail($colaborador_id);
        
        // Omitir registros inativos conforme regra.
        // Ordenado pela DATA DA ENTREGA do mais antigo para o mais novo:
        // as entregas mais recentes sempre vão para o fim do documento/ficha.
        $entregas = EpiEntrega::with(['epi', 'variacao'])
            ->where('ss_e_nb_colaborador_id', $colaborador_id)
            ->where('ss_e_tx_status', '<>', 'inativo')
            ->orderBy('ss_e_tx_data_entrega', 'asc')
            ->orderBy('ss_e_nb_id', 'asc')
            ->get();

        return view('epi.ficha', compact('colaborador', 'entregas'));
    }

    /**
     * API: Retorna entregas pendentes de assinatura para o colaborador logado.
     */
    public function pendentesAssinatura()
    {
        $this->ensureTablesExist();

        $user = Auth::user();
        $colaborador = EpiColaborador::where('ss_c_tx_cpf', $user->cpf)->first();

        if (!$colaborador) {
            if (request()->wantsJson()) {
                return response()->json(['status' => 'success', 'data' => [], 'count' => 0]);
            }
            return view('epi.assinaturas', ['pendentes' => collect(), 'count' => 0, 'colaborador' => null]);
        }

        $pendentes = EpiEntrega::with(['epi', 'variacao'])
            ->pendentesAssinatura($colaborador->ss_c_nb_id)
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->get();

        // Agrupar por grupo_assinatura
        $grupos = $pendentes->groupBy(function ($item) {
            return $item->ss_e_tx_grupo_assinatura ?: 'grupo_' . $item->ss_e_nb_id;
        });

        $count = $grupos->count();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'success', 'data' => $pendentes, 'count' => $count]);
        }

        return view('epi.assinaturas', compact('grupos', 'count', 'colaborador'));
    }

    /**
     * API: Funcionário assina uma entrega pendente.
     */
    public function assinarEntrega(Request $request, $id)
    {
        $this->ensureTablesExist();

        $user = Auth::user();
        $colaborador = EpiColaborador::where('ss_c_tx_cpf', $user->cpf)->first();

        if (!$colaborador) {
            return response()->json(['status' => 'error', 'message' => 'Colaborador não encontrado!'], 404);
        }

        $entrega = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $request->validate([
            'ss_e_tx_assinatura' => 'required|string',
        ]);

        $grupo = $entrega->ss_e_tx_grupo_assinatura;
        $assinatura = $request->input('ss_e_tx_assinatura');
        $agora = now();

        // Assinar todas as entregas do mesmo grupo
        $query = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id);
        if ($grupo) {
            $query->porGrupoAssinatura($grupo);
        } else {
            $query->where('ss_e_nb_id', $id);
        }

        $query->update([
            'ss_e_tx_assinatura' => $assinatura,
            'ss_e_tx_status_assinatura' => 'assinada',
            'ss_e_tx_data_assinatura' => $agora,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Assinatura registrada com sucesso!']);
        }

        return redirect()->route('epi.assinaturas')->with('success', 'Assinatura registrada com sucesso!');
    }

    /**
     * API: Funcionário nega assinatura de uma entrega pendente.
     */
    public function negarAssinatura(Request $request, $id)
    {
        $this->ensureTablesExist();

        $user = Auth::user();
        $colaborador = EpiColaborador::where('ss_c_tx_cpf', $user->cpf)->first();

        if (!$colaborador) {
            return response()->json(['status' => 'error', 'message' => 'Colaborador não encontrado!'], 404);
        }

        $entrega = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $grupo = $entrega->ss_e_tx_grupo_assinatura;
        $justificativa = $request->input('ss_e_tx_justificativa_negacao');

        // Negar todas as entregas do mesmo grupo
        $query = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id);
        if ($grupo) {
            $query->porGrupoAssinatura($grupo);
        } else {
            $query->where('ss_e_nb_id', $id);
        }

        $query->update([
            'ss_e_tx_status_assinatura' => 'negada',
            'ss_e_tx_justificativa_negacao' => $justificativa,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Assinatura recusada. A gestão será notificada.']);
        }

        return redirect()->route('epi.assinaturas')->with('success', 'Assinatura recusada.');
    }

    /**
     * Gestão de assinaturas (para gestores).
     */
    public function gestaoAssinaturas()
    {
        $this->ensureTablesExist();

        $assinadas = EpiEntrega::with(['colaborador', 'epi', 'variacao'])
            ->where('ss_e_tx_requer_assinatura', true)
            ->where('ss_e_tx_status_assinatura', 'assinada')
            ->orderBy('ss_e_tx_data_assinatura', 'desc')
            ->limit(100)
            ->get();

        $negadas = EpiEntrega::with(['colaborador', 'epi', 'variacao'])
            ->where('ss_e_tx_requer_assinatura', true)
            ->where('ss_e_tx_status_assinatura', 'negada')
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->limit(100)
            ->get();

        $pendentes = EpiEntrega::with(['colaborador', 'epi', 'variacao'])
            ->where('ss_e_tx_requer_assinatura', true)
            ->where('ss_e_tx_status_assinatura', 'pendente')
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->limit(100)
            ->get();

        return view('epi.gestao_assinaturas', compact('assinadas', 'negadas', 'pendentes'));
    }

    /**
     * Gestor altera/cancela uma entrega que foi negada.
     */
    public function alterarEntrega(Request $request, $id)
    {
        $this->ensureTablesExist();

        $entrega = EpiEntrega::findOrFail($id);

        if ($entrega->ss_e_tx_status_assinatura !== 'negada') {
            return redirect()->back()->with('error', 'Apenas entregas com assinatura negada podem ser alteradas.');
        }

        $action = $request->input('action', 'cancelar');
        $justificativa = $request->input('ss_e_tx_justificativa_exclusao');

        if ($action === 'cancelar') {
            $entrega->update([
                'ss_e_tx_status' => 'inativo',
                'ss_e_tx_justificativa_exclusao' => $justificativa ? "[Gestão] {$justificativa}" : 'Cancelado pela gestão após recusa do colaborador.',
            ]);

            return redirect()->back()->with('success', 'Entrega cancelada com sucesso!');
        }

        if ($action === 'reativar') {
            $entrega->update([
                'ss_e_tx_status_assinatura' => 'pendente',
                'ss_e_tx_justificativa_negacao' => null,
                'ss_e_tx_assinatura' => null,
            ]);

            return redirect()->back()->with('success', 'Entrega reenviada para assinatura do colaborador!');
        }

        return redirect()->back()->with('error', 'Ação inválida.');
    }

    /**
     * Cadastro / Atualização de Colaborador na tabela ss_colaborador.
     */
    public function colaboradorStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_c_tx_nome' => 'required|string|max:255',
        ]);

        $id = $request->input('ss_c_nb_id');
        $dados = [
            'ss_c_tx_nome' => $request->input('ss_c_tx_nome'),
            'ss_c_tx_cpf' => $request->input('ss_c_tx_cpf'),
            'ss_c_tx_matricula' => $request->input('ss_c_tx_matricula'),
            'ss_c_tx_cargo' => $request->input('ss_c_tx_cargo'),
            'ss_c_tx_status' => $request->input('ss_c_tx_status', 'ativo'),
            'ss_c_nb_empresa_id' => $request->input('ss_c_nb_empresa_id', 0),
        ];

        if (!empty($id)) {
            EpiColaborador::where('ss_c_nb_id', $id)->update($dados);
            $msg = 'Colaborador atualizado com sucesso!';
        } else {
            EpiColaborador::create($dados);
            $msg = 'Colaborador cadastrado com sucesso!';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Baixar Modelo CSV com UTF-8 BOM headers (compatível com Excel PT-BR).
     */
    public function modeloCsv()
    {
        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=modelo_importacao_epis.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Escrever UTF-8 BOM (\xEF\xBB\xBF) para compatibilidade perfeita no Excel PT-BR
            fputs($file, "\xEF\xBB\xBF");

            // Cabecalhos
            fputcsv($file, [
                'Grupo',
                'Subgrupo',
                'Item',
                'Descricao',
                'Fabricante',
                'CA',
                'ValidadeCA',
                'VidaUtilDias',
                'Modelo'
            ], ';');

            // Exemplo 1
            fputcsv($file, [
                'PROTEÇÃO DA CABEÇA',
                'Capacetes',
                'Capacete de Segurança Tipo Aba Frontal',
                'Capacete contra impactos de objetos sobre o crânio',
                'MSA',
                '12345',
                '2028-12-31',
                '365',
                'V-Gard'
            ], ';');

            // Exemplo 2
            fputcsv($file, [
                'EPI PARA PROTEÇÃO DOS OLHOS E FACE',
                'Óculos de Segurança',
                'Óculos de Proteção Incolor',
                'Proteção contra partículas volantes multidirecionais',
                '3M',
                '67890',
                '2027-06-30',
                '180',
                'Virtua'
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar Catálogo de EPIs em CSV UTF-8 BOM.
     */
    public function exportCsv()
    {
        $this->ensureTablesExist();

        $epis = Epi::orderBy('ss_e_tx_grupo')->orderBy('ss_e_tx_item')->get();

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=catalogo_epis_" . date('Y-m-d_H-i') . ".csv",
        ];

        $callback = function () use ($epis) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID',
                'Grupo',
                'Subgrupo',
                'Item',
                'Descricao',
                'Fabricante',
                'CA',
                'ValidadeCA',
                'VidaUtilDias',
                'Status',
                'TipoCadastro',
                'Modelo'
            ], ';');

            foreach ($epis as $epi) {
                fputcsv($file, [
                    $epi->ss_e_nb_id,
                    $epi->ss_e_tx_grupo,
                    $epi->ss_e_tx_subgrupo,
                    $epi->ss_e_tx_item,
                    $epi->ss_e_tx_descricao,
                    $epi->ss_e_tx_fabricante,
                    $epi->ss_e_tx_ca,
                    $epi->ss_e_tx_validade_ca,
                    $epi->ss_e_nb_vida_util_dias,
                    $epi->ss_e_tx_status,
                    $epi->ss_e_tx_cadastro_tipo,
                    $epi->ss_e_tx_modelo,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar EPIs em massa via Upload de CSV (Regra 7).
     */
    public function importCsv(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'arquivo_csv' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('arquivo_csv');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return redirect()->back()->with('error', 'Não foi possível abrir o arquivo CSV.');
        }

        // Remover BOM se presente
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $inseridos = 0;
        $atualizados = 0;
        $erros = 0;
        $linha = 0;

        // Detectar delimitador (ponto e vírgula ou vírgula)
        $primeiraLinha = fgets($handle);
        $delimitador = (substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',')) ? ';' : ',';
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }

        // Pular cabeçalho
        fgetcsv($handle, 0, $delimitador);

        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            $linha++;
            if (empty($data) || count($data) < 3) {
                continue;
            }

            $grupo = trim($data[0] ?? '');
            $subgrupo = trim($data[1] ?? '');
            $item = trim($data[2] ?? '');
            $descricao = trim($data[3] ?? '');
            $fabricante = trim($data[4] ?? '');
            $ca = trim($data[5] ?? '');
            $validadeCa = !empty($data[6]) ? trim($data[6]) : null;
            $vidaUtil = isset($data[7]) ? (int)trim($data[7]) : 0;
            $modelo = trim($data[8] ?? '');

            if (empty($grupo) || empty($item)) {
                $erros++;
                continue;
            }

            $existente = Epi::where('ss_e_tx_grupo', $grupo)
                ->where('ss_e_tx_item', $item)
                ->first();

            $dados = [
                'ss_e_tx_grupo' => $grupo,
                'ss_e_tx_subgrupo' => $subgrupo,
                'ss_e_tx_item' => $item,
                'ss_e_tx_descricao' => $descricao,
                'ss_e_tx_fabricante' => $fabricante,
                'ss_e_tx_ca' => $ca,
                'ss_e_tx_validade_ca' => $validadeCa,
                'ss_e_nb_vida_util_dias' => $vidaUtil,
                'ss_e_tx_modelo' => $modelo,
                'ss_e_tx_status' => 'ativo',
            ];

            if ($existente) {
                $existente->update($dados);
                $atualizados++;
            } else {
                $dados['ss_e_tx_cadastro_tipo'] = 'estoque';
                $dados['ss_e_nb_userCadastro'] = Auth::id();
                $dados['ss_e_tx_dataCadastro'] = now();
                Epi::create($dados);
                $inseridos++;
            }
        }

        fclose($handle);

        $msg = "Importação CSV concluída! Inseridos: {$inseridos}, Atualizados: {$atualizados}" . ($erros > 0 ? ", Erros: {$erros}" : "");
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Cadastro / Atualização de Filial.
     */
    public function filialStore(Request $request)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_f_tx_nome' => 'required|string|max:255',
        ]);

        $id = $request->input('ss_f_nb_id');
        $dados = [
            'ss_f_tx_nome' => $request->input('ss_f_tx_nome'),
            'ss_f_tx_codigo' => $request->input('ss_f_tx_codigo'),
            'ss_f_tx_cidade' => $request->input('ss_f_tx_cidade'),
            'ss_f_tx_status' => $request->input('ss_f_tx_status', 'ativo'),
        ];

        if (!empty($id)) {
            DB::table('ss_filial')->where('ss_f_nb_id', $id)->update($dados);
            $msg = 'Filial atualizada com sucesso!';
        } else {
            DB::table('ss_filial')->insert($dados);
            $msg = 'Filial cadastrada com sucesso!';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Alternar Status da Filial.
     */
    public function filialToggleStatus($id)
    {
        $this->ensureTablesExist();

        $filial = DB::table('ss_filial')->where('ss_f_nb_id', $id)->first();
        if ($filial) {
            $novoStatus = ($filial->ss_f_tx_status === 'ativo') ? 'inativo' : 'ativo';
            DB::table('ss_filial')->where('ss_f_nb_id', $id)->update(['ss_f_tx_status' => $novoStatus]);
        }

        return redirect()->back()->with('success', 'Status da filial alterado com sucesso!');
    }

    /**
     * Excluir Filial.
     */
    public function filialDestroy($id)
    {
        $this->ensureTablesExist();

        DB::table('ss_filial')->where('ss_f_nb_id', $id)->delete();

        return redirect()->back()->with('success', 'Filial excluída com sucesso!');
    }
}
