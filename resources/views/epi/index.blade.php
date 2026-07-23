@extends('layout')

@section('title', 'Módulo de Saúde e Segurança - Gestão de EPIs')

@section('extra_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(229, 231, 235, 0.8);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .nav-tab-btn {
        transition: all 0.2s ease-in-out;
        border-bottom: 3px solid transparent;
        font-weight: 600;
    }
    .nav-tab-btn.active {
        border-bottom-color: var(--accent, #F28C2B);
        color: var(--primary, #153B2E);
        background-color: rgba(21, 59, 46, 0.05);
    }
    .epi-option-externo {
        color: #d97706 !important;
        font-weight: bold !important;
        font-style: italic !important;
    }
    .signature-container {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background-color: #f8fafc;
        position: relative;
        touch-action: none;
    }
    #signature-canvas {
        width: 100%;
        height: 160px;
        cursor: crosshair;
        background: #ffffff;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Mensagens de Alerta Flash -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-emerald-500 text-xl mr-3"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-rose-500 text-xl mr-3"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
        </div>
    @endif

    <!-- Cabeçalho do Módulo -->
    <div class="glass-card p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-xl bg-emerald-900 text-amber-400 flex items-center justify-center text-2xl shadow-lg">
                    <i class="fas fa-hard-hat"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Módulo de Saúde e Segurança (EPIs)</h1>
                    <p class="text-sm text-gray-500">Gestão de Catálogo Universal, Estoque por Filial e Central de Entregas aos Colaboradores</p>
                </div>
            </div>

            <!-- Seleção de Filial Ativa -->
            <div class="flex items-center bg-gray-100 p-2 rounded-lg border border-gray-200">
                <label for="select-filial-global" class="text-xs font-bold text-gray-700 uppercase tracking-wider mr-2">
                    <i class="fas fa-building mr-1"></i> Filial Ativa:
                </label>
                <select id="select-filial-global" class="bg-white text-sm font-semibold border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-1.5" onchange="atualizarFilialGlobal(this.value)">
                    @foreach($filiais as $fId => $fNome)
                        <option value="{{ $fId }}" {{ (int)$filialSelecionada === (int)$fId ? 'selected' : '' }}>
                            {{ $fNome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Indicadores Numéricos -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                <div class="text-xs font-semibold text-emerald-700 uppercase">Catálogo de EPIs</div>
                <div class="text-2xl font-bold text-emerald-900 mt-1">{{ number_format($totalCatalogo) }}</div>
                <div class="text-xs text-emerald-600 mt-0.5"><i class="fas fa-tag mr-1"></i> Itens Universais e Locais</div>
            </div>
            <div class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                <div class="text-xs font-semibold text-amber-700 uppercase">Saldo em Estoque (Rede)</div>
                <div class="text-2xl font-bold text-amber-900 mt-1">{{ number_format($saldoEstoqueTotal) }}</div>
                <div class="text-xs text-amber-600 mt-0.5"><i class="fas fa-boxes mr-1"></i> Unidades disponíveis</div>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <div class="text-xs font-semibold text-blue-700 uppercase">Entregas Ativas</div>
                <div class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($totalEntregasAtivas) }}</div>
                <div class="text-xs text-blue-600 mt-0.5"><i class="fas fa-clipboard-check mr-1"></i> Com comprovante/visto</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-xl border border-purple-100">
                <div class="text-xs font-semibold text-purple-700 uppercase">Colaboradores Elegíveis</div>
                <div class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($totalColaboradoresElegiveis) }}</div>
                <div class="text-xs text-purple-600 mt-0.5"><i class="fas fa-user-shield mr-1"></i> Sem cargos de diretoria</div>
            </div>
        </div>
    </div>

    <!-- Navegação de Abas -->
    <div class="glass-card mb-6 overflow-hidden">
        <div class="flex flex-wrap border-b border-gray-200 bg-gray-50/50 px-2 pt-2">
            <button type="button" onclick="mudarAba('sacola')" id="tab-btn-sacola" class="nav-tab-btn active px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-shopping-basket mr-2"></i> Central de Entregas (Sacola)
            </button>
            <button type="button" onclick="mudarAba('catalogo')" id="tab-btn-catalogo" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-list-ul mr-2"></i> Catálogo Universal de EPIs
            </button>
            <button type="button" onclick="mudarAba('estoque')" id="tab-btn-estoque" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-warehouse mr-2"></i> Controle de Estoque
            </button>
            <button type="button" onclick="mudarAba('kits')" id="tab-btn-kits" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-briefcase-medical mr-2"></i> Kits de Entrega Rápida
            </button>
            <button type="button" onclick="mudarAba('fichas')" id="tab-btn-fichas" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-id-card-alt mr-2"></i> Fichas & Histórico
            </button>
            <button type="button" onclick="mudarAba('filiais')" id="tab-btn-filiais" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-building mr-2"></i> Cadastro de Filiais
            </button>
        </div>

        <!-- Conteúdo das Abas -->
        <div class="p-6">

            <!-- ABA 1: CENTRAL DE ENTREGAS (SACOLA EM LOTE) -->
            <div id="tab-content-sacola" class="aba-content">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Formulário de Seleção e Montagem da Sacola -->
                    <div class="lg:col-span-1 bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user-check text-emerald-700 mr-2"></i> 1. Dados da Entrega
                        </h3>

                        <!-- Seleção do Colaborador (Filtrado sem Diretores) -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Colaborador Elegível *</label>
                            <select id="sacola-colaborador-id" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- Selecione o Colaborador --</option>
                                @foreach($colaboradores as $c)
                                    <option value="{{ $c->ss_c_nb_id }}">
                                        {{ $c->ss_c_tx_nome }} ({{ $c->ss_c_tx_cargo ?? 'Sem Cargo' }} - Mat: {{ $c->ss_c_tx_matricula ?? 'S/N' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Data da Entrega -->
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Data da Entrega *</label>
                            <input type="date" id="sacola-data-entrega" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500" value="{{ date('Y-m-d') }}">
                        </div>

                        <hr class="my-4 border-gray-200">

                        <h3 class="text-base font-bold text-gray-900 mb-2 flex items-center justify-between">
                            <span><i class="fas fa-plus-circle text-emerald-700 mr-2"></i> 2. Adicionar Itens / Kits</span>
                        </h3>

                        <!-- Checkbox Ocultar Esgotados -->
                        <div class="mb-3 flex items-center">
                            <input type="checkbox" id="chk-ocultar-esgotados" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4" onchange="carregarEstoqueDisponivelAPI()">
                            <label for="chk-ocultar-esgotados" class="ml-2 text-xs text-gray-600 font-medium">Ocultar itens com saldo zero em toda a rede</label>
                        </div>

                        <!-- Opção Adicionar Kit Completo -->
                        @if($kits->count() > 0)
                            <div class="mb-3 p-3 bg-amber-50 rounded-lg border border-amber-200">
                                <label class="block text-xs font-bold text-amber-800 uppercase mb-1">Agadregar Kit Completo:</label>
                                <div class="flex space-x-2">
                                    <select id="sacola-kit-select" class="flex-1 text-sm border-amber-300 rounded-lg shadow-sm">
                                        <option value="">-- Selecione um Kit --</option>
                                        @foreach($kits as $k)
                                            <option value="{{ $k->ss_k_nb_id }}">{{ $k->ss_k_tx_nome }} ({{ $k->itens->count() }} itens)</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="window.adicionarKitSacola()" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-bold hover:bg-amber-700 shadow cursor-pointer">
                                        + Kit
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Adicionar EPI Individual -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Selecionar EPI Individual</label>
                                <select id="sacola-epi-select" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Selecione o EPI --</option>
                                    @foreach($episCatalogo as $epi)
                                        @php
                                            $sAtual = $epi->getSaldoPorFilial($filialSelecionada);
                                            $sRede = $epi->getSaldoTotalRede();
                                            // Exibir apenas EPIs com saldo positivo em estoque
                                            if ($sRede <= 0) {
                                                continue;
                                            }
                                            $label = "{$epi->ss_e_tx_grupo} - {$epi->ss_e_tx_item} " . ($epi->ss_e_tx_ca ? "[CA: {$epi->ss_e_tx_ca}]" : "");
                                            if ($sAtual > 0) {
                                                $label .= " (Saldo local: {$sAtual})";
                                                $style = "font-weight: bold; color: #065f46;";
                                            } else {
                                                $label .= " (Sem saldo nesta filial, disponível na rede: {$sRede})";
                                                $style = "color: #d97706; font-weight: bold; font-style: italic;";
                                            }
                                        @endphp
                                        <option value="{{ $epi->ss_e_nb_id }}" style="{{ $style }}">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex space-x-2">
                                <div class="w-1/3">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Qtd</label>
                                    <input type="number" id="sacola-epi-qtd" min="1" value="1" class="w-full text-sm border-gray-300 rounded-lg shadow-sm">
                                </div>
                                <div class="w-2/3 flex items-end">
                                    <button type="button" onclick="window.adicionarEpiSacola()" class="w-full py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center justify-center cursor-pointer">
                                        <i class="fas fa-cart-plus mr-1"></i> Adicionar à Sacola
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Legenda de Cores de Estoque -->
                        <div class="mt-4 p-3 bg-white rounded-lg border border-gray-200 text-xs space-y-1">
                            <div class="font-bold text-gray-700 mb-1">Legenda de Estoque:</div>
                            <div class="text-gray-700"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500 mr-1"></span> Disponível na Filial Ativa</div>
                            <div class="text-amber-600 font-bold italic"><span class="inline-block w-3 h-3 rounded-full bg-amber-500 mr-1"></span> Saldo 0 local, porém disponível em outra filial</div>
                            <div class="text-gray-400"><span class="inline-block w-3 h-3 rounded-full bg-gray-300 mr-1"></span> Esgotado em todas as filiais</div>
                        </div>
                    </div>

                    <!-- Lista da Sacola de Entregas por Motorista / Colaborador -->
                    <div class="lg:col-span-2 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-shopping-bag text-emerald-700 mr-2"></i> Sacola de Entregas por Motorista / Colaborador
                                </h3>
                                <button type="button" onclick="limparSacola()" class="text-xs text-rose-600 hover:text-rose-800 font-semibold cursor-pointer">
                                    <i class="fas fa-trash-alt mr-1"></i> Esvaziar Toda a Sacola
                                </button>
                            </div>

                            <!-- Container Dinâmico de Cards por Motorista -->
                            <div id="container-sacola-motoristas" class="space-y-6">
                                <div class="bg-white p-8 text-center border border-gray-200 rounded-xl text-gray-400">
                                    <i class="fas fa-shopping-basket text-4xl mb-3 text-gray-300"></i>
                                    <p class="font-medium text-sm">Sua sacola de entregas está vazia.</p>
                                    <p class="text-xs text-gray-400 mt-1">Selecione o motorista/colaborador e adicione itens ou kits à esquerda!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Botão de Confirmação Global para Todos os Motoristas -->
                        <div id="container-btn-global-sacola" class="mt-6 hidden">
                            <button type="button" onclick="confirmarTodasEntregasLote()" class="w-full py-4 bg-emerald-900 hover:bg-emerald-950 text-white font-bold rounded-xl shadow-xl text-base transition flex items-center justify-center cursor-pointer">
                                <i class="fas fa-check-double mr-2 text-xl text-amber-400"></i> REGISTRAR TODAS AS ENTREGAS DE UMA VEZ (TODOS OS MOTORISTAS)
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ABA 2: CATÁLOGO UNIVERSAL DE EPIS -->
            <div id="tab-content-catalogo" class="aba-content hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('epi.index') }}" class="flex flex-wrap items-center gap-3">
                        <input type="hidden" name="filial_id" value="{{ $filialSelecionada }}">
                        <input type="text" name="busca_catalogo" value="{{ request('busca_catalogo') }}" placeholder="Buscar por item, grupo, CA..." class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2">
                        
                        <select name="grupo_catalogo" class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2">
                            <option value="">Todos os Grupos</option>
                            @foreach($gruposUnicos as $g)
                                <option value="{{ $g }}" {{ request('grupo_catalogo') == $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 shadow">
                            <i class="fas fa-search mr-1"></i> Filtrar
                        </button>
                    </form>

                    <!-- Botões de Ação CSV / Cadastro -->
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('epi.modelo-csv') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg border border-gray-300 flex items-center">
                            <i class="fas fa-file-csv mr-1 text-emerald-600"></i> Modelo CSV
                        </a>
                        <a href="{{ route('epi.export-csv') }}" class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200 flex items-center">
                            <i class="fas fa-file-export mr-1"></i> Exportar CSV
                        </a>
                        <button type="button" onclick="abrirModalImportCsv()" class="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 text-xs font-bold rounded-lg border border-blue-200 flex items-center cursor-pointer">
                            <i class="fas fa-file-import mr-1"></i> Importar CSV
                        </button>
                        <button type="button" onclick="abrirModalNovoEpi()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow flex items-center cursor-pointer">
                            <i class="fas fa-plus mr-1"></i> + Cadastrar EPI
                        </button>
                    </div>
                </div>

                <!-- Tabela do Catálogo -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Grupo / Subgrupo</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Item / Descrição</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">CA & Validade</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Vida Útil</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Saldo (Rede)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($episCatalogo as $epi)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 text-xs uppercase">{{ $epi->ss_e_tx_grupo }}</div>
                                        <div class="text-xs text-gray-500">{{ $epi->ss_e_tx_subgrupo ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-emerald-950">{{ $epi->ss_e_tx_item }}</div>
                                        <div class="text-xs text-gray-500 line-clamp-1">{{ $epi->ss_e_tx_descricao }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if($epi->ss_e_tx_ca)
                                            <span class="font-bold px-2 py-0.5 bg-gray-100 border border-gray-300 rounded">CA: {{ $epi->ss_e_tx_ca }}</span>
                                            <div class="text-gray-400 mt-0.5">Val: {{ $epi->ss_e_tx_validade_ca ? date('d/m/Y', strtotime($epi->ss_e_tx_validade_ca)) : '-' }}</div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-xs">
                                        {{ $epi->ss_e_nb_vida_util_dias }} dias
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-800">
                                        {{ $epi->getSaldoTotalRede() }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if($epi->ss_e_tx_cadastro_tipo === 'universal')
                                            <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded font-semibold text-xs">Universal</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-semibold text-xs">Estoque</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if($epi->ss_e_tx_status === 'ativo')
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold">Ativo</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded-full font-bold">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs space-x-1">
                                        <button onclick="editarEpi({{ json_encode($epi) }})" class="p-1.5 text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('epi.catalogo.toggle', $epi->ss_e_nb_id) }}" class="inline" onsubmit="return confirm('Deseja alterar o status deste EPI?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-gray-500 hover:text-gray-800" title="Alternar Status">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">Nenhum EPI encontrado no catálogo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA 3: CONTROLE DE ESTOQUE (MOVIMENTAÇÕES POR FILIAL) -->
            <div id="tab-content-estoque" class="aba-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-bold text-gray-900">
                        <i class="fas fa-boxes text-amber-600 mr-2"></i> Movimentações de Inventário
                    </h3>
                    <button type="button" onclick="abrirModalNovaEntradaEstoque()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                        <i class="fas fa-plus mr-1"></i> + Registrar Lançamento / Entrada
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data / Registro</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Filial</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">EPI</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Qtd</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Valor Total</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">NF / Fornecedor</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Motivo / Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($estoqueMovimentos as $mov)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ date('d/m/Y H:i', strtotime($mov->ss_e_tx_data)) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-800">
                                        {{ $filiais[$mov->ss_e_nb_empresa_id ?? 0] ?? 'Matriz' }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-emerald-950">
                                        {{ $mov->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($mov->ss_e_tx_tipo === 'entrada')
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-xs">Entrada</span>
                                        @elseif($mov->ss_e_tx_tipo === 'saida')
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold text-xs">Saída</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-xs">Substituição</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-900">
                                        {{ $mov->ss_e_nb_quantidade }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-700">
                                        {{ $mov->ss_e_db_valor_total ? 'R$ ' . number_format($mov->ss_e_db_valor_total, 2, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        @if($mov->ss_e_tx_chave_nf)
                                            <div class="font-bold">NF: {{ $mov->ss_e_tx_chave_nf }}</div>
                                        @endif
                                        <div class="text-gray-500">{{ $mov->ss_e_tx_fornecedor ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="text-gray-600 line-clamp-1">{{ $mov->ss_e_tx_motivo ?? '-' }}</div>
                                        @if($mov->ss_e_tx_foto)
                                            <a href="{{ $mov->ss_e_tx_foto }}" target="_blank" class="text-blue-600 font-bold text-xs hover:underline">
                                                <i class="fas fa-image"></i> Ver Foto
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">Nenhuma movimentação de estoque registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA 4: KITS DE ENTREGA RÁPIDA -->
            <div id="tab-content-kits" class="aba-content hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-bold text-gray-900">
                        <i class="fas fa-briefcase-medical text-amber-600 mr-2"></i> Kits de EPIs para Entrega Rápida
                    </h3>
                    <button type="button" onclick="abrirModalNovoKit()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                        <i class="fas fa-plus mr-1"></i> + Cadastrar Novo Kit
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @forelse($kits as $kit)
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-bold text-emerald-950 text-base flex items-center">
                                        <i class="fas fa-box text-amber-500 mr-2"></i> {{ $kit->ss_k_tx_nome }}
                                    </h4>
                                    <form method="POST" action="{{ route('epi.kits.destroy', $kit->ss_k_nb_id) }}" onsubmit="return confirm('Deseja excluir este kit?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <ul class="divide-y divide-gray-100 text-xs text-gray-700 mb-4">
                                    @foreach($kit->itens as $ki)
                                        <li class="py-1.5 flex justify-between">
                                            <span>{{ $ki->epi->ss_e_tx_item ?? 'EPI N/D' }}</span>
                                            <span class="font-bold bg-gray-100 px-2 py-0.5 rounded">{{ $ki->ss_ki_nb_quantidade }}x</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <button onclick="selecionarKitParaSacola({{ json_encode($kit) }})" class="w-full py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs rounded-lg border border-amber-300 flex items-center justify-center">
                                <i class="fas fa-cart-plus mr-1"></i> Usar este Kit na Sacola
                            </button>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-12 text-gray-400">
                            <i class="fas fa-briefcase-medical text-4xl mb-2 text-gray-300"></i>
                            <p>Nenhum Kit de EPI cadastrado. Clique no botão acima para criar o primeiro!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ABA 5: FICHAS INDIVIDUAIS & HISTÓRICO DE ENTREGAS -->
            <div id="tab-content-fichas" class="aba-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Lista de Colaboradores para Emissão de Ficha -->
                    <div class="lg:col-span-1 bg-white p-5 rounded-xl border border-gray-200">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-id-card text-emerald-700 mr-2"></i> Emitir Ficha Individual
                        </h3>

                        <input type="text" id="input-filtro-colaborador" onkeyup="filtrarListaColaboradores()" placeholder="Buscar colaborador por nome..." class="w-full text-xs border-gray-300 rounded-lg shadow-sm mb-3">

                        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100" id="lista-colaboradores-fichas">
                            @foreach($colaboradores as $c)
                                <div class="py-2.5 flex items-center justify-between hover:bg-gray-50 px-2 rounded colab-item-row">
                                    <div>
                                        <div class="font-bold text-xs text-gray-900 colab-nome">{{ $c->ss_c_tx_nome }}</div>
                                        <div class="text-xs text-gray-500">{{ $c->ss_c_tx_cargo ?? 'Funcionário' }} - Mat: {{ $c->ss_c_tx_matricula }}</div>
                                    </div>
                                    <a href="{{ route('epi.ficha', $c->ss_c_nb_id) }}" target="_blank" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-xs font-bold border border-emerald-200">
                                        <i class="fas fa-print mr-1"></i> Ficha
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tabela de Histórico de Entregas Recentes -->
                    <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-gray-200">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-history text-emerald-700 mr-2"></i> Histórico Recente de Entregas (Ativas)
                        </h3>

                        <div class="overflow-x-auto border border-gray-100 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Data</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Colaborador</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">EPI entregue</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Vencimento</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Comprovante</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($entregasRecentes as $ent)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) }}
                                            </td>
                                            <td class="px-3 py-2 font-bold text-gray-900">
                                                {{ $ent->colaborador->ss_c_tx_nome ?? 'N/D' }}
                                            </td>
                                            <td class="px-3 py-2 font-semibold text-emerald-950">
                                                {{ $ent->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                                @if($ent->epi->ss_e_tx_ca)
                                                    <span class="text-gray-400 font-normal">(CA {{ $ent->epi->ss_e_tx_ca }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-gray-800">
                                                {{ $ent->ss_e_nb_quantidade }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-600">
                                                {{ $ent->ss_e_tx_vencimento ? date('d/m/Y', strtotime($ent->ss_e_tx_vencimento)) : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                @if($ent->ss_e_tx_assinatura)
                                                    <button onclick="verAssinatura('{{ $ent->ss_e_tx_assinatura }}')" class="text-emerald-600 font-bold hover:underline">
                                                        <i class="fas fa-signature"></i> Assinatura
                                                    </button>
                                                @elseif($ent->ss_e_tx_foto)
                                                    <a href="{{ $ent->ss_e_tx_foto }}" target="_blank" class="text-blue-600 font-bold hover:underline">
                                                        <i class="fas fa-image"></i> Recibo Foto
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="abrirModalCancelarEntrega({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}')" class="text-rose-600 hover:text-rose-800 font-bold" title="Cancelar / Inativar Entrega">
                                                    <i class="fas fa-ban"></i> Inativar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center text-gray-400">Nenhuma entrega registrada ainda.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ABA 6: CADASTRO DE FILIAIS -->
            <div id="tab-content-filiais" class="aba-content hidden">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            <i class="fas fa-building text-emerald-700 mr-2"></i> Filiais Cadastradas
                        </h3>
                        <p class="text-xs text-gray-500">Gerencie as filiais da empresa para habilitar seleções de filial e controle de estoque localizado.</p>
                    </div>
                    <button type="button" onclick="abrirModalNovaFilial()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                        <i class="fas fa-plus mr-1"></i> + Cadastrar Nova Filial
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID / Código</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nome da Filial</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Cidade / UF</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Matriz Padrao -->
                            <tr class="bg-emerald-50/50">
                                <td class="px-4 py-3 font-bold text-xs text-gray-500">0 (FIXO)</td>
                                <td class="px-4 py-3 font-bold text-emerald-950">Matriz / Sede Principal</td>
                                <td class="px-4 py-3 text-xs text-gray-600">Sede Global</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-xs">Ativo (Padrão)</span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-400">Sistema</td>
                            </tr>
                            @forelse($filiaisCadastradas as $f)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-xs text-gray-700">#{{ $f->ss_f_nb_id }} {{ $f->ss_f_tx_codigo ? "({$f->ss_f_tx_codigo})" : '' }}</td>
                                    <td class="px-4 py-3 font-bold text-gray-900">{{ $f->ss_f_tx_nome }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $f->ss_f_tx_cidade ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($f->ss_f_tx_status === 'ativo')
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-xs">Ativo</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-bold text-xs">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        <button type="button" onclick="editarFilial({{ json_encode($f) }})" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form method="POST" action="{{ route('epi.filiais.toggle', $f->ss_f_nb_id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-amber-600 hover:text-amber-800 font-bold text-xs">
                                                <i class="fas fa-sync-alt"></i> Status
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('epi.filiais.destroy', $f->ss_f_nb_id) }}" class="inline" onsubmit="return confirm('Deseja excluir esta filial?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- MODAL: CADASTRAR / EDITAR EPI NO CATÁLOGO -->
<div id="modal-epi" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full p-6 relative">
        <h3 id="modal-epi-titulo" class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-hard-hat text-emerald-700 mr-2"></i> Cadastrar Novo EPI
        </h3>

        <form method="POST" action="{{ route('epi.catalogo.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="epi-form-id" name="ss_e_nb_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Grupo *</label>
                    <input type="text" id="epi-form-grupo" name="ss_e_tx_grupo" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: PROTEÇÃO DA CABEÇA">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Subgrupo</label>
                    <input type="text" id="epi-form-subgrupo" name="ss_e_tx_subgrupo" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Capacetes">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome do Item *</label>
                    <input type="text" id="epi-form-item" name="ss_e_tx_item" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Capacete de Segurança Com Carneira">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nº CA (Certificado)</label>
                    <input type="text" id="epi-form-ca" name="ss_e_tx_ca" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: 12345">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Validade do CA</label>
                    <input type="date" id="epi-form-validade-ca" name="ss_e_tx_validade_ca" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Vida Útil (em dias)</label>
                    <input type="number" id="epi-form-vida-util" name="ss_e_nb_vida_util_dias" value="365" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fabricante / Marca</label>
                    <input type="text" id="epi-form-fabricante" name="ss_e_tx_fabricante" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição</label>
                    <textarea id="epi-form-descricao" name="ss_e_tx_descricao" rows="2" class="w-full text-xs border-gray-300 rounded-lg shadow-sm"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="fecharModalEpi()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-700 text-white text-xs font-bold rounded-lg hover:bg-emerald-800 shadow">Salvar EPI</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: IMPORTAR CSV -->
<div id="modal-import-csv" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-file-csv text-blue-600 mr-2"></i> Importar EPIs em Massa (CSV)
        </h3>
        <p class="text-xs text-gray-500 mb-4">Envie um arquivo CSV separado por ponto e vírgula (;) ou vírgula (,). É recomendado usar nosso modelo CSV pré-formatado.</p>

        <form method="POST" action="{{ route('epi.import-csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <input type="file" name="arquivo_csv" accept=".csv, .txt" required class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalImportCsv()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-700 text-white text-xs font-bold rounded-lg hover:bg-blue-800 shadow">Iniciar Importação</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR ENTRADA NO ESTOQUE -->
<div id="modal-estoque-entrada" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-boxes text-amber-600 mr-2"></i> Registrar Movimentação de Estoque
        </h3>

        <form method="POST" action="{{ route('epi.estoque.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Filial / Sede *</label>
                    <select name="ss_e_nb_empresa_id" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        @foreach($filiais as $fId => $fNome)
                            <option value="{{ $fId }}">{{ $fNome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">EPI do Catálogo *</label>
                    <select name="ss_e_nb_epi_id" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        @foreach($episCatalogo as $e)
                            <option value="{{ $e->ss_e_nb_id }}">{{ $e->ss_e_tx_grupo }} - {{ $e->ss_e_tx_item }} (CA: {{ $e->ss_e_tx_ca ?? 'N/D' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de Movimento</label>
                    <select name="ss_e_tx_tipo" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="entrada">Entrada / Compra</option>
                        <option value="saida">Saída / Baixa</option>
                        <option value="substituicao">Substituição</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Quantidade *</label>
                    <input type="number" name="ss_e_nb_quantidade" min="1" value="1" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valor Unitário (R$)</label>
                    <input type="number" step="0.01" name="ss_e_db_valor_unitario" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Chave / Nº Nota Fiscal</label>
                    <input type="text" name="ss_e_tx_chave_nf" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fornecedor</label>
                    <input type="text" name="ss_e_tx_fornecedor" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Foto da NF / Comprovante</label>
                    <input type="file" name="ss_e_tx_foto" accept="image/*" class="w-full text-xs text-gray-500">
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalEstoque()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-700 text-white text-xs font-bold rounded-lg hover:bg-emerald-800 shadow">Registrar Lançamento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CADASTRAR NOVO KIT DE EPI -->
<div id="modal-kit-novo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-briefcase-medical text-amber-600 mr-2"></i> Criar Kit de Entrega Rápida
        </h3>

        <form method="POST" action="{{ route('epi.kits.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome do Kit *</label>
                <input type="text" name="ss_k_tx_nome" required placeholder="Ex: Kit Construção Civil / Kit Eletricista" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="mb-3 flex justify-between items-center">
                <label class="text-xs font-bold text-gray-700 uppercase">Itens Integrantes do Kit:</label>
                <button type="button" onclick="adicionarLinhaItemKit()" class="text-xs text-emerald-700 hover:text-emerald-900 font-bold">+ Adicionar Item</button>
            </div>

            <div id="container-itens-kit" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <div class="flex space-x-2 item-kit-row">
                    <select name="itens[0][epi_id]" required class="flex-1 text-xs border-gray-300 rounded-lg shadow-sm">
                        @foreach($episCatalogo as $e)
                            <option value="{{ $e->ss_e_nb_id }}">{{ $e->ss_e_tx_item }} (CA: {{ $e->ss_e_tx_ca ?? 'N/D' }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="itens[0][quantidade]" min="1" value="1" required class="w-20 text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Qtd">
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 p-1"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalNovoKit()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 shadow">Salvar Kit</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: INATIVAR / CANCELAR ENTREGA COM ESTORNO (Regra 6) -->
<div id="modal-cancelar-entrega" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-rose-700 mb-2 flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i> Inativar / Cancelar Entrega
        </h3>
        <p id="desc-cancelar-entrega" class="text-xs text-gray-600 mb-4"></p>

        <form id="form-cancelar-entrega" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Justificativa de Exclusão (Obrigatória) *</label>
                <textarea name="ss_e_tx_justificativa_exclusao" required rows="3" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Informe o motivo do cancelamento..."></textarea>
            </div>

            <div class="mb-4 p-3 bg-amber-50 rounded-lg border border-amber-200 flex items-center">
                <input type="checkbox" id="estornar_estoque" name="estornar_estoque" value="1" checked class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                <label for="estornar_estoque" class="ml-2 text-xs font-bold text-amber-900">
                    Estornar item para o estoque da filial (Gera entrada)
                </label>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalCancelarEntrega()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-rose-700 text-white text-xs font-bold rounded-lg hover:bg-rose-800 shadow">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CADASTRAR / EDITAR FILIAL -->
<div id="modal-filial" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 id="modal-filial-titulo" class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-building text-emerald-700 mr-2"></i> Cadastrar Nova Filial
        </h3>

        <form method="POST" action="{{ route('epi.filiais.store') }}">
            @csrf
            <input type="hidden" id="filial-form-id" name="ss_f_nb_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome da Filial *</label>
                    <input type="text" id="filial-form-nome" name="ss_f_tx_nome" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Filial 01 - Centro">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código / Sigla</label>
                    <input type="text" id="filial-form-codigo" name="ss_f_tx_codigo" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: FIL-01">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cidade / UF</label>
                    <input type="text" id="filial-form-cidade" name="ss_f_tx_cidade" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: São Paulo / SP">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status</label>
                    <select id="filial-form-status" name="ss_f_tx_status" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalFilial()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg cursor-pointer">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-emerald-700 text-white text-xs font-bold rounded-lg hover:bg-emerald-800 shadow cursor-pointer">Salvar Filial</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Função global de troca de abas
    window.mudarAba = function(nomeAba) {
        var abas = ['sacola', 'catalogo', 'estoque', 'kits', 'fichas', 'filiais'];
        abas.forEach(function(aba) {
            var content = document.getElementById('tab-content-' + aba);
            var btn = document.getElementById('tab-btn-' + aba);
            if (content) {
                if (aba === nomeAba) {
                    content.classList.remove('hidden');
                    content.style.setProperty('display', 'block', 'important');
                } else {
                    content.classList.add('hidden');
                    content.style.setProperty('display', 'none', 'important');
                }
            }
            if (btn) {
                if (aba === nomeAba) {
                    btn.classList.add('active');
                    btn.style.borderBottomColor = '#F28C2B';
                    btn.style.backgroundColor = 'rgba(21, 59, 46, 0.05)';
                    btn.style.color = '#153B2E';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderBottomColor = 'transparent';
                    btn.style.backgroundColor = 'transparent';
                    btn.style.color = '#4b5563';
                }
            }
        });
    };

    // Variáveis Globais de Estado
    let filialGlobalId = {{ $filialSelecionada }};
    let matrizEstoqueApi = [];
    let sacolaItens = [];
    let kitsLista = @json($kits);
    let episCatalogoLista = @json($episCatalogo);

    document.addEventListener('DOMContentLoaded', function () {
        carregarEstoqueDisponivelAPI();
    });

    function atualizarFilialGlobal(filialId) {
        filialGlobalId = filialId;
        carregarEstoqueDisponivelAPI();
    }

    // Carregar saldos via API em tempo real
    function carregarEstoqueDisponivelAPI() {
        const elChk = document.getElementById('chk-ocultar-esgotados');
        const ocultarEsgotados = (elChk && elChk.checked) ? 1 : 0;
        const filialId = typeof filialGlobalId !== 'undefined' ? filialGlobalId : 0;

        fetch(`{{ route('epi.estoque-disponivel') }}?filial_id=${filialId}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.status === 'success') {
                    matrizEstoqueApi = data.data;
                    renderizarOptionsSelectEpi(ocultarEsgotados);
                }
            })
            .catch(err => console.error('Erro ao carregar estoque API:', err));
    }

    // Renderizar Dropdown de EPIs com Estilos da Regra 1
    function renderizarOptionsSelectEpi(ocultarEsgotados) {
        const select = document.getElementById('sacola-epi-select');
        if (!select || !Array.isArray(matrizEstoqueApi) || matrizEstoqueApi.length === 0) return;

        select.innerHTML = '<option value="">-- Selecione o EPI --</option>';

        matrizEstoqueApi.forEach(epi => {
            // Exibir apenas itens que possuem saldo positivo em estoque (local ou em outra filial)
            if (epi.saldo_rede <= 0) {
                return;
            }

            const option = document.createElement('option');
            option.value = epi.id;

            let textoSaldo = `(Saldo local: ${epi.saldo_atual})`;
            if (epi.disponibilidade === 'externo') {
                textoSaldo = `(Sem saldo nesta filial, disponível na rede: ${epi.saldo_rede})`;
                option.style.color = '#d97706';
                option.style.fontWeight = 'bold';
                option.style.fontStyle = 'italic';
            } else {
                option.style.fontWeight = 'bold';
                option.style.color = '#065f46';
            }

            option.textContent = `${epi.grupo} - ${epi.item} ${epi.ca ? '[CA: ' + epi.ca + ']' : ''} ${textoSaldo}`;
            select.appendChild(option);
        });
    }

    let driverCanvases = {};

    // Adicionar EPI Individual à Sacola
    window.adicionarEpiSacola = function() {
        const select = document.getElementById('sacola-epi-select');
        const epiId = parseInt(select ? select.value : 0);
        const qtd = parseInt(document.getElementById('sacola-epi-qtd').value) || 1;

        if (!epiId) {
            Swal.fire('Atenção', 'Selecione um EPI da lista!', 'warning');
            return;
        }

        let epiInfo = null;

        if (Array.isArray(matrizEstoqueApi) && matrizEstoqueApi.length > 0) {
            epiInfo = matrizEstoqueApi.find(e => parseInt(e.id) === epiId);
        }

        if (!epiInfo && typeof episCatalogoLista !== 'undefined' && Array.isArray(episCatalogoLista)) {
            const rawEpi = episCatalogoLista.find(e => parseInt(e.ss_e_nb_id) === epiId);
            if (rawEpi) {
                epiInfo = {
                    id: rawEpi.ss_e_nb_id,
                    grupo: rawEpi.ss_e_tx_grupo || '',
                    subgrupo: rawEpi.ss_e_tx_subgrupo || '',
                    item: rawEpi.ss_e_tx_item || '',
                    ca: rawEpi.ss_e_tx_ca || '',
                    vida_util_dias: parseInt(rawEpi.ss_e_nb_vida_util_dias) || 0,
                    saldo_atual: 99,
                    saldo_rede: 99,
                    disponibilidade: 'local',
                    outras_filiais: []
                };
            }
        }

        if (!epiInfo) {
            Swal.fire('Atenção', 'EPI selecionado não foi encontrado!', 'warning');
            return;
        }

        window.inserirItemNaSacola(epiInfo, qtd);
    };

    // Adicionar Kit Completo à Sacola
    window.adicionarKitSacola = function() {
        const selectKit = document.getElementById('sacola-kit-select');
        const kitId = parseInt(selectKit ? selectKit.value : 0);

        if (!kitId) {
            Swal.fire('Atenção', 'Selecione um Kit para adicionar!', 'warning');
            return;
        }

        const kitObj = kitsLista.find(k => parseInt(k.ss_k_nb_id) === kitId);
        if (!kitObj || !kitObj.itens || kitObj.itens.length === 0) {
            Swal.fire('Atenção', 'O Kit selecionado não possui itens!', 'warning');
            return;
        }

        let adicionados = 0;
        let ignoradosSemSaldo = [];

        kitObj.itens.forEach(ki => {
            const kiEpiId = parseInt(ki.ss_ki_nb_epi_id);
            let epiInfo = null;

            if (Array.isArray(matrizEstoqueApi) && matrizEstoqueApi.length > 0) {
                epiInfo = matrizEstoqueApi.find(e => parseInt(e.id) === kiEpiId);
            }

            if (!epiInfo && typeof episCatalogoLista !== 'undefined' && Array.isArray(episCatalogoLista)) {
                const rawEpi = episCatalogoLista.find(e => parseInt(e.ss_e_nb_id) === kiEpiId);
                if (rawEpi) {
                    epiInfo = {
                        id: rawEpi.ss_e_nb_id,
                        grupo: rawEpi.ss_e_tx_grupo || '',
                        subgrupo: rawEpi.ss_e_tx_subgrupo || '',
                        item: rawEpi.ss_e_tx_item || '',
                        ca: rawEpi.ss_e_tx_ca || '',
                        vida_util_dias: parseInt(rawEpi.ss_e_nb_vida_util_dias) || 0,
                        saldo_atual: 0,
                        saldo_rede: 0,
                        disponibilidade: 'esgotado',
                        outras_filiais: []
                    };
                }
            }

            // Regra: Apenas adicionar à sacola se o item do kit possuir saldo positivo
            if (epiInfo && epiInfo.saldo_rede > 0) {
                window.inserirItemNaSacola(epiInfo, parseInt(ki.ss_ki_nb_quantidade) || 1, false);
                adicionados++;
            } else {
                const itemNome = epiInfo ? epiInfo.item : ('EPI #' + kiEpiId);
                ignoradosSemSaldo.push(itemNome);
            }
        });

        if (adicionados > 0) {
            let msgToast = `Kit "${kitObj.ss_k_tx_nome}" (${adicionados} item(ns)) adicionado!`;
            if (ignoradosSemSaldo.length > 0) {
                msgToast += ` (${ignoradosSemSaldo.length} sem saldo omitido(s))`;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: msgToast,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kit sem Saldo em Estoque',
                    text: `Nenhum item do Kit "${kitObj.ss_k_tx_nome}" possui saldo positivo em estoque no momento!`,
                    confirmButtonColor: '#065f46'
                });
            } else {
                alert(`Nenhum item do Kit "${kitObj.ss_k_tx_nome}" possui saldo positivo em estoque!`);
            }
        }
    };

    window.selecionarKitParaSacola = function(kitObj) {
        if (typeof window.mudarAba === 'function') window.mudarAba('sacola');
        const selectKit = document.getElementById('sacola-kit-select');
        if (selectKit) selectKit.value = kitObj.ss_k_nb_id;
        window.adicionarKitSacola();
    };

    window.inserirItemNaSacola = function(epiInfo, qtd, notificar = true) {
        const colabSelect = document.getElementById('sacola-colaborador-id');
        let colaboradorId = parseInt(colabSelect ? colabSelect.value : 0);

        // Se nenhum colaborador estiver selecionado, auto-selecionar o primeiro elegível
        if (!colaboradorId && colabSelect && colabSelect.options.length > 1) {
            for (let i = 1; i < colabSelect.options.length; i++) {
                if (colabSelect.options[i].value) {
                    colabSelect.selectedIndex = i;
                    colaboradorId = parseInt(colabSelect.options[i].value);
                    break;
                }
            }
        }

        if (!colaboradorId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhum Colaborador Cadastrado',
                    text: 'Não existem colaboradores elegíveis cadastrados no sistema para receber EPIs.',
                    confirmButtonColor: '#065f46'
                });
            } else {
                alert('Nenhum colaborador elegível cadastrado.');
            }
            return false;
        }

        const colabOption = colabSelect.options[colabSelect.selectedIndex];
        const colaboradorNome = colabOption ? colabOption.text.split('(')[0].trim() : 'Colaborador #' + colaboradorId;

        const dataEntregaVal = (document.getElementById('sacola-data-entrega') && document.getElementById('sacola-data-entrega').value) ? document.getElementById('sacola-data-entrega').value : new Date().toISOString().split('T')[0];
        let vencimentoFormatado = '-';
        if (epiInfo.vida_util_dias > 0) {
            const dt = new Date(dataEntregaVal);
            dt.setDate(dt.getDate() + parseInt(epiInfo.vida_util_dias));
            vencimentoFormatado = dt.toLocaleDateString('pt-BR');
        }

        let filialOrigem = filialGlobalId;
        let precisaTransferencia = false;
        let filiaisComSaldo = Array.isArray(epiInfo.outras_filiais) ? epiInfo.outras_filiais : [];

        if (epiInfo.saldo_atual < qtd && filiaisComSaldo.length > 0) {
            precisaTransferencia = true;
            filialOrigem = filiaisComSaldo[0].filial_id;
        }

        const itemSacola = {
            unique_id: Date.now() + Math.random(),
            colaborador_id: colaboradorId,
            colaborador_nome: colaboradorNome,
            epi_id: epiInfo.id,
            nome: epiInfo.item || epiInfo.ss_e_tx_item || 'EPI',
            grupo: epiInfo.grupo || epiInfo.ss_e_tx_grupo || '',
            ca: epiInfo.ca || epiInfo.ss_e_tx_ca || '-',
            quantidade: qtd,
            vencimento_previsto: vencimentoFormatado,
            precisa_transferencia: precisaTransferencia,
            filial_origem: filialOrigem,
            outras_filiais: filiaisComSaldo
        };

        sacolaItens.push(itemSacola);
        window.renderizarTabelaSacola();

        if (notificar) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Item "${itemSacola.nome}" adicionado à sacola de ${colaboradorNome}!`,
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }
        return true;
    };

    window.renderizarTabelaSacola = function() {
        const container = document.getElementById('container-sacola-motoristas');
        const btnGlobal = document.getElementById('container-btn-global-sacola');

        if (!container) return;

        if (!Array.isArray(sacolaItens) || sacolaItens.length === 0) {
            container.innerHTML = `
                <div class="bg-white p-8 text-center border border-gray-200 rounded-xl text-gray-400">
                    <i class="fas fa-shopping-basket text-4xl mb-3 text-gray-300"></i>
                    <p class="font-medium text-sm">Sua sacola de entregas está vazia.</p>
                    <p class="text-xs text-gray-400 mt-1">Selecione o motorista/colaborador e adicione itens ou kits à esquerda!</p>
                </div>
            `;
            if (btnGlobal) btnGlobal.classList.add('hidden');
            return;
        }

        if (btnGlobal) btnGlobal.classList.remove('hidden');

        // Agrupar itens por colaborador_id
        const gruposPorMotorista = {};
        sacolaItens.forEach(item => {
            const cId = item.colaborador_id || 0;
            if (!gruposPorMotorista[cId]) {
                gruposPorMotorista[cId] = {
                    colaborador_id: cId,
                    colaborador_nome: item.colaborador_nome || ('Colaborador #' + cId),
                    itens: []
                };
            }
            gruposPorMotorista[cId].itens.push(item);
        });

        container.innerHTML = '';
        driverCanvases = {};

        Object.keys(gruposPorMotorista).forEach(colabId => {
            const grupo = gruposPorMotorista[colabId];
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl border border-gray-200 shadow-md p-5 relative space-y-4';

            let rowsHtml = '';
            grupo.itens.forEach(item => {
                const itemIndexInGlobal = sacolaItens.indexOf(item);
                let alertaOrigemHtml = `<span class="text-xs text-emerald-700 font-semibold"><i class="fas fa-check-circle mr-1"></i> Filial Local</span>`;
                if (item.precisa_transferencia) {
                    const outrasArr = Array.isArray(item.outras_filiais) ? item.outras_filiais : [];
                    let optionsFiliais = outrasArr.map(f => `<option value="${f.filial_id}" ${f.filial_id == item.filial_origem ? 'selected' : ''}>${f.filial_nome} (Saldo: ${f.saldo})</option>`).join('');
                    alertaOrigemHtml = `
                        <div class="p-1.5 bg-rose-50 border border-rose-200 rounded text-xs">
                            <span class="text-rose-700 font-bold block mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Sem saldo local! Importar de:
                            </span>
                            <select onchange="window.alterarFilialOrigemSacola(${itemIndexInGlobal}, this.value)" class="text-xs border-rose-300 rounded p-1 text-rose-900 bg-white font-bold">
                                ${optionsFiliais}
                            </select>
                        </div>
                    `;
                }

                rowsHtml += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-900">
                            ${item.nome}
                            <div class="text-xs text-gray-400 font-normal">${item.grupo}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-semibold">${item.ca}</td>
                        <td class="px-4 py-3 text-center font-bold">
                            <input type="number" min="1" value="${item.quantidade}" onchange="window.atualizarQtdSacola(${itemIndexInGlobal}, this.value)" class="w-16 text-center text-xs border-gray-300 rounded">
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-700 font-semibold">${item.vencimento_previsto}</td>
                        <td class="px-4 py-3">${alertaOrigemHtml}</td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="window.removerItemSacola(${itemIndexInGlobal})" class="text-rose-500 hover:text-rose-700 text-xs font-bold p-1">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            card.innerHTML = `
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-9 h-9 rounded-full bg-emerald-900 text-amber-400 font-bold flex items-center justify-center text-sm shadow">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm">${grupo.colaborador_nome}</h4>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full">${grupo.itens.length} item(ns) na sacola</span>
                        </div>
                    </div>
                    <button type="button" onclick="window.removerMotoristaDaSacola(${colabId})" class="text-xs text-rose-600 hover:text-rose-800 font-bold border border-rose-200 bg-rose-50 px-3 py-1 rounded-lg">
                        <i class="fas fa-user-minus mr-1"></i> Remover Motorista
                    </button>
                </div>

                <div class="overflow-x-auto no-transform border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-bold text-gray-500">EPI</th>
                                <th class="px-4 py-2 text-center font-bold text-gray-500">CA</th>
                                <th class="px-4 py-2 text-center font-bold text-gray-500">Qtd</th>
                                <th class="px-4 py-2 text-center font-bold text-gray-500">Vencimento</th>
                                <th class="px-4 py-2 text-left font-bold text-gray-500">Origem / Transferência</th>
                                <th class="px-4 py-2 text-center font-bold text-gray-500">Remover</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>

                <!-- Assinatura Digital do Motorista -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-gray-700">Assinatura Digital no Canvas:</label>
                                <button type="button" onclick="window.limparCanvasMotorista(${colabId})" class="text-xs text-amber-600 hover:text-amber-800 font-bold">
                                    <i class="fas fa-eraser mr-1"></i> Limpar Assinatura
                                </button>
                            </div>
                            <div class="signature-container bg-white border border-gray-300 rounded-lg shadow-inner">
                                <canvas id="signature-canvas-${colabId}" height="100" class="w-full h-24 rounded-lg cursor-crosshair"></canvas>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-700 block mb-1">OU Upload de Recibo Físico Assinado:</label>
                            <input type="file" id="foto-recibo-${colabId}" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                            
                            <div class="mt-2">
                                <label class="text-xs font-bold text-gray-700 block mb-1">Observação para este motorista:</label>
                                <input type="text" id="obs-motorista-${colabId}" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Entregue em mãos...">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-200 flex justify-end">
                        <button type="button" onclick="window.salvarEntregaMotoristaIndividual(${colabId})" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow-md flex items-center transition cursor-pointer">
                            <i class="fas fa-check-circle mr-1.5 text-amber-400 text-sm"></i> Salvar Entrega Deste Colaborador
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(card);

            setTimeout(() => {
                inicializarCanvasMotorista(colabId);
            }, 50);
        });
    };

    function inicializarCanvasMotorista(colabId) {
        const cvs = document.getElementById(`signature-canvas-${colabId}`);
        if (!cvs) return;

        const rect = cvs.getBoundingClientRect();
        cvs.width = rect.width || 350;
        cvs.height = rect.height || 100;

        const ctxObj = cvs.getContext('2d');
        ctxObj.lineWidth = 2;
        ctxObj.lineCap = 'round';
        ctxObj.strokeStyle = '#000000';

        driverCanvases[colabId] = {
            canvas: cvs,
            ctx: ctxObj,
            isDrawing: false
        };

        function getPos(e) {
            const r = cvs.getBoundingClientRect();
            let clientX = e.clientX;
            let clientY = e.clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }
            return {
                x: clientX - r.left,
                y: clientY - r.top
            };
        }

        function startDraw(e) {
            if (driverCanvases[colabId]) {
                driverCanvases[colabId].isDrawing = true;
                const pos = getPos(e);
                ctxObj.beginPath();
                ctxObj.moveTo(pos.x, pos.y);
            }
            e.preventDefault();
        }

        function draw(e) {
            if (!driverCanvases[colabId] || !driverCanvases[colabId].isDrawing) return;
            const pos = getPos(e);
            ctxObj.lineTo(pos.x, pos.y);
            ctxObj.stroke();
            e.preventDefault();
        }

        function stopDraw() {
            if (driverCanvases[colabId]) {
                driverCanvases[colabId].isDrawing = false;
            }
        }

        cvs.addEventListener('mousedown', startDraw);
        cvs.addEventListener('mousemove', draw);
        cvs.addEventListener('mouseup', stopDraw);

        cvs.addEventListener('touchstart', startDraw);
        cvs.addEventListener('touchmove', draw);
        cvs.addEventListener('touchend', stopDraw);
    }

    window.limparCanvasMotorista = function(colabId) {
        if (driverCanvases[colabId]) {
            const { canvas, ctx } = driverCanvases[colabId];
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    };

    function canvasMotoristaEstaVazio(colabId) {
        if (!driverCanvases[colabId]) return true;
        const { canvas, ctx } = driverCanvases[colabId];
        const pixelBuffer = new Uint32Array(
            ctx.getImageData(0, 0, canvas.width, canvas.height).data.buffer
        );
        return !pixelBuffer.some(color => color !== 0);
    }

    window.removerMotoristaDaSacola = function(colabId) {
        sacolaItens = sacolaItens.filter(i => parseInt(i.colaborador_id) !== parseInt(colabId));
        window.renderizarTabelaSacola();
    };

    window.atualizarQtdSacola = function(index, novaQtd) {
        if (sacolaItens[index]) {
            sacolaItens[index].quantidade = parseInt(novaQtd) || 1;
        }
    };

    window.alterarFilialOrigemSacola = function(index, novaFilialId) {
        if (sacolaItens[index]) {
            sacolaItens[index].filial_origem = parseInt(novaFilialId);
        }
    };

    window.removerItemSacola = function(index) {
        sacolaItens.splice(index, 1);
        window.renderizarTabelaSacola();
    };

    window.limparSacola = function() {
        sacolaItens = [];
        window.renderizarTabelaSacola();
    };

    window.salvarEntregaMotoristaIndividual = function(colabId) {
        const itensMotorista = sacolaItens.filter(i => parseInt(i.colaborador_id) === parseInt(colabId));
        if (itensMotorista.length === 0) return;

        const motoristaNome = itensMotorista[0].colaborador_nome;
        const dataEntrega = document.getElementById('sacola-data-entrega').value || new Date().toISOString().split('T')[0];

        let assinaturaBase64 = null;
        if (driverCanvases[colabId] && !canvasMotoristaEstaVazio(colabId)) {
            assinaturaBase64 = driverCanvases[colabId].canvas.toDataURL('image/png');
        }

        const fotoFileInput = document.getElementById(`foto-recibo-${colabId}`);
        const temFoto = fotoFileInput && fotoFileInput.files && fotoFileInput.files.length > 0;

        if (!assinaturaBase64 && !temFoto) {
            Swal.fire('Atenção', `É obrigatório capturar a Assinatura Digital no Canvas OU anexar a foto do recibo para ${motoristaNome}!`, 'warning');
            return;
        }

        const obsVal = document.getElementById(`obs-motorista-${colabId}`) ? document.getElementById(`obs-motorista-${colabId}`).value : '';

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('ss_e_nb_colaborador_id', colabId);
        formData.append('ss_e_tx_data_entrega', dataEntrega);
        formData.append('ss_e_nb_empresa_id', filialGlobalId);
        if (assinaturaBase64) formData.append('ss_e_tx_assinatura', assinaturaBase64);
        if (temFoto) formData.append('ss_e_tx_foto', fotoFileInput.files[0]);
        formData.append('ss_e_tx_observacao', obsVal);

        itensMotorista.forEach((item, idx) => {
            formData.append(`itens[${idx}][epi_id]`, item.epi_id);
            formData.append(`itens[${idx}][quantidade]`, item.quantidade);
            formData.append(`itens[${idx}][empresa_origem_id]`, item.filial_origem);
        });

        fetch('{{ route("epi.entrega.store") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Entrega de ${motoristaNome} salva com sucesso!`,
                    showConfirmButton: false,
                    timer: 3500
                });

                sacolaItens = sacolaItens.filter(i => parseInt(i.colaborador_id) !== parseInt(colabId));
                window.renderizarTabelaSacola();
                carregarEstoqueDisponivelAPI();
            } else {
                Swal.fire('Erro!', 'Falha ao registrar a entrega do motorista.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro!', 'Ocorreu uma falha na requisição.', 'error');
        });
    };

    window.confirmarTodasEntregasLote = function() {
        if (!sacolaItens || sacolaItens.length === 0) return;

        const colabIds = [...new Set(sacolaItens.map(i => parseInt(i.colaborador_id)))];
        const dataEntrega = document.getElementById('sacola-data-entrega').value || new Date().toISOString().split('T')[0];

        for (let id of colabIds) {
            let temAssinatura = driverCanvases[id] && !canvasMotoristaEstaVazio(id);
            let fotoInput = document.getElementById(`foto-recibo-${id}`);
            let temFoto = fotoInput && fotoInput.files && fotoInput.files.length > 0;

            if (!temAssinatura && !temFoto) {
                const item = sacolaItens.find(i => parseInt(i.colaborador_id) === id);
                const nome = item ? item.colaborador_nome : '#' + id;
                Swal.fire('Atenção', `Assinatura Digital ou Foto do Recibo é obrigatória para o motorista: ${nome}!`, 'warning');
                return;
            }
        }

        Swal.fire({
            title: 'Confirmar TODAS as Entregas em Lote?',
            text: `Serão processadas as entregas para ${colabIds.length} motorista(s) e total de ${sacolaItens.length} item(ns) com baixa de estoque!`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#065f46',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, Finalizar Todas!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const bulkPayload = {
                    _token: '{{ csrf_token() }}',
                    ss_e_nb_empresa_id: filialGlobalId,
                    entregas: []
                };

                colabIds.forEach(id => {
                    const itensMotorista = sacolaItens.filter(i => parseInt(i.colaborador_id) === id);
                    let sigBase64 = (driverCanvases[id] && !canvasMotoristaEstaVazio(id)) ? driverCanvases[id].canvas.toDataURL('image/png') : null;
                    let obsVal = document.getElementById(`obs-motorista-${id}`) ? document.getElementById(`obs-motorista-${id}`).value : '';

                    bulkPayload.entregas.push({
                        ss_e_nb_colaborador_id: id,
                        ss_e_tx_data_entrega: dataEntrega,
                        ss_e_nb_empresa_id: filialGlobalId,
                        ss_e_tx_assinatura: sigBase64,
                        ss_e_tx_observacao: obsVal,
                        itens: itensMotorista.map(i => ({
                            epi_id: i.epi_id,
                            quantidade: i.quantidade,
                            empresa_origem_id: i.filial_origem
                        }))
                    });
                });

                fetch('{{ route("epi.entrega.store") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(bulkPayload)
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.status === 'success') {
                        Swal.fire('Sucesso!', resData.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Erro!', 'Ocorreu uma falha ao registrar as entregas em lote.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Erro!', 'Ocorreu uma falha na requisição.', 'error');
                });
            }
        });
    };

    window.abrirModalNovoEpi = function() {
        var modal = document.getElementById('modal-epi');
        if (!modal) return;
        var elTitulo = document.getElementById('modal-epi-titulo');
        if (elTitulo) elTitulo.textContent = 'Cadastrar Novo EPI';
        if (document.getElementById('epi-form-id')) document.getElementById('epi-form-id').value = '';
        if (document.getElementById('epi-form-grupo')) document.getElementById('epi-form-grupo').value = '';
        if (document.getElementById('epi-form-subgrupo')) document.getElementById('epi-form-subgrupo').value = '';
        if (document.getElementById('epi-form-item')) document.getElementById('epi-form-item').value = '';
        if (document.getElementById('epi-form-ca')) document.getElementById('epi-form-ca').value = '';
        if (document.getElementById('epi-form-validade-ca')) document.getElementById('epi-form-validade-ca').value = '';
        if (document.getElementById('epi-form-vida-util')) document.getElementById('epi-form-vida-util').value = '365';
        if (document.getElementById('epi-form-fabricante')) document.getElementById('epi-form-fabricante').value = '';
        if (document.getElementById('epi-form-descricao')) document.getElementById('epi-form-descricao').value = '';

        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.editarEpi = function(epi) {
        var modal = document.getElementById('modal-epi');
        if (!modal) return;
        document.getElementById('modal-epi-titulo').textContent = 'Editar EPI #' + epi.ss_e_nb_id;
        document.getElementById('epi-form-id').value = epi.ss_e_nb_id;
        document.getElementById('epi-form-grupo').value = epi.ss_e_tx_grupo || '';
        document.getElementById('epi-form-subgrupo').value = epi.ss_e_tx_subgrupo || '';
        document.getElementById('epi-form-item').value = epi.ss_e_tx_item || '';
        document.getElementById('epi-form-ca').value = epi.ss_e_tx_ca || '';
        document.getElementById('epi-form-validade-ca').value = epi.ss_e_tx_validade_ca || '';
        document.getElementById('epi-form-vida-util').value = epi.ss_e_nb_vida_util_dias || 0;
        document.getElementById('epi-form-fabricante').value = epi.ss_e_tx_fabricante || '';
        document.getElementById('epi-form-descricao').value = epi.ss_e_tx_descricao || '';
        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.fecharModalEpi = function() {
        var modal = document.getElementById('modal-epi');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    window.abrirModalImportCsv = function() {
        var modal = document.getElementById('modal-import-csv');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
        }
    };

    window.fecharModalImportCsv = function() {
        var modal = document.getElementById('modal-import-csv');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    window.abrirModalNovaEntradaEstoque = function() {
        var modal = document.getElementById('modal-estoque-entrada');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
        }
    };

    window.fecharModalEstoque = function() {
        var modal = document.getElementById('modal-estoque-entrada');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    window.abrirModalNovoKit = function() {
        var modal = document.getElementById('modal-kit-novo');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
        }
    };

    window.fecharModalNovoKit = function() {
        var modal = document.getElementById('modal-kit-novo');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    window.adicionarLinhaItemKit = function() {
        const container = document.getElementById('container-itens-kit');
        const idx = container.children.length;
        const div = document.createElement('div');
        div.className = 'flex space-x-2 item-kit-row mt-2';

        let options = episCatalogoLista.map(e => `<option value="${e.ss_e_nb_id}">${e.ss_e_tx_item} (CA: ${e.ss_e_tx_ca || 'N/D'})</option>`).join('');
        div.innerHTML = `
            <select name="itens[${idx}][epi_id]" required class="flex-1 text-xs border-gray-300 rounded-lg shadow-sm">
                ${options}
            </select>
            <input type="number" name="itens[${idx}][quantidade]" min="1" value="1" required class="w-20 text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Qtd">
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 p-1"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(div);
    };

    // Regra 6: Cancelar/Inativar Entrega com Justificativa e Estorno Opcional
    window.abrirModalCancelarEntrega = function(entregaId, itemNome) {
        var modal = document.getElementById('modal-cancelar-entrega');
        if (modal) {
            document.getElementById('desc-cancelar-entrega').textContent = `Você está inativando a entrega do item: "${itemNome}".`;
            const form = document.getElementById('form-cancelar-entrega');
            form.action = `{{ url('/epi/entrega') }}/${entregaId}/cancelar`;
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
        }
    };

    window.fecharModalCancelarEntrega = function() {
        var modal = document.getElementById('modal-cancelar-entrega');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    function verAssinatura(base64) {
        Swal.fire({
            title: 'Assinatura Digital',
            imageUrl: base64,
            imageAlt: 'Assinatura Digital do Recibo',
            confirmButtonText: 'Fechar'
        });
    }

    window.abrirModalNovaFilial = function() {
        var modal = document.getElementById('modal-filial');
        if (!modal) return;
        var elTitulo = document.getElementById('modal-filial-titulo');
        if (elTitulo) elTitulo.textContent = 'Cadastrar Nova Filial';
        if (document.getElementById('filial-form-id')) document.getElementById('filial-form-id').value = '';
        if (document.getElementById('filial-form-nome')) document.getElementById('filial-form-nome').value = '';
        if (document.getElementById('filial-form-codigo')) document.getElementById('filial-form-codigo').value = '';
        if (document.getElementById('filial-form-cidade')) document.getElementById('filial-form-cidade').value = '';
        if (document.getElementById('filial-form-status')) document.getElementById('filial-form-status').value = 'ativo';

        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.editarFilial = function(f) {
        var modal = document.getElementById('modal-filial');
        if (!modal) return;
        var elTitulo = document.getElementById('modal-filial-titulo');
        if (elTitulo) elTitulo.textContent = 'Editar Filial #' + f.ss_f_nb_id;
        if (document.getElementById('filial-form-id')) document.getElementById('filial-form-id').value = f.ss_f_nb_id;
        if (document.getElementById('filial-form-nome')) document.getElementById('filial-form-nome').value = f.ss_f_tx_nome || '';
        if (document.getElementById('filial-form-codigo')) document.getElementById('filial-form-codigo').value = f.ss_f_tx_codigo || '';
        if (document.getElementById('filial-form-cidade')) document.getElementById('filial-form-cidade').value = f.ss_f_tx_cidade || '';
        if (document.getElementById('filial-form-status')) document.getElementById('filial-form-status').value = f.ss_f_tx_status || 'ativo';

        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.fecharModalFilial = function() {
        var modal = document.getElementById('modal-filial');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };
</script>
@endsection
