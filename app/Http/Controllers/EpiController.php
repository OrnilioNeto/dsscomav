<?php

namespace App\Http\Controllers;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\EpiKit;
use App\Models\EpiKitItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EpiController extends Controller
{
    /**
     * Garante auto-criação e seeder automatizado das 6 tabelas em qualquer ambiente/banco.
     */
    private function ensureTablesExist(): void
    {
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

        // Popular EPIs universais se ss_epi estiver vazia
        if (DB::table('ss_epi')->count() === 0) {
            $defaultItems = [
                ['ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA', 'ss_e_tx_subgrupo' => 'Capacetes', 'ss_e_tx_item' => 'Capacete de Segurança', 'ss_e_tx_descricao' => 'capacete para proteção contra impactos de objetos sobre o crânio', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'PROTEÇÃO DA CABEÇA', 'ss_e_tx_subgrupo' => 'Capacetes', 'ss_e_tx_item' => 'Capacete de Segurança Com Carneira', 'ss_e_tx_descricao' => 'capacete para proteção contra impactos de objetos sobre o crânio', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS OLHOS E FACE', 'ss_e_tx_subgrupo' => 'Óculos de Segurança', 'ss_e_tx_item' => 'Óculos de Segurança Anti-Embaçante', 'ss_e_tx_descricao' => 'óculos para proteção dos olhos contra impactos de partículas volantes', 'ss_e_nb_vida_util_dias' => 180],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS OLHOS E FACE', 'ss_e_tx_subgrupo' => 'Óculos de Segurança', 'ss_e_tx_item' => 'Óculos Ampla Visão', 'ss_e_tx_descricao' => 'óculos para proteção dos olhos contra impactos de partículas volantes', 'ss_e_nb_vida_util_dias' => 180],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO AUDITIVA', 'ss_e_tx_subgrupo' => 'Protetores Auriculares', 'ss_e_tx_item' => 'Protetor Auricular de Silicone Tipo Plug', 'ss_e_tx_descricao' => 'para proteção do sistema auditivo contra níveis de pressão sonora superiores ao estabelecido na NR-15', 'ss_e_nb_vida_util_dias' => 90],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO AUDITIVA', 'ss_e_tx_subgrupo' => 'Protetores Auriculares', 'ss_e_tx_item' => 'Protetor Auricular Abafador Concha', 'ss_e_tx_descricao' => 'para proteção do sistema auditivo contra níveis de pressão sonora superiores ao estabelecido na NR-15', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO RESPIRATÓRIA', 'ss_e_tx_subgrupo' => 'Respiradores e Máscaras', 'ss_e_tx_item' => 'Respirador purificador de ar não motorizado', 'ss_e_tx_descricao' => 'com filtros combinados para proteção das vias respiratórias contra gases e vapores', 'ss_e_nb_vida_util_dias' => 180],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO RESPIRATÓRIA', 'ss_e_tx_subgrupo' => 'Respiradores e Máscaras', 'ss_e_tx_item' => 'Máscara Descartável Pff2', 'ss_e_tx_descricao' => 'peça semifacial filtrante para partículas PFF2 para proteção das vias respiratórias', 'ss_e_nb_vida_util_dias' => 30],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES', 'ss_e_tx_subgrupo' => 'Luvas de Proteção', 'ss_e_tx_item' => 'luvas pvc', 'ss_e_tx_descricao' => 'luvas para proteção das mãos contra agentes químicos', 'ss_e_nb_vida_util_dias' => 90],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES', 'ss_e_tx_subgrupo' => 'Luvas de Proteção', 'ss_e_tx_item' => 'luvas nitrilica', 'ss_e_tx_descricao' => 'luvas para proteção das mãos contra agentes químicos', 'ss_e_nb_vida_util_dias' => 90],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS SUPERIORES', 'ss_e_tx_subgrupo' => 'Luvas de Proteção', 'ss_e_tx_item' => 'luvas algodao', 'ss_e_tx_descricao' => 'luvas para proteção das mãos contra vibrações', 'ss_e_nb_vida_util_dias' => 60],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO DOS MEMBROS INFERIORES', 'ss_e_tx_subgrupo' => 'Calçados de Segurança', 'ss_e_tx_item' => 'Bota Botina Bico PVC', 'ss_e_tx_descricao' => 'calçado para proteção contra impactos de quedas de objetos sobre os artelhos', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL', 'ss_e_tx_subgrupo' => 'Cintos e Talabartes', 'ss_e_tx_item' => 'Cinto Paraquedista', 'ss_e_tx_descricao' => 'Cinturão de segurança com dispositivo trava-queda para proteção do usuário', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL', 'ss_e_tx_subgrupo' => 'Cintos e Talabartes', 'ss_e_tx_item' => 'Cinto Paraquedista + Talabarte', 'ss_e_tx_descricao' => 'cinturão de segurança com talabarte para proteção do usuário', 'ss_e_nb_vida_util_dias' => 365],
                ['ss_e_tx_grupo' => 'EPI PARA PROTEÇÃO CONTRA QUEDAS COM DIFERENÇA DE NÍVEL', 'ss_e_tx_subgrupo' => 'Cintos e Talabartes', 'ss_e_tx_item' => 'Talabarte', 'ss_e_tx_descricao' => 'talabarte para proteção do usuário contra riscos de queda', 'ss_e_nb_vida_util_dias' => 365],
            ];

            $now = date('Y-m-d H:i:s');
            foreach ($defaultItems as $item) {
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

        // Sincronizar colaboradores se ss_colaborador estiver vazia
        if (Schema::hasTable('users') && DB::table('ss_colaborador')->count() === 0) {
            $users = DB::table('users')->get();
            foreach ($users as $u) {
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
        
        $totalEntradasEstoque = DB::table('ss_epi_estoque')->where('ss_e_tx_tipo', 'entrada')->sum('ss_e_nb_quantidade');
        $totalSaidasEstoque = DB::table('ss_epi_estoque')->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');
        $saldoEstoqueTotal = max(0, $totalEntradasEstoque - $totalSaidasEstoque);

        $totalEntregasAtivas = EpiEntrega::where('ss_e_tx_status', '<>', 'inativo')->count();
        $totalColaboradoresElegiveis = EpiColaborador::elegiveisParaEntrega()->count();

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
        $episCatalogo = $queryCatalogo->orderBy('ss_e_tx_grupo')->orderBy('ss_e_tx_item')->get();

        // Grupos únicos para os filtros
        $gruposUnicos = Epi::distinct()->pluck('ss_e_tx_grupo')->filter()->values();

        // 3. Colaboradores Elegíveis (Regra 4: sem diretores)
        $colaboradores = EpiColaborador::elegiveisParaEntrega()->orderBy('ss_c_tx_nome')->get();

        // 4. Kits Cadastrados
        $kits = EpiKit::with('itens.epi')->where('ss_k_tx_status', 'ativo')->get();

        // 5. Movimentações de Estoque Recentes
        $estoqueMovimentos = EpiEstoque::with('epi')
            ->orderBy('ss_e_tx_data', 'desc')
            ->limit(100)
            ->get();

        // 6. Entregas Recentes (Omitindo inativos conforme regra)
        $entregasRecentes = EpiEntrega::with(['colaborador', 'epi'])
            ->where('ss_e_tx_status', '<>', 'inativo')
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->orderBy('ss_e_nb_id', 'desc')
            ->limit(100)
            ->get();

        // Lista de Filiais dinâmicas (Matriz + Filiais cadastradas ativas)
        $filiais = $this->getFiliaisList();
        $filiaisCadastradas = DB::table('ss_filial')->orderBy('ss_f_nb_id', 'desc')->get();

        return view('epi.index', compact(
            'totalCatalogo',
            'saldoEstoqueTotal',
            'totalEntregasAtivas',
            'totalColaboradoresElegiveis',
            'episCatalogo',
            'gruposUnicos',
            'colaboradores',
            'kits',
            'estoqueMovimentos',
            'entregasRecentes',
            'filiais',
            'filialSelecionada',
            'filiaisCadastradas'
        ));
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
            $disponibilidade = 'local'; // disponível na filial atual
            if ($saldoAtual <= 0 && $saldoRede > 0) {
                $disponibilidade = 'externo'; // cor laranja #d97706, negrito itálico
            } elseif ($saldoRede <= 0) {
                $disponibilidade = 'esgotado'; // saldo zero na rede inteira
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
            ];
        }

        return response()->json([
            'status' => 'success',
            'filial_id' => $filialAtual,
            'data' => $resultado,
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
            Epi::create($dados);
            $msg = 'EPI cadastrado com sucesso!';
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
            'ss_e_nb_quantidade' => 'required|integer|min:1',
            'ss_e_tx_tipo' => 'required|in:entrada,saida,substituicao',
        ]);

        $quantidade = (int)$request->input('ss_e_nb_quantidade');
        $valorUnitario = $request->filled('ss_e_db_valor_unitario') ? (float)$request->input('ss_e_db_valor_unitario') : null;
        $valorTotal = $valorUnitario !== null ? ($valorUnitario * $quantidade) : null;

        $dados = [
            'ss_e_nb_epi_id' => $request->input('ss_e_nb_epi_id'),
            'ss_e_nb_empresa_id' => $request->input('ss_e_nb_empresa_id', 0),
            'ss_e_nb_quantidade' => $quantidade,
            'ss_e_tx_tipo' => $request->input('ss_e_tx_tipo'),
            'ss_e_db_valor_unitario' => $valorUnitario,
            'ss_e_db_valor_total' => $valorTotal,
            'ss_e_tx_data_recebimento' => $request->input('ss_e_tx_data_recebimento'),
            'ss_e_tx_validade' => $request->input('ss_e_tx_validade'),
            'ss_e_tx_chave_nf' => $request->input('ss_e_tx_chave_nf'),
            'ss_e_tx_fornecedor' => $request->input('ss_e_tx_fornecedor'),
            'ss_e_tx_data' => now(),
            'ss_e_tx_motivo' => $request->input('ss_e_tx_motivo'),
            'ss_e_nb_userCadastro' => Auth::id(),
        ];

        if ($request->hasFile('ss_e_tx_foto')) {
            $path = $request->file('ss_e_tx_foto')->store('estoque_comprovantes', 'public');
            $dados['ss_e_tx_foto'] = '/storage/' . $path;
        }

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

        // 1. Processamento Multi-Motorista (Lote Completo)
        if ($request->has('entregas') && is_array($request->input('entregas'))) {
            $entregasLote = $request->input('entregas');
            $totalProcessados = 0;

            // Validação estrita de saldo de estoque para cada item do lote
            foreach ($entregasLote as $entData) {
                $empId = $entData['ss_e_nb_empresa_id'] ?? $request->input('ss_e_nb_empresa_id', 0);
                foreach ($entData['itens'] ?? [] as $itemData) {
                    $epi = Epi::find($itemData['epi_id']);
                    if (!$epi) continue;
                    $qtd = (int)$itemData['quantidade'];
                    $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empId;
                    $saldoLocal = $epi->getSaldoPorFilial($filialOrigem);

                    if ($saldoLocal < $qtd) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "O item '{$epi->ss_e_tx_item}' não possui saldo suficiente em estoque para concluir a entrega! (Necessário: {$qtd}, Saldo na filial: {$saldoLocal})"
                        ], 422);
                    }
                }
            }

            DB::transaction(function () use ($entregasLote, $request, &$totalProcessados) {
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

                        $diasVidaUtil = (int)$epi->ss_e_nb_vida_util_dias;
                        $vencimento = null;
                        if ($diasVidaUtil > 0) {
                            $vencimento = date('Y-m-d', strtotime("{$dtEntrega} + {$diasVidaUtil} days"));
                        }

                        EpiEntrega::create([
                            'ss_e_nb_colaborador_id' => $colabId,
                            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                            'ss_e_nb_empresa_id' => $empId,
                            'ss_e_tx_data_entrega' => $dtEntrega,
                            'ss_e_nb_quantidade' => $qtd,
                            'ss_e_tx_vencimento' => $vencimento,
                            'ss_e_tx_status' => 'ativo',
                            'ss_e_tx_assinatura' => $sig,
                            'ss_e_tx_observacao' => $obs,
                            'ss_e_nb_userCadastro' => Auth::id(),
                            'ss_e_tx_dataCadastro' => now(),
                        ]);

                        EpiEstoque::create([
                            'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                            'ss_e_nb_empresa_id' => $filialOrigem,
                            'ss_e_nb_quantidade' => $qtd,
                            'ss_e_tx_tipo' => 'saida',
                            'ss_e_tx_data' => now(),
                            'ss_e_tx_motivo' => "Entrega em lote para Colaborador ID #{$colabId}" . ($filialOrigem != $empId ? " (Transferido da Filial #{$filialOrigem})" : ""),
                            'ss_e_nb_userCadastro' => Auth::id(),
                        ]);

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
        foreach ($request->input('itens') as $itemData) {
            $epi = Epi::find($itemData['epi_id']);
            if (!$epi) continue;
            $qtd = (int)$itemData['quantidade'];
            $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empresaId;
            $saldoLocal = $epi->getSaldoPorFilial($filialOrigem);

            if ($saldoLocal < $qtd) {
                return response()->json([
                    'status' => 'error',
                    'message' => "O item '{$epi->ss_e_tx_item}' não possui saldo suficiente em estoque para concluir a entrega! (Necessário: {$qtd}, Saldo na filial: {$saldoLocal})"
                ], 422);
            }
        }

        $fotoCaminho = null;
        if ($request->hasFile('ss_e_tx_foto')) {
            $path = $request->file('ss_e_tx_foto')->store('recibos_entregas', 'public');
            $fotoCaminho = '/storage/' . $path;
        }

        DB::transaction(function () use ($colaboradorId, $dataEntrega, $empresaId, $assinatura, $fotoCaminho, $observacao, $request) {
            foreach ($request->input('itens') as $itemData) {
                $epi = Epi::findOrFail($itemData['epi_id']);
                $qtd = (int)$itemData['quantidade'];
                $filialOrigem = isset($itemData['empresa_origem_id']) ? (int)$itemData['empresa_origem_id'] : (int)$empresaId;

                $diasVidaUtil = (int)$epi->ss_e_nb_vida_util_dias;
                $vencimento = null;
                if ($diasVidaUtil > 0) {
                    $vencimento = date('Y-m-d', strtotime("{$dataEntrega} + {$diasVidaUtil} days"));
                }

                EpiEntrega::create([
                    'ss_e_nb_colaborador_id' => $colaboradorId,
                    'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
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
                ]);

                EpiEstoque::create([
                    'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                    'ss_e_nb_empresa_id' => $filialOrigem,
                    'ss_e_nb_quantidade' => $qtd,
                    'ss_e_tx_tipo' => 'saida',
                    'ss_e_tx_data' => now(),
                    'ss_e_tx_motivo' => "Entrega para Colaborador ID #{$colaboradorId}" . ($filialOrigem != $empresaId ? " (Transferido da Filial #{$filialOrigem})" : ""),
                    'ss_e_nb_userCadastro' => Auth::id(),
                ]);
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
            if ($estornarEstoque) {
                EpiEstoque::create([
                    'ss_e_nb_epi_id' => $entrega->ss_e_nb_epi_id,
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
     * Ficha Individual do Colaborador (Regra 3 & Visualização/Impressão PDF).
     */
    public function fichaColaborador($colaborador_id)
    {
        $this->ensureTablesExist();

        $colaborador = EpiColaborador::findOrFail($colaborador_id);
        
        // Omitir registros inativos conforme regra
        $entregas = EpiEntrega::with('epi')
            ->where('ss_e_nb_colaborador_id', $colaborador_id)
            ->where('ss_e_tx_status', '<>', 'inativo')
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->get();

        return view('epi.ficha', compact('colaborador', 'entregas'));
    }

    /**
     * Cadastro/Edição rápida de Colaborador na tabela ss_colaborador.
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
