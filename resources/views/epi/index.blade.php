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

    @if($errors->any())
        <div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded shadow-sm flex items-start justify-between">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-rose-500 text-xl mr-3 mt-0.5"></i>
                <div>
                    <strong class="block text-sm font-bold mb-1">Erros encontrados:</strong>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
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

    <!-- MONITORAMENTO DE VALIDADE (CA / VIDA ÚTIL / VENCIMENTOS) -->
    <div class="glass-card p-5 mb-6 border-l-4 border-amber-400">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="text-lg font-bold text-gray-900 flex items-center">
                <i class="fas fa-bell text-amber-500 mr-2"></i> Monitoramento de Validade
                <span class="ml-3 text-xs font-semibold text-gray-400 hidden md:inline">CAs, vida útil e vencimentos que precisam de atenção</span>
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-gray-400 font-semibold">Atualizado em {{ date('d/m/Y H:i') }}</span>
                <button type="button" onclick="toggleMonitoramentoValidade()" id="btn-toggle-monitoramento" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-300 text-xs font-bold cursor-pointer transition" title="Ocultar / Exibir painel">
                    <i id="icone-toggle-monitoramento" class="fas fa-chevron-up transition-transform duration-200"></i>
                    <span id="texto-toggle-monitoramento">Ocultar</span>
                </button>
            </div>
        </div>

        <div id="monitoramento-validade-body">
            <!-- KPIs de Validade -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-4">
            <div class="bg-rose-50 p-3 rounded-xl border border-rose-100 text-center">
                <div class="text-2xl font-bold text-rose-700">{{ $caStats['vencidos'] }}</div>
                <div class="text-[10px] font-bold text-rose-600 uppercase mt-0.5">CAs Vencidos</div>
            </div>
            <div class="bg-amber-50 p-3 rounded-xl border border-amber-100 text-center">
                <div class="text-2xl font-bold text-amber-800">{{ $caStats['expirando'] }}</div>
                <div class="text-[10px] font-bold text-amber-700 uppercase mt-0.5">CAs vencem em 30d</div>
            </div>
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                <div class="text-2xl font-bold text-gray-700">{{ $caStats['sem_data'] }}</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase mt-0.5">CAs sem validade</div>
            </div>
            <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 text-center">
                <div class="text-2xl font-bold text-indigo-700">{{ $vidaUtilStats['nao_configurada'] }}</div>
                <div class="text-[10px] font-bold text-indigo-600 uppercase mt-0.5">Vida útil não config.</div>
            </div>
            <div class="bg-rose-50 p-3 rounded-xl border border-rose-100 text-center">
                <div class="text-2xl font-bold text-rose-700">{{ $entregasVencidasCount }}</div>
                <div class="text-[10px] font-bold text-rose-600 uppercase mt-0.5">Entregas vencidas</div>
            </div>
            <div class="bg-amber-50 p-3 rounded-xl border border-amber-100 text-center">
                <div class="text-2xl font-bold text-amber-800">{{ $entregasVencendoCount }}</div>
                <div class="text-[10px] font-bold text-amber-700 uppercase mt-0.5">Entregas vencem em 30d</div>
            </div>
            <div class="bg-violet-50 p-3 rounded-xl border border-violet-100 text-center">
                <div class="text-2xl font-bold text-violet-700">{{ $devolucoesPendentesCount }}</div>
                <div class="text-[10px] font-bold text-violet-600 uppercase mt-0.5">Pendências de inspeção</div>
            </div>
        </div>

        @if($episCaProblema->isEmpty() && $episVidaUtilProblema->isEmpty() && $entregasAlerta->isEmpty())
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-lg text-emerald-700 text-sm font-semibold">
                <i class="fas fa-check-circle mr-1"></i> Nenhum alerta: todos os CAs estão em dia, a vida útil está configurada e não há entregas vencidas ou vencendo.
            </div>
        @else
            <div class="space-y-4">
                <!-- Tabela: Itens do catálogo com CA crítico -->
                @if($episCaProblema->isNotEmpty())
                    <div class="border border-rose-200 rounded-lg overflow-hidden">
                        <div class="bg-rose-50 px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-bold text-rose-800 uppercase"><i class="fas fa-certificate mr-1"></i> CAs do catálogo com atenção ({{ $episCaProblema->count() }})</span>
                            <span class="text-[10px] text-rose-500 font-semibold">Edite o item para informar a nova data de validade do CA</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Item</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">CA</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Validade do CA</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Situação</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Vida útil</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Funcionários</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($episCaProblema as $epi)
                                        @php [$caLabel, $caCls] = $epi->status_ca_badge; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <div class="font-bold text-gray-900">{{ $epi->ss_e_tx_item }}</div>
                                                <div class="text-[10px] text-gray-500">{{ $epi->ss_e_tx_grupo }}</div>
                                            </td>
                                            <td class="px-3 py-2 text-center font-mono font-bold text-gray-800">{{ $epi->ss_e_tx_ca }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">
                                                {{ $epi->ss_e_tx_validade_ca ? date('d/m/Y', strtotime($epi->ss_e_tx_validade_ca)) : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $caCls }}">{{ $caLabel }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-center {{ $epi->ss_e_nb_vida_util_dias <= 0 ? 'text-rose-600 font-bold' : 'text-gray-600' }}">
                                                {{ $epi->ss_e_nb_vida_util_dias > 0 ? $epi->ss_e_nb_vida_util_dias . ' dias' : 'Não configurada' }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="abrirModalEpiEntregas({{ $epi->ss_e_nb_id }}, '{{ addslashes($epi->ss_e_tx_item ?? '') }}')" class="text-emerald-700 hover:text-emerald-900 font-bold cursor-pointer text-sm" title="Ver funcionários com este EPI">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="editarEpi({{ json_encode($epi) }})" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold cursor-pointer">
                                                    <i class="fas fa-edit mr-1"></i> Editar / Regularizar
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Tabela: Itens com vida útil não configurada -->
                @if($episVidaUtilProblema->isNotEmpty())
                    <div class="border border-indigo-200 rounded-lg overflow-hidden">
                        <div class="bg-indigo-50 px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-800 uppercase"><i class="fas fa-clock mr-1"></i> EPIs com vida útil não configurada ({{ $episVidaUtilProblema->count() }})</span>
                            <span class="text-[10px] text-indigo-500 font-semibold">Defina a validade em dias (ex.: 365) no cadastro do item</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Item</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Grupo</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Vida útil (dias)</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($episVidaUtilProblema as $epi)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 font-bold text-gray-900">{{ $epi->ss_e_tx_item }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ $epi->ss_e_tx_grupo }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded text-[10px] font-bold">Não configurada (0)</span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="editarEpi({{ json_encode($epi) }})" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold cursor-pointer">
                                                    <i class="fas fa-edit mr-1"></i> Editar / Regularizar
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Tabela: Entregas vencidas ou vencendo -->
                @if($entregasAlerta->isNotEmpty())
                    <div class="border border-amber-200 rounded-lg overflow-hidden">
                        <div class="bg-amber-50 px-4 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-800 uppercase"><i class="fas fa-truck mr-1"></i> Entregas vencidas ou vencendo em 30 dias ({{ $entregasAlerta->count() }} exibidas)</span>
                            <span class="text-[10px] text-amber-600 font-semibold">Regularize a data de vencimento da entrega ou realize nova entrega</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Colaborador</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">EPI entregue</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Entrega</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Vencimento</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Situação</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php $hojeCompara = now()->format('Y-m-d'); @endphp
                                    @foreach($entregasAlerta as $ent)
                                        @php $estaVencida = $ent->ss_e_tx_vencimento < $hojeCompara; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 font-bold text-gray-900">{{ $ent->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                            <td class="px-3 py-2 font-semibold text-emerald-950">
                                                {{ $ent->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                                @if($ent->epi && $ent->epi->ss_e_tx_ca)
                                                    <span class="text-gray-400 font-normal">(CA {{ $ent->epi->ss_e_tx_ca }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-gray-800">{{ $ent->ss_e_nb_quantidade }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ $ent->ss_e_tx_vencimento ? date('d/m/Y', strtotime($ent->ss_e_tx_vencimento)) : '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if($estaVencida)
                                                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded text-[10px] font-bold">Vencida</span>
                                                @else
                                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Vence em 30 dias</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="abrirModalVencimentoEntrega({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}', '{{ $ent->ss_e_tx_vencimento ?? '' }}')" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold cursor-pointer">
                                                    <i class="fas fa-calendar-alt mr-1"></i> Editar Vencimento
                                                </button>
                                                <button onclick="abrirModalDevolucao({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}', {{ $ent->colaborador->ss_c_nb_id ?? 'null' }})" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[10px] font-bold cursor-pointer" title="Registrar devolução / saída do EPI">
                                                    <i class="fas fa-undo-alt mr-1"></i> Devolver / Saída
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        </div>
    </div>

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
            <button type="button" onclick="mudarAba('fardamento')" id="tab-btn-fardamento" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-tshirt mr-2"></i> Fardamento
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
            <button type="button" onclick="mudarAba('devolucoes')" id="tab-btn-devolucoes" class="nav-tab-btn px-5 py-3 text-sm flex items-center cursor-pointer">
                <i class="fas fa-undo-alt mr-2"></i> Devoluções & Inspeção
                @if($devolucoesPendentesCount > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 bg-rose-500 text-white text-[10px] font-bold rounded-full">{{ $devolucoesPendentesCount }}</span>
                @endif
            </button>
            <a href="{{ route('epi.gestao-assinaturas') }}" class="nav-tab-btn px-5 py-3 text-sm flex items-center text-emerald-700 hover:text-emerald-900 cursor-pointer">
                <i class="fas fa-file-signature mr-2"></i> Gestão de Assinaturas
            </a>
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

                        <!-- Flag Entrega Retroativa -->
                        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" id="sacola-retroativo" onchange="alternarModoRetroativo()" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                                <span class="ml-2">
                                    <span class="text-xs font-bold text-amber-900">
                                        <i class="fas fa-history mr-1"></i> Entrega Retroativa
                                    </span>
                                    <span class="block text-[11px] text-amber-700 mt-0.5">Apenas atualiza a ficha do funcionário com itens já entregues. Sem validação de saldo e sem baixa no estoque.</span>
                                </span>
                            </label>
                        </div>

                        <!-- Aviso modo retroativo ativo -->
                        <div id="banner-retroativo" class="hidden mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg text-[11px] text-purple-800 font-semibold">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Modo retroativo ativo: todos os EPIs cadastrados estão liberados e nenhuma movimentação de estoque será gerada.
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

                                <!-- Combobox: botão + dropdown com busca interna -->
                                <div class="relative" id="sacola-epi-combobox">
                                    <button type="button" id="sacola-epi-trigger" onclick="toggleDropdownEpi()" class="w-full text-sm border-gray-300 rounded-lg shadow-sm bg-white px-3 py-2 text-left flex items-center justify-between hover:bg-gray-50">
                                        <span id="sacola-epi-trigger-text" class="text-gray-400">-- Selecione o EPI --</span>
                                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                                    </button>

                                    <div id="sacola-epi-dropdown" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-xl">
                                        <div class="p-2 border-b border-gray-200">
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                                    <i class="fas fa-search text-xs"></i>
                                                </span>
                                                <input type="text" id="sacola-epi-busca" oninput="filtrarDropdownEpi()" onkeydown="navegarDropdownEpi(event)" placeholder="Buscar por nome ou CA..." class="w-full text-xs border-gray-300 rounded-md pl-7 pr-2 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                                            </div>
                                        </div>
                                        <div id="sacola-epi-lista" class="max-h-56 overflow-y-auto py-1"></div>
                                    </div>
                                </div>
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
                        <input type="hidden" name="tab" value="catalogo">
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
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Variações</th>
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
                                            @php [$caLabel, $caCls] = $epi->status_ca_badge; @endphp
                                            <span class="font-bold px-2 py-0.5 bg-gray-100 border border-gray-300 rounded">CA: {{ $epi->ss_e_tx_ca }}</span>
                                            <div class="mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $caCls }}" title="Validade: {{ $epi->ss_e_tx_validade_ca ? date('d/m/Y', strtotime($epi->ss_e_tx_validade_ca)) : 'não informada' }}">
                                                    {{ $caLabel }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if(isset($saldosVariacao[$epi->ss_e_nb_id]) && count($saldosVariacao[$epi->ss_e_nb_id]) > 0)
                                            <div class="flex flex-wrap gap-1 justify-center">
                                                @foreach($epi->variacoes->where('ss_ev_tx_status', 'ativo') as $v)
                                                    @php $saldoVar = $saldosVariacao[$epi->ss_e_nb_id][$v->ss_ev_nb_id] ?? 0; @endphp
                                                    <span class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-xs {{ $saldoVar <= 0 ? 'text-gray-400' : 'text-gray-800' }}" title="Saldo: {{ $saldoVar }}">
                                                        {{ $v->ss_ev_tx_nome }} <strong>({{ $saldoVar }})</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-xs" title="Usado como fallback quando a movimentação de entrada não informa a validade do lote. A validade do lote tem prioridade no vencimento das entregas.">
                                        @if($epi->ss_e_nb_vida_util_dias > 0)
                                            {{ $epi->ss_e_nb_vida_util_dias }} dias
                                        @else
                                            <span class="px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded text-[10px] font-bold">Não configurada</span>
                                        @endif
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

            <!-- ABA 3: CONTROLE DE ESTOQUE -->
            <div id="tab-content-estoque" class="aba-content hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            <i class="fas fa-boxes text-amber-600 mr-2"></i> Controle de Estoque
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Os saldos acompanham o seletor de filial no topo da página. Clique em um item para ver a distribuição entre filiais.
                        </p>
                    </div>
                    <button type="button" onclick="abrirModalNovaEntradaEstoque()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                        <i class="fas fa-plus mr-1"></i> + Registrar Lançamento / Entrada
                    </button>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                    <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="fas fa-boxes"></i></div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-gray-400 uppercase leading-tight">Saldo total (rede)</p>
                            <p id="kpi-saldo-rede" class="text-2xl font-black text-gray-900 leading-tight">-</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0"><i class="fas fa-warehouse"></i></div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-gray-400 uppercase leading-tight">Saldo na filial</p>
                            <p id="kpi-saldo-filial" class="text-2xl font-black text-gray-900 leading-tight">-</p>
                            <p id="kpi-filial-nome" class="text-[10px] text-gray-400 truncate">-</p>
                        </div>
                    </div>
                    <button type="button" onclick="setFiltroStatusEstoque('ok')" class="text-left bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3 hover:border-emerald-400 hover:shadow cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="fas fa-check-circle"></i></div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-gray-400 uppercase leading-tight">Itens com saldo</p>
                            <p id="kpi-com-saldo" class="text-2xl font-black text-emerald-700 leading-tight">-</p>
                        </div>
                    </button>
                    <button type="button" onclick="setFiltroStatusEstoque('esgotado')" class="text-left bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3 hover:border-rose-400 hover:shadow cursor-pointer">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold text-gray-400 uppercase leading-tight">Itens esgotados</p>
                            <p id="kpi-esgotados" class="text-2xl font-black text-rose-700 leading-tight">-</p>
                        </div>
                    </button>
                </div>

                <!-- Fluxo dos últimos 14 dias -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <h4 class="text-sm font-bold text-gray-900"><i class="fas fa-chart-bar text-amber-600 mr-2"></i> Fluxo de Entradas x Saídas (últimos 14 dias)</h4>
                        <div class="flex items-center gap-4 text-[11px] font-bold">
                            <span class="flex items-center gap-1 text-emerald-700"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Entradas</span>
                            <span class="flex items-center gap-1 text-rose-700"><span class="w-2.5 h-2.5 rounded-sm bg-rose-400 inline-block"></span> Saídas</span>
                        </div>
                    </div>
                    @php
                        $maxGrafico = max(array_map(fn ($g) => $g['entradas'] + $g['saidas'], $estoqueGrafico)) ?: 1;
                    @endphp
                    <div class="flex items-end gap-1 h-28">
                        @forelse($estoqueGrafico as $g)
                            @php
                                $pctEnt = $g['entradas'] > 0 ? max(4, (int) round($g['entradas'] / $maxGrafico * 100)) : 0;
                                $pctSai = $g['saidas'] > 0 ? max(4, (int) round($g['saidas'] / $maxGrafico * 100)) : 0;
                            @endphp
                            <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ $g['label'] }} — Entradas: {{ $g['entradas'] }} · Saídas: {{ $g['saidas'] }}">
                                <div class="flex items-end gap-0.5 w-full justify-center" style="height: calc(100% - 18px);">
                                    <div class="w-2.5 bg-emerald-500 rounded-t-sm" style="height: {{ $pctEnt }}%;"></div>
                                    <div class="w-2.5 bg-rose-400 rounded-t-sm" style="height: {{ $pctSai }}%;"></div>
                                </div>
                                <div class="text-[9px] text-gray-400 mt-0.5">{{ $g['label'] }}</div>
                            </div>
                        @empty
                            <div class="w-full text-center text-xs text-gray-400 py-8">Sem movimentações de estoque nos últimos 14 dias.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Toolbar de filtros -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" id="filtro-estoque-busca" oninput="filtroEstoque.busca = this.value; renderizarControleEstoque();" placeholder="Buscar item, grupo, CA..." class="pl-8 pr-3 py-2 text-xs border-gray-300 rounded-lg shadow-sm w-56">
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" id="filtro-estoque-todos" onclick="setFiltroStatusEstoque('todos')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-emerald-900 text-white">Todos</button>
                        <button type="button" id="filtro-estoque-ok" onclick="setFiltroStatusEstoque('ok')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Com saldo</button>
                        <button type="button" id="filtro-estoque-baixo" onclick="setFiltroStatusEstoque('baixo')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Baixo estoque (0)</button>
                        <button type="button" id="filtro-estoque-esgotado" onclick="setFiltroStatusEstoque('esgotado')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Esgotados</button>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Limiar baixo:</label>
                        <select id="filtro-estoque-limiar" onchange="filtroEstoque.limiar = parseInt(this.value); setFiltroStatusEstoque(filtroEstoque.status);" class="text-xs border-gray-300 rounded-lg shadow-sm">
                            <option value="2">≤ 2</option>
                            <option value="5" selected>≤ 5</option>
                            <option value="10">≤ 10</option>
                            <option value="20">≤ 20</option>
                        </select>
                        <select id="filtro-estoque-ordem" onchange="filtroEstoque.ordem = this.value; renderizarControleEstoque();" class="text-xs border-gray-300 rounded-lg shadow-sm">
                            <option value="nome">Nome (A-Z)</option>
                            <option value="saldo-asc">Menor saldo</option>
                            <option value="saldo-desc">Maior saldo</option>
                        </select>
                    </div>
                </div>

                <!-- Tabela de Saldos por EPI -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white mb-8">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">EPI</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Variações (saldo)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Saldo total (rede)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Saldo na filial</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="estoque-linhas">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Carregando estoque...</td></tr>
                        </tbody>
                    </table>
                    <div id="estoque-vazio" class="hidden text-center text-gray-400 py-8 text-sm">
                        Nenhum item encontrado com os filtros selecionados.
                    </div>
                    <div class="px-4 py-2 border-t border-gray-100 text-[11px] text-gray-400">
                        <span id="estoque-contagem"></span>
                    </div>
                </div>

                <h3 class="text-base font-bold text-gray-900 mb-1">
                    <i class="fas fa-history text-amber-600 mr-2"></i> Movimentações de Inventário
                </h3>
                <p class="text-xs text-gray-500 mb-3">Filtre por tipo, período e texto. A borda colorida indica o sentido de cada movimentação.</p>

                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <button type="button" id="filtro-mov-todos" onclick="setFiltroTipoMovimentacao('todos')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-emerald-900 text-white">Todas</button>
                    <button type="button" id="filtro-mov-entrada" onclick="setFiltroTipoMovimentacao('entrada')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Entradas</button>
                    <button type="button" id="filtro-mov-saida" onclick="setFiltroTipoMovimentacao('saida')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Saídas</button>
                    <button type="button" id="filtro-mov-devolucao" onclick="setFiltroTipoMovimentacao('devolucao')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Devoluções</button>
                    <button type="button" id="filtro-mov-substituicao" onclick="setFiltroTipoMovimentacao('substituicao')" class="px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200">Substituições</button>
                    <select id="filtro-mov-periodo" onchange="filtroMovimentacoes.periodo = parseInt(this.value); aplicarFiltroMovimentacoes();" class="text-xs border-gray-300 rounded-lg shadow-sm ml-1">
                        <option value="0">Todo o período</option>
                        <option value="7">Últimos 7 dias</option>
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 90 dias</option>
                    </select>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" id="filtro-mov-busca" oninput="filtroMovimentacoes.busca = this.value; aplicarFiltroMovimentacoes();" placeholder="Buscar item, motivo, NF..." class="pl-8 pr-3 py-2 text-xs border-gray-300 rounded-lg shadow-sm w-56">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold"><i class="fas fa-arrow-down mr-1"></i>Entradas: <span id="mov-total-entradas">0</span></span>
                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-800 text-[11px] font-bold"><i class="fas fa-arrow-up mr-1"></i>Saídas: <span id="mov-total-saidas">0</span></span>
                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[11px] font-bold"><i class="fas fa-list mr-1"></i><span id="mov-contagem">0</span> movimentações</span>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" id="tabela-movimentacoes">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data / Registro</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Filial</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">EPI</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Qtd</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Validade do Lote</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Valor Total</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">NF / Fornecedor</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Motivo / Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($estoqueMovimentos as $mov)
                                <tr class="hover:bg-gray-50 border-l-4 {{ $mov->ss_e_tx_tipo === 'entrada' ? 'border-emerald-500' : ($mov->ss_e_tx_tipo === 'saida' ? 'border-rose-500' : ($mov->ss_e_tx_tipo === 'devolucao' ? 'border-teal-500' : 'border-amber-500')) }}"
                                    data-tipo="{{ $mov->ss_e_tx_tipo }}"
                                    data-ts="{{ strtotime($mov->ss_e_tx_data) * 1000 }}"
                                    data-qtd="{{ $mov->ss_e_nb_quantidade }}"
                                    data-busca="{{ mb_strtolower(($mov->epi->ss_e_tx_item ?? '') . ' ' . ($mov->variacao->ss_ev_tx_nome ?? '') . ' ' . ($mov->ss_e_tx_motivo ?? '') . ' ' . ($mov->ss_e_tx_chave_nf ?? '') . ' ' . ($mov->ss_e_tx_fornecedor ?? '') . ' ' . ($filiais[$mov->ss_e_nb_empresa_id ?? 0] ?? 'Matriz')) }}">
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ date('d/m/Y H:i', strtotime($mov->ss_e_tx_data)) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-800">
                                        {{ $filiais[$mov->ss_e_nb_empresa_id ?? 0] ?? 'Matriz' }}
                                    </td>
                                    <td class="px-4 py-3 font-bold text-emerald-950">
                                        {{ $mov->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                        @if($mov->ss_e_nb_variacao_id && $mov->variacao)
                                            <div class="text-xs text-gray-500 font-normal">{{ $mov->variacao->ss_ev_tx_nome }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($mov->ss_e_tx_tipo === 'entrada')
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-xs">Entrada</span>
                                        @elseif($mov->ss_e_tx_tipo === 'saida')
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded font-bold text-xs">Saída</span>
                                        @elseif($mov->ss_e_tx_tipo === 'devolucao')
                                            <span class="px-2 py-0.5 bg-teal-100 text-teal-800 rounded font-bold text-xs">Devolução</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-xs">Substituição</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-900">
                                        {{ $mov->ss_e_nb_quantidade }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if($mov->ss_e_tx_validade)
                                            {{ date('d/m/Y', strtotime($mov->ss_e_tx_validade)) }}
                                            @if($mov->ss_e_tx_validade < now()->format('Y-m-d'))
                                                <div class="mt-0.5"><span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded text-[10px] font-bold">Vencida</span></div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                        @if($mov->ss_e_tx_data_recebimento)
                                            <div class="text-[10px] text-gray-400">Receb: {{ date('d/m/Y', strtotime($mov->ss_e_tx_data_recebimento)) }}</div>
                                        @endif
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
                                <tr id="mov-sem-registro">
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">Nenhuma movimentação de estoque registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div id="mov-vazio" class="hidden text-center text-gray-400 py-8 text-sm">
                        Nenhuma movimentação encontrada com os filtros selecionados.
                    </div>
                </div>
            </div>

            <!-- ABA 4: FARDAMENTO (UNIFORMES) -->
            <div id="tab-content-fardamento" class="aba-content hidden">
                @php
                    $fardamentoCores = ['#153B2E', '#F28C2B', '#0E7490', '#7C3AED', '#B45309', '#059669', '#DC2626', '#2563EB', '#9333EA', '#64748B'];
                    $fardamentoCategorias = [
                        'camisa' => ['icon' => 'fa-tshirt', 'label' => 'Camisa', 'desc' => 'Tamanhos PP a XGG'],
                        'calca' => ['icon' => 'fa-vest', 'label' => 'Calça', 'desc' => 'Tamanhos 36 a 52'],
                        'bota' => ['icon' => 'fa-shoe-prints', 'label' => 'Bota', 'desc' => 'Numeração 33 a 47'],
                    ];
                @endphp

                <!-- Cabeçalho -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center">
                            <i class="fas fa-tshirt text-emerald-700 mr-2"></i> Gestão de Fardamento
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Distribuição de tamanhos cadastrados dos colaboradores e saldo em banco (via Controle de Estoque) para apoiar os pedidos de uniformes.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="abrirModalListaFuncionariosFardamento()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                            <i class="fas fa-users mr-1"></i> Listar Funcionários
                        </button>
                        <span class="px-3 py-2 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-bold">
                            <i class="fas fa-sync-alt mr-1"></i> Saldo atualizado pelos lançamentos de estoque
                        </span>
                    </div>
                </div>

                <!-- Cards por Categoria com Gráfico de Distribuição -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    @foreach($fardamentoCategorias as $tipo => $cat)
                        @php
                            $catDados = $fardamentoDados[$tipo] ?? ['total' => 0, 'grupos' => []];
                            $totalCat = (int) $catDados['total'];
                            $conicParts = [];
                            $inicioPct = 0;
                            $idxCor = 0;
                            foreach ($catDados['grupos'] as $tam => $info) {
                                $pct = $totalCat > 0 ? ($info['qtd'] / $totalCat) * 100 : 0;
                                $fimPct = $inicioPct + $pct;
                                $cor = $fardamentoCores[$idxCor % count($fardamentoCores)];
                                $conicParts[] = "{$cor} {$inicioPct}% {$fimPct}%";
                                $inicioPct = $fimPct;
                                $idxCor++;
                            }
                            $conicStyle = count($conicParts) > 0 ? 'conic-gradient(' . implode(', ', $conicParts) . ')' : 'conic-gradient(#e5e7eb 0% 100%)';
                            $dominante = null;
                            $dominanteQtd = 0;
                            foreach ($catDados['grupos'] as $tam => $info) {
                                if ($info['qtd'] > $dominanteQtd) {
                                    $dominante = $tam;
                                    $dominanteQtd = (int) $info['qtd'];
                                }
                            }
                            $def = $fardamentoDeficit[$tipo] ?? ['demanda' => 0, 'saldo' => 0, 'cobertura' => 0, 'deficit' => 0];
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="px-5 py-4 bg-gradient-to-r from-emerald-950 to-emerald-800 flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-amber-400/20 text-amber-300 flex items-center justify-center text-lg">
                                        <i class="fas {{ $cat['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white text-sm">{{ $cat['label'] }}</h4>
                                        <p class="text-[10px] text-emerald-200 font-semibold uppercase tracking-wider">{{ $cat['desc'] }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-black text-amber-400">{{ $totalCat }}</div>
                                    <div class="text-[10px] text-emerald-200 font-semibold uppercase">colaboradores</div>
                                </div>
                            </div>

                            <div class="p-5 flex-1">
                                @if($totalCat > 0)
                                    <div class="flex items-center gap-5">
                                        <!-- Gráfico Donut -->
                                        <div class="relative w-32 h-32 rounded-full shrink-0 shadow-inner" style="background: {{ $conicStyle }};">
                                            <div class="absolute inset-2.5 bg-white rounded-full flex flex-col items-center justify-center shadow-sm">
                                                <span class="text-xl font-black text-emerald-950 leading-none">{{ $dominante }}</span>
                                                <span class="text-[9px] text-gray-400 font-bold uppercase mt-1">maioria</span>
                                            </div>
                                        </div>

                                        <!-- Legenda -->
                                        <div class="flex-1 min-w-0 space-y-1.5">
                                            @foreach(array_slice($catDados['grupos'], 0, 5, true) as $tam => $info)
                                                @php $corLeg = $fardamentoCores[$loop->index % count($fardamentoCores)]; @endphp
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="flex items-center text-gray-700 font-semibold">
                                                        <span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background: {{ $corLeg }}"></span>
                                                        Tamanho {{ $tam }}
                                                    </span>
                                                    <span class="font-bold text-gray-900">{{ $info['qtd'] }} <span class="text-gray-400 font-medium">({{ $totalCat > 0 ? round($info['qtd'] / $totalCat * 100) : 0 }}%)</span></span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Barras por tamanho com botão Ver -->
                                    <div class="mt-5 pt-4 border-t border-gray-100 space-y-2">
                                        @foreach($catDados['grupos'] as $tam => $info)
                                            @php
                                                $pctBar = $totalCat > 0 ? round($info['qtd'] / $totalCat * 100) : 0;
                                                $corBar = $fardamentoCores[$loop->index % count($fardamentoCores)];
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <span class="w-9 text-xs font-black text-gray-700">{{ $tam }}</span>
                                                <div class="flex-1 bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                                    <div class="h-full rounded-full transition-all" style="width: {{ max(2, $pctBar) }}%; background: {{ $corBar }};"></div>
                                                </div>
                                                <span class="w-7 text-right text-xs font-bold text-gray-800">{{ $info['qtd'] }}</span>
                                                <button type="button" onclick="verFardamentoFuncionarios('{{ $tipo }}', '{{ $tam }}')" class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline shrink-0 cursor-pointer" title="Ver colaboradores com tamanho {{ $tam }}">
                                                    <i class="fas fa-users mr-0.5"></i> Ver
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-10 text-gray-400">
                                        <i class="fas {{ $cat['icon'] }} text-4xl mb-3 text-gray-300"></i>
                                        <p class="text-sm font-semibold text-gray-500">Nenhum colaborador com tamanho cadastrado.</p>
                                        <p class="text-xs mt-1">Os tamanhos são informados no cadastro do funcionário, na seção "Dados de Fardamento".</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Rodapé: Demanda vs Saldo -->
                            <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <div class="text-sm font-black text-gray-900">{{ $def['demanda'] }}</div>
                                    <div class="text-[9px] text-gray-500 font-bold uppercase">Demanda</div>
                                </div>
                                <div class="border-x border-gray-200">
                                    <div class="text-sm font-black text-{{ $def['saldo'] > 0 ? 'amber-600' : 'gray-400' }}">{{ $def['saldo'] }}</div>
                                    <div class="text-[9px] text-gray-500 font-bold uppercase">Saldo em banco</div>
                                </div>
                                <div>
                                    <div class="text-sm font-black {{ $def['deficit'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $def['deficit'] > 0 ? '-' . $def['deficit'] : 'OK' }}
                                    </div>
                                    <div class="text-[9px] text-gray-500 font-bold uppercase">Déficit p/ pedido</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Saldo em Banco (via Controle de Estoque) -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                        <div>
                            <h4 class="text-base font-bold text-gray-900">
                                <i class="fas fa-boxes text-amber-600 mr-2"></i> Saldo em Banco (Controle de Estoque)
                            </h4>
                            <p class="text-xs text-gray-500 mt-0.5">Saldo calculado pelos lançamentos de entrada/saída do Controle de Estoque, por item e variação (tamanho).</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700">
                                <i class="fas fa-tags text-emerald-600 mr-1"></i> {{ count($fardamentoEstoqueLinhas) }} itens/variações
                            </span>
                            <span class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700">
                                <i class="fas fa-box text-amber-600 mr-1"></i> {{ number_format($fardamentoEstoqueTotal) }} unidades (rede)
                            </span>
                            <button type="button" onclick="mudarAba('estoque')" class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow cursor-pointer">
                                <i class="fas fa-warehouse mr-1"></i> Ir para Controle de Estoque
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">EPI / Peça</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Variação / Tamanho</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Saldo Filial Ativa</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Saldo Rede</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Disponibilidade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($fardamentoEstoqueLinhas as $linha)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3">
                                            <div class="font-bold text-emerald-950">{{ $linha['item'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $linha['grupo'] }} @if($linha['ca']) · CA: {{ $linha['ca'] }} @endif</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($linha['variacao_nome'])
                                                <span class="px-2.5 py-1 bg-gray-100 border border-gray-200 rounded-full text-xs font-black text-gray-800">{{ $linha['variacao_nome'] }}</span>
                                            @else
                                                <span class="text-gray-400 text-xs">Geral</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold {{ $linha['saldo_local'] > 0 ? 'text-emerald-800' : 'text-gray-400' }}">
                                            {{ $linha['saldo_local'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-900">
                                            {{ $linha['saldo_rede'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($linha['disponibilidade'] === 'local')
                                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">
                                                    <i class="fas fa-check-circle mr-1"></i> Disponível
                                                </span>
                                            @elseif($linha['disponibilidade'] === 'externo')
                                                <span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold italic">
                                                    <i class="fas fa-truck mr-1"></i> Em outra filial
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Esgotado
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                                            <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                                            <p class="font-semibold text-gray-500">Nenhum item de fardamento com saldo.</p>
                                            <p class="text-xs mt-1 max-w-md mx-auto">
                                                Cadastre as peças no <strong>Catálogo Universal de EPIs</strong> (ex.: grupo "FARDAMENTO", com variações de tamanho) e registre os lançamentos no <strong>Controle de Estoque</strong> — o saldo aparecerá aqui automaticamente.
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-4 text-xs text-gray-600">
                        <span class="flex items-center"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500 mr-1.5"></span> Disponível na Filial Ativa</span>
                        <span class="flex items-center"><span class="inline-block w-3 h-3 rounded-full bg-amber-500 mr-1.5"></span> Sem saldo local, mas disponível em outra filial</span>
                        <span class="flex items-center"><span class="inline-block w-3 h-3 rounded-full bg-rose-400 mr-1.5"></span> Esgotado em toda a rede</span>
                    </div>
                </div>
            </div>

            <!-- ABA 5: KITS DE ENTREGA RÁPIDA -->
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

                        <div class="hidden text-center text-gray-400 py-6 text-xs mb-2" id="lista-colaboradores-vazia">
                            Nenhum colaborador encontrado com o filtro informado.
                        </div>

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
                            <span class="ml-2 px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">{{ count($entregasRecentes) }} registro(s)</span>
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
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Status Ass.</th>
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
                                                @if(!empty($ent->ss_e_tx_retroativo))
                                                    <span class="ml-1 px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded text-[10px] font-bold" title="Registro retroativo - sem baixa de estoque">
                                                        <i class="fas fa-history"></i> Retroativo
                                                    </span>
                                                @endif
                                                @if($ent->ss_e_nb_variacao_id)
                                                    @php $varNome = $ent->variacao ? $ent->variacao->ss_ev_tx_nome : ''; @endphp
                                                    @if($varNome)
                                                        <div class="text-xs text-gray-500">{{ $varNome }}</div>
                                                    @endif
                                                @endif
                                                @if($ent->epi->ss_e_tx_ca)
                                                    <span class="text-gray-400 font-normal">(CA {{ $ent->epi->ss_e_tx_ca }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-gray-800">
                                                {{ $ent->ss_e_nb_quantidade }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-600">
                                                @if($ent->ss_e_tx_vencimento)
                                                    {{ date('d/m/Y', strtotime($ent->ss_e_tx_vencimento)) }}
                                                    @if($ent->ss_e_tx_vencimento < now()->format('Y-m-d'))
                                                        <div class="mt-0.5"><span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded text-[10px] font-bold">Vencida</span></div>
                                                    @elseif($ent->ss_e_tx_vencimento <= now()->addDays(30)->format('Y-m-d'))
                                                        <div class="mt-0.5"><span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Vence em 30 dias</span></div>
                                                    @endif
                                                @else
                                                    -
                                                @endif
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
                                                @if($ent->ss_e_tx_requer_assinatura)
                                                    @if($ent->ss_e_tx_status_assinatura === 'assinada')
                                                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">Assinada</span>
                                                    @elseif($ent->ss_e_tx_status_assinatura === 'negada')
                                                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-800 rounded text-[10px] font-bold" title="{{ $ent->ss_e_tx_justificativa_negacao ?? '' }}">Recusada</span>
                                                    @else
                                                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Pendente</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 text-[10px]">-</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button onclick="abrirModalVencimentoEntrega({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}', '{{ $ent->ss_e_tx_vencimento ?? '' }}')" class="text-blue-600 hover:text-blue-800 font-bold mr-2" title="Editar Vencimento">
                                                    <i class="fas fa-calendar-alt"></i> Venc.
                                                </button>
                                                <button onclick="abrirModalDevolucao({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}')" class="text-amber-600 hover:text-amber-800 font-bold mr-2" title="Registrar Devolução / Encerrar controle">
                                                    <i class="fas fa-undo-alt"></i> Devolver
                                                </button>
                                                <button onclick="abrirModalCancelarEntrega({{ $ent->ss_e_nb_id }}, '{{ addslashes($ent->epi->ss_e_tx_item ?? '') }}')" class="text-rose-600 hover:text-rose-800 font-bold" title="Cancelar / Inativar Entrega">
                                                    <i class="fas fa-ban"></i> Inativar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-3 py-6 text-center text-gray-400">Nenhuma entrega registrada ainda.</td>
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

            <!-- ABA 7: DEVOLUÇÕES & INSPEÇÃO -->
            <div id="tab-content-devolucoes" class="aba-content hidden">
                @php
                    $labelsDevolucaoMotivo = [
                        'avaria' => 'Avaria / Danificado',
                        'perdido' => 'Perdido',
                        'extraviado' => 'Extraviado',
                        'devolvido_empresa' => 'Devolvido à empresa',
                        'vencido' => 'Vencido',
                        'outro' => 'Outro',
                    ];
                    $labelsDevolucaoDestino = [
                        'estoque' => 'Retorno ao estoque',
                        'descarte' => 'Descarte',
                        'inspecao' => 'Inspeção',
                    ];
                    $labelsDevolucaoResultado = [
                        'estornado' => 'Estornado ao estoque',
                        'descartado' => 'Descartado',
                    ];
                @endphp

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center">
                            <i class="fas fa-undo-alt text-emerald-700 mr-2"></i> Devoluções & Inspeção de EPIs
                        </h3>
                        <p class="text-xs text-gray-500">Encerre o controle de um EPI entregue informando o motivo e o destino (retorno ao estoque, descarte ou inspeção).</p>
                    </div>
                    <button type="button" onclick="abrirModalDevolucao()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow cursor-pointer">
                        <i class="fas fa-plus mr-1"></i> + Registrar Devolução
                    </button>
                </div>

                <!-- Pendências de Inspeção -->
                <div class="border border-amber-300 rounded-xl overflow-hidden mb-6">
                    <div class="bg-amber-50 px-4 py-3 flex items-center justify-between flex-wrap gap-2">
                        <span class="text-sm font-bold text-amber-900 uppercase"><i class="fas fa-search mr-1"></i> Pendências de Inspeção ({{ $devolucoesPendentesCount }})</span>
                        <span class="text-[10px] text-amber-700 font-semibold">Avalie os itens: estornar para o estoque ou confirmar descarte</span>
                    </div>
                    @if($devolucoesPendentes->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Colaborador</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">EPI</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Motivo</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Registrado por</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Data</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($devolucoesPendentes as $dev)
                                        <tr class="hover:bg-amber-50/40">
                                            <td class="px-3 py-2 font-bold text-gray-900">{{ $dev->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                            <td class="px-3 py-2 font-semibold text-emerald-950">
                                                {{ $dev->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                                @if($dev->epi && $dev->epi->ss_e_tx_ca)
                                                    <span class="text-gray-400 font-normal">(CA {{ $dev->epi->ss_e_tx_ca }})</span>
                                                @endif
                                                @if($dev->variacao)
                                                    <div class="text-[10px] text-gray-500">{{ $dev->variacao->ss_ev_tx_nome }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-gray-800">{{ $dev->ss_ed_nb_quantidade }}</td>
                                            <td class="px-3 py-2 text-center text-gray-700">{{ $labelsDevolucaoMotivo[$dev->ss_ed_tx_motivo] ?? $dev->ss_ed_tx_motivo }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ $dev->usuarioRegistro->nome ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ date('d/m/Y H:i', strtotime($dev->ss_ed_tx_data_registro)) }}</td>
                                            <td class="px-3 py-2 text-center space-x-1 whitespace-nowrap">
                                                <form method="POST" action="{{ route('epi.devolucao.decidir', $dev->ss_ed_nb_id) }}" class="inline" onsubmit="return confirm('Confirmar estorno deste item para o estoque?')">
                                                    @csrf
                                                    <input type="hidden" name="action" value="estornar">
                                                    <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold cursor-pointer">
                                                        <i class="fas fa-box-open mr-1"></i> Estornar ao estoque
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('epi.devolucao.decidir', $dev->ss_ed_nb_id) }}" class="inline" onsubmit="return confirm('Confirmar descarte deste item? Ele não voltará ao estoque.')">
                                                    @csrf
                                                    <input type="hidden" name="action" value="descartar">
                                                    <button type="submit" class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold cursor-pointer">
                                                        <i class="fas fa-trash mr-1"></i> Descartar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-400 text-sm">Nenhuma pendência de inspeção no momento.</div>
                    @endif
                </div>

                <!-- Histórico de Devoluções (Auditoria) -->
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3">
                        <span class="text-sm font-bold text-gray-800 uppercase"><i class="fas fa-history mr-1"></i> Histórico de Devoluções (auditoria)</span>
                    </div>
                    @if($devolucoesHistorico->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Data</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Colaborador</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">EPI</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Motivo</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Destino</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Situação</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Registrado por</th>
                                        <th class="px-3 py-2 text-center font-bold text-gray-500">Decidido por</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-500">Observação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($devolucoesHistorico as $dev)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ date('d/m/Y H:i', strtotime($dev->ss_ed_tx_data_registro)) }}</td>
                                            <td class="px-3 py-2 font-bold text-gray-900">{{ $dev->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                            <td class="px-3 py-2 font-semibold text-emerald-950">
                                                {{ $dev->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                                @if($dev->variacao)
                                                    <div class="text-[10px] text-gray-500">{{ $dev->variacao->ss_ev_tx_nome }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-gray-800">{{ $dev->ss_ed_nb_quantidade }}</td>
                                            <td class="px-3 py-2 text-center text-gray-700">{{ $labelsDevolucaoMotivo[$dev->ss_ed_tx_motivo] ?? $dev->ss_ed_tx_motivo }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if($dev->ss_ed_tx_destino === 'estoque')
                                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">Estoque</span>
                                                @elseif($dev->ss_ed_tx_destino === 'descarte')
                                                    <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded text-[10px] font-bold">Descarte</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Inspeção</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                @if($dev->ss_ed_tx_status === 'pendente')
                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Aguardando decisão</span>
                                                @elseif($dev->ss_ed_tx_resultado_inspecao)
                                                    <span class="px-1.5 py-0.5 {{ $dev->ss_ed_tx_resultado_inspecao === 'estornado' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }} rounded text-[10px] font-bold">{{ $labelsDevolucaoResultado[$dev->ss_ed_tx_resultado_inspecao] ?? $dev->ss_ed_tx_resultado_inspecao }}</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] font-bold">Concluída</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ $dev->usuarioRegistro->nome ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center text-gray-600">{{ $dev->usuarioDecisao->nome ?? '-' }}</td>
                                            <td class="px-3 py-2 text-gray-500 max-w-[200px]">{{ $dev->ss_ed_tx_observacao ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-400 text-sm">Nenhuma devolução registrada ainda.</div>
                    @endif
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
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Variações (Tamanhos, Cores, etc.)</label>
                    <div id="container-variacoes" class="space-y-2">
                        <div class="flex space-x-2 variacao-row">
                            <input type="text" name="variacoes[]" class="flex-1 text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Tamanho 38">
                            <button type="button" onclick="removerVariacao(this)" class="text-rose-500 hover:text-rose-700 p-1"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" onclick="adicionarVariacao()" class="mt-2 text-xs text-emerald-700 hover:text-emerald-900 font-bold">
                        <i class="fas fa-plus mr-1"></i> Adicionar Variação
                    </button>
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
    <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-boxes text-amber-600 mr-2"></i> Registrar Movimentação de Estoque
        </h3>

        <form method="POST" action="{{ route('epi.estoque.store') }}" enctype="multipart/form-data" id="form-estoque">
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
                    <select name="ss_e_nb_epi_id" id="estoque-epi-select" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm" onchange="atualizarVariacoesEstoque(this.value)">
                        <option value="">-- Selecione o EPI --</option>
                        @foreach($episCatalogo as $e)
                             <option value="{{ $e->ss_e_nb_id }}">{{ $e->ss_e_tx_grupo }} - {{ $e->ss_e_tx_item }} (CA: {{ $e->ss_e_tx_ca ?? 'N/D' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Seção: Quantidade por Variação (aparece quando EPI tem variações) -->
                <div class="md:col-span-2" id="estoque-variacoes-table" style="display:none;">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <label class="block text-xs font-bold text-amber-800 uppercase mb-2">Distribuir Quantidade por Tamanho / Variação</label>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-amber-200">
                                        <th class="text-left py-1 pr-2 font-bold text-amber-900">Variação</th>
                                        <th class="text-center py-1 px-2 font-bold text-amber-900">Quantidade</th>
                                    </tr>
                                </thead>
                                <tbody id="estoque-variacoes-rows"></tbody>
                                <tfoot>
                                    <tr class="border-t border-amber-200">
                                        <td class="text-right py-1 pr-2 font-bold text-amber-900">Total</td>
                                        <td class="text-center py-1 px-2">
                                            <span id="estoque-variacoes-total" class="font-bold text-amber-900">0</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="text-[10px] text-amber-700 mt-1"><i class="fas fa-info-circle mr-1"></i> Se não preencher nenhuma quantidade e confirmar, um alerta será exibido.</p>
                    </div>
                </div>

                <!-- Seção: Quantidade Única (aparece quando EPI não tem variações ou user escolhe "Sem variação") -->
                <div id="estoque-qtd-unica-container">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Quantidade *</label>
                    <input type="number" name="ss_e_nb_quantidade" id="estoque-qtd-unica" min="1" value="1" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
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
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valor Unitário (R$)</label>
                    <input type="number" step="0.01" name="ss_e_db_valor_unitario" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Data de Recebimento</label>
                    <input type="date" name="ss_e_tx_data_recebimento" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Validade do Lote *</label>
                    <input type="date" name="ss_e_tx_validade" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                    <div class="text-[10px] text-gray-400 mt-0.5">A validade informada aqui define o vencimento das entregas deste EPI (tem prioridade sobre a vida útil do cadastro).</div>
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

<!-- MODAL: EDITAR VENCIMENTO DA ENTREGA (REGULARIZAÇÃO DE VALIDADE) -->
<div id="modal-vencimento-entrega" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i> Editar Vencimento da Entrega
        </h3>
        <p id="desc-vencimento-entrega" class="text-xs text-gray-600 mb-4"></p>

        <form id="form-vencimento-entrega" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nova Data de Vencimento *</label>
                <input type="date" name="ss_e_tx_vencimento" id="input-vencimento-entrega" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="fecharModalVencimentoEntrega()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-700 text-white text-xs font-bold rounded-lg hover:bg-blue-800 shadow">Salvar Vencimento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR DEVOLUÇÃO / ENCERRAMENTO DE EPI -->
<div id="modal-devolucao" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
            <i class="fas fa-undo-alt text-amber-600 mr-2"></i> Registrar Devolução de EPI
        </h3>
        <p id="desc-devolucao" class="text-xs text-gray-600 mb-4">Informe o colaborador, a entrega, o motivo e o destino do item.</p>

        <form id="form-devolucao" method="POST" action="{{ route('epi.devolucao.store') }}">
            @csrf
            <input type="hidden" name="ss_ed_nb_entrega_id" id="devolucao-entrega-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Colaborador *</label>
                    <select id="devolucao-colaborador" onchange="carregarEntregasDevolucao(this.value)" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="">-- Selecione o Colaborador --</option>
                        @foreach($colaboradores as $c)
                            <option value="{{ $c->ss_c_nb_id }}">{{ $c->ss_c_tx_nome }} ({{ $c->ss_c_tx_cargo ?? 'Sem Cargo' }} - Mat: {{ $c->ss_c_tx_matricula ?? 'S/N' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Entrega / EPI *</label>
                    <select id="devolucao-entrega" required onchange="atualizarQtdMaxDevolucao()" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="">-- Selecione a entrega --</option>
                    </select>
                    <div id="devolucao-entrega-info" class="text-[10px] text-gray-400 mt-1"></div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Quantidade *</label>
                    <input type="number" id="devolucao-qtd" name="ss_ed_nb_quantidade" min="1" value="1" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                    <div class="text-[10px] text-gray-400 mt-1">Se devolver menos que o total, a entrega continua ativa para o restante.</div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Motivo da devolução *</label>
                    <select name="ss_ed_tx_motivo" required class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="avaria">Avaria / Danificado</option>
                        <option value="perdido">Perdido</option>
                        <option value="extraviado">Extraviado</option>
                        <option value="devolvido_empresa">Devolvido à empresa</option>
                        <option value="vencido">Vencido</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Destino do item *</label>
                    <select name="ss_ed_tx_destino" id="devolucao-destino" required onchange="atualizarAvisoDestinoDevolucao()" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                        <option value="estoque">Volta para o estoque (fica disponível para nova entrega)</option>
                        <option value="descarte">Descarte / Encerra o controle (não volta ao estoque)</option>
                        <option value="inspecao">Vai para inspeção (gestor avalia depois: estornar ou descartar)</option>
                    </select>
                    <div id="devolucao-destino-aviso" class="mt-1 text-[10px] font-semibold text-emerald-600">O item voltará ao estoque e ficará disponível para novas entregas.</div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Observação</label>
                    <textarea name="ss_ed_tx_observacao" rows="2" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Detalhes adicionais (opcional)..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="fecharModalDevolucao()" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg shadow">Registrar Devolução</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: FUNCIONÁRIOS COM UM EPI ESPECÍFICO -->
<div id="modal-epi-entregas" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full p-6 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modal-epi-entregas-titulo" class="text-lg font-bold text-gray-900 flex items-center">
                <i class="fas fa-users text-emerald-700 mr-2"></i> Funcionários com este EPI
            </h3>
            <button type="button" onclick="fecharModalEpiEntregas()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none cursor-pointer">&times;</button>
        </div>
        <div id="modal-epi-entregas-corpo" class="flex-1 overflow-y-auto min-h-0"></div>
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

<!-- MODAL: COLABORADORES POR TAMANHO DE FARDAMENTO -->
<div id="modal-fardamento-funcionarios" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modal-fardamento-func-titulo" class="text-lg font-bold text-gray-900 flex items-center">
                <i class="fas fa-users text-emerald-700 mr-2"></i> Colaboradores
            </h3>
            <button type="button" onclick="fecharModalFardamentoFuncionarios()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none cursor-pointer">&times;</button>
        </div>
        <div id="modal-fardamento-func-lista" class="max-h-96 overflow-y-auto space-y-2"></div>
    </div>
</div>

<!-- MODAL: LISTA GERAL DE FUNCIONÁRIOS E FARDAMENTO -->
<div id="modal-lista-funcionarios-fardamento" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full p-6 flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                <i class="fas fa-user-tie text-emerald-700 mr-2"></i> Funcionários e Fardamento
            </h3>
            <button type="button" onclick="fecharModalListaFuncionariosFardamento()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none cursor-pointer">&times;</button>
        </div>

        <!-- Filtros -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
            <div class="col-span-2 md:col-span-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar Funcionário</label>
                <input type="text" id="filtro-nome-funcionario" oninput="filtrarListaFuncionariosFardamento()" placeholder="Digite o nome..." class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Camisa</label>
                <select id="filtro-camisa-funcionario" onchange="filtrarListaFuncionariosFardamento()" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                    <option value="">Todos</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Calça</label>
                <select id="filtro-calca-funcionario" onchange="filtrarListaFuncionariosFardamento()" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                    <option value="">Todos</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Bota</label>
                <select id="filtro-bota-funcionario" onchange="filtrarListaFuncionariosFardamento()" class="w-full text-xs border-gray-300 rounded-lg shadow-sm">
                    <option value="">Todos</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between mb-2">
            <p class="text-xs text-gray-500">
                Mostrando <strong id="contador-funcionarios-fardamento" class="text-emerald-700">0</strong> de <strong class="text-gray-800">{{ count($fardamentoFuncionarios) }}</strong> funcionário(s)
            </p>
            <button type="button" onclick="limparFiltrosListaFuncionariosFardamento()" class="text-[11px] font-bold text-rose-600 hover:text-rose-800 cursor-pointer">
                <i class="fas fa-eraser mr-1"></i> Limpar filtros
            </button>
        </div>

        <div class="flex-1 overflow-y-auto border border-gray-200 rounded-xl min-h-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase">Funcionário</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Camisa</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Calça</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Bota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Cargo / Setor</th>
                    </tr>
                </thead>
                <tbody id="tbody-funcionarios-fardamento" class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Oculta / exibe o painel de Monitoramento de Validade (estado salvo no navegador)
    window.toggleMonitoramentoValidade = function() {
        var body = document.getElementById('monitoramento-validade-body');
        var icone = document.getElementById('icone-toggle-monitoramento');
        var texto = document.getElementById('texto-toggle-monitoramento');
        if (!body) return;
        var oculto = body.classList.toggle('hidden');
        if (icone) icone.style.transform = oculto ? 'rotate(180deg)' : '';
        if (texto) texto.textContent = oculto ? 'Exibir' : 'Ocultar';
        try { localStorage.setItem('epi_monitoramento_oculto', oculto ? '1' : '0'); } catch (e) {}
    };

    // Restaura o estado salvo do painel ao carregar a página
    (function() {
        var body = document.getElementById('monitoramento-validade-body');
        if (!body) return;
        var oculto = false;
        try { oculto = localStorage.getItem('epi_monitoramento_oculto') === '1'; } catch (e) {}
        if (oculto) {
            body.classList.add('hidden');
            var icone = document.getElementById('icone-toggle-monitoramento');
            var texto = document.getElementById('texto-toggle-monitoramento');
            if (icone) icone.style.transform = 'rotate(180deg)';
            if (texto) texto.textContent = 'Exibir';
        }
    })();

    // Função global de troca de abas
    window.mudarAba = function(nomeAba) {
        var abas = ['sacola', 'catalogo', 'estoque', 'fardamento', 'kits', 'fichas', 'filiais', 'devolucoes'];
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
        // Persiste a aba ativa na URL e nos formulários de filtro, para que
        // recarregamentos (ex: aplicar filtro no catálogo) mantenham a aba aberta
        try {
            var url = new URL(window.location.href);
            if (url.searchParams.get('tab') !== nomeAba) {
                url.searchParams.set('tab', nomeAba);
                window.history.replaceState({}, '', url);
            }
        } catch (e) {}
        document.querySelectorAll('input[name="tab"]').forEach(function(el) {
            el.value = nomeAba;
        });
    };

    // Variáveis Globais de Estado
    let filialGlobalId = {{ $filialSelecionada }};
    let matrizEstoqueApi = [];
    let sacolaItens = [];
    let kitsLista = @json($kits);
    let episCatalogoLista = @json($episCatalogo);
    let variacoesPorEpi = @json($episCatalogo->mapWithKeys(fn($epi) => [$epi->ss_e_nb_id => $epi->variacoes->where('ss_ev_tx_status', 'ativo')->values()]));

    document.addEventListener('DOMContentLoaded', function () {
        carregarEstoqueDisponivelAPI();
        aplicarFiltroMovimentacoes();

        // Restaura a aba ativa vinda da URL (ex: filtro aplicado no catálogo)
        try {
            var abaUrl = new URLSearchParams(window.location.search).get('tab');
            var abasValidas = ['sacola', 'catalogo', 'estoque', 'fardamento', 'kits', 'fichas', 'filiais', 'devolucoes'];
            if (abaUrl && abasValidas.indexOf(abaUrl) !== -1) {
                window.mudarAba(abaUrl);
            }
        } catch (e) {}
    });

    function atualizarFilialGlobal(filialId) {
        filialGlobalId = filialId;
        carregarEstoqueDisponivelAPI();
    }

    // Modo Entrega Retroativa: atualiza a ficha sem validar saldo e sem baixa de estoque
    function retroativoAtivo() {
        const el = document.getElementById('sacola-retroativo');
        return !!(el && el.checked);
    }

    function alternarModoRetroativo() {
        const banner = document.getElementById('banner-retroativo');
        if (banner) banner.classList.toggle('hidden', !retroativoAtivo());
        carregarEstoqueDisponivelAPI();
        window.renderizarTabelaSacola();
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
                    if (typeof renderizarControleEstoque === 'function') renderizarControleEstoque();
                }
            })
            .catch(err => console.error('Erro ao carregar estoque API:', err));
    }

    // ===== Combobox de EPI (dropdown com busca por nome ou CA) =====

    let epiSelecionadoSacola = null;

    function renderizarOptionsSelectEpi(ocultarEsgotados) {
        const lista = document.getElementById('sacola-epi-lista');
        if (!lista || !Array.isArray(matrizEstoqueApi) || matrizEstoqueApi.length === 0) return;

        lista.innerHTML = '';

        const retroativo = retroativoAtivo();

        matrizEstoqueApi.forEach(epi => {
            // Mostrar TODOS os EPIs cadastrados, inclusive com saldo zerado,
            // exceto quando o gestor marcar "Ocultar itens esgotados"
            if (ocultarEsgotados && !retroativo && epi.saldo_rede <= 0) {
                return;
            }

            let corItem = '#065f46';
            let textoSaldo = `Saldo local: ${epi.saldo_atual}`;
            if (retroativo) {
                corItem = '#7c3aed';
                textoSaldo = 'Retroativo - sem baixa de estoque';
            } else if (epi.saldo_rede <= 0) {
                corItem = '#9ca3af';
                textoSaldo = 'Esgotado em toda a rede';
            } else if (epi.disponibilidade === 'externo') {
                corItem = '#d97706';
                textoSaldo = `Sem saldo nesta filial, disponível na rede: ${epi.saldo_rede}`;
            }

            const botao = document.createElement('button');
            botao.type = 'button';
            botao.dataset.id = epi.id;
            botao.className = 'w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-gray-100 last:border-0 cursor-pointer';
            botao.innerHTML = `
                <div class="text-xs font-bold" style="color: ${corItem}">
                    ${window.escFardamento(epi.item || 'EPI')}
                    ${epi.ca ? '<span class="text-gray-400 font-semibold">[CA: ' + window.escFardamento(epi.ca) + ']</span>' : ''}
                </div>
                <div class="text-[10px] text-gray-500">${window.escFardamento(epi.grupo || '')} &middot; ${textoSaldo}</div>
            `;
            botao.addEventListener('click', function() {
                selecionarEpi(parseInt(this.dataset.id));
            });
            lista.appendChild(botao);
        });

        // Estado vazio
        if (lista.children.length === 0) {
            lista.innerHTML = '<div class="px-3 py-6 text-center text-xs text-gray-400">Nenhum EPI encontrado.</div>';
        }

        filtrarDropdownEpi();
    }

    window.toggleDropdownEpi = function() {
        const dropdown = document.getElementById('sacola-epi-dropdown');
        if (!dropdown) return;

        const aberto = !dropdown.classList.contains('hidden');
        if (aberto) {
            fecharDropdownEpi();
        } else {
            dropdown.classList.remove('hidden');
            dropdown.style.setProperty('display', 'block', 'important');
            const busca = document.getElementById('sacola-epi-busca');
            if (busca) {
                busca.value = '';
                busca.focus();
            }
            filtrarDropdownEpi();
        }
    };

    function fecharDropdownEpi() {
        const dropdown = document.getElementById('sacola-epi-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
            dropdown.style.setProperty('display', 'none', 'important');
        }
    }

    function selecionarEpi(epiId) {
        const epiInfo = (Array.isArray(matrizEstoqueApi) ? matrizEstoqueApi : []).find(e => parseInt(e.id) === epiId);
        if (!epiInfo) return;

        epiSelecionadoSacola = epiInfo;

        const triggerText = document.getElementById('sacola-epi-trigger-text');
        if (triggerText) {
            triggerText.textContent = `${epiInfo.item} ${epiInfo.ca ? '[CA: ' + epiInfo.ca + ']' : ''}${epiInfo.saldo_rede > 0 ? ' (Saldo: ' + epiInfo.saldo_rede + ')' : ''}`;
            triggerText.className = 'text-sm font-bold text-emerald-950';
        }

        fecharDropdownEpi();
    }

    // Filtro de busca por nome do EPI ou número do CA
    function filtrarDropdownEpi() {
        const busca = document.getElementById('sacola-epi-busca');
        const lista = document.getElementById('sacola-epi-lista');
        if (!busca || !lista) return;

        const termo = busca.value.trim().toLowerCase();

        let visiveis = 0;
        Array.from(lista.children).forEach(item => {
            const match = termo === '' || item.textContent.toLowerCase().includes(termo);
            item.style.display = match ? '' : 'none';
            if (match) visiveis++;
        });

        const vazio = lista.querySelector('.epi-busca-vazio');
        if (visiveis === 0) {
            if (!vazio) {
                const div = document.createElement('div');
                div.className = 'epi-busca-vazio px-3 py-6 text-center text-xs text-gray-400';
                div.textContent = 'Nenhum EPI encontrado para a busca.';
                lista.appendChild(div);
            }
        } else if (vazio) {
            vazio.remove();
        }
    }

    function navegarDropdownEpi(event) {
        if (event.key === 'Escape') {
            fecharDropdownEpi();
        } else if (event.key === 'Enter') {
            const lista = document.getElementById('sacola-epi-lista');
            if (!lista) return;
            const primeiro = Array.from(lista.children).find(item => item.style.display !== 'none' && item.dataset.id);
            if (primeiro) {
                selecionarEpi(parseInt(primeiro.dataset.id));
                event.preventDefault();
            }
        }
    }

    document.addEventListener('click', function(event) {
        const combobox = document.getElementById('sacola-epi-combobox');
        const dropdown = document.getElementById('sacola-epi-dropdown');
        if (combobox && dropdown && !combobox.contains(event.target)) {
            fecharDropdownEpi();
        }
    });

    let driverCanvases = {};

    // Adicionar EPI Individual à Sacola
    window.adicionarEpiSacola = function() {
        const epiInfo = epiSelecionadoSacola;
        const qtd = parseInt(document.getElementById('sacola-epi-qtd').value) || 1;

        if (!epiInfo) {
            Swal.fire('Atenção', 'Selecione um EPI da lista!', 'warning');
            return;
        }

        // Se o EPI tem variações, perguntar qual usar
        const variacoes = Array.isArray(epiInfo.variacoes) ? epiInfo.variacoes.filter(v => v.ss_ev_tx_status === 'ativo' || !v.ss_ev_tx_status) : [];
        if (variacoes.length > 0) {
            const varOptions = variacoes.map(v => `<option value="${v.ss_ev_nb_id || v.id}">${v.ss_ev_tx_nome || v.nome}</option>`).join('');
            Swal.fire({
                title: 'Selecione a Variação',
                html: `<select id="swal-variacao-select" class="swal2-input">${varOptions}</select>`,
                showCancelButton: true,
                confirmButtonText: 'Adicionar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return document.getElementById('swal-variacao-select').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const varId = parseInt(result.value);
                    const varNome = variacoes.find(v => (v.ss_ev_nb_id || v.id) === varId)?.ss_ev_tx_nome || '';
                    epiInfo.variacao_id = varId;
                    epiInfo.variacao_nome = varNome;
                    window.inserirItemNaSacola(epiInfo, qtd);
                }
            });
        } else {
            epiInfo.variacao_id = null;
            epiInfo.variacao_nome = null;
            window.inserirItemNaSacola(epiInfo, qtd);
        }
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
            // (exceto no modo retroativo, onde todos os EPIs cadastrados são liberados)
            if (epiInfo && (retroativoAtivo() || epiInfo.saldo_rede > 0)) {
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

        const retroativo = retroativoAtivo();

        // Modo retroativo: sem validação/transferência de saldo (apenas atualização de ficha)
        if (!retroativo && epiInfo.saldo_atual < qtd && filiaisComSaldo.length > 0) {
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
            outras_filiais: filiaisComSaldo,
            variacao_id: epiInfo.variacao_id || null,
            variacao_nome: epiInfo.variacao_nome || null,
            retroativo: retroativo,
            esgotado: !retroativo && (parseInt(epiInfo.saldo_rede) <= 0)
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
                if (item.retroativo) {
                    alertaOrigemHtml = `
                        <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 rounded-lg text-[11px] font-bold">
                            <i class="fas fa-history mr-1"></i> Retroativo - sem baixa de estoque
                        </span>
                    `;
                } else if (item.esgotado) {
                    alertaOrigemHtml = `
                        <span class="inline-block px-2 py-1 bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Esgotado em toda a rede
                        </span>
                    `;
                } else if (item.precisa_transferencia) {
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

                const variacaoHtml = item.variacao_nome ? `<div class="text-xs text-amber-700 font-semibold">${item.variacao_nome}</div>` : '';
                rowsHtml += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-bold text-gray-900">
                            ${item.nome}
                            ${variacaoHtml}
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

                <!-- Observação para o motorista -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="mb-3">
                        <label class="text-xs font-bold text-gray-700 block mb-1">Observação para este motorista:</label>
                        <input type="text" id="obs-motorista-${colabId}" class="w-full text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Entregue em mãos...">
                    </div>
                    <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded mb-3">
                        <i class="fas fa-info-circle mr-1"></i> A assinatura será coletada diretamente pelo colaborador no sistema.
                    </p>
                    <div class="flex justify-end">
                        <button type="button" onclick="window.salvarEntregaMotoristaIndividual(${colabId})" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-lg shadow-md flex items-center transition cursor-pointer">
                            <i class="fas fa-check-circle mr-1.5 text-amber-400 text-sm"></i> Finalizar Entrega (Sem Assinatura)
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

        const obsVal = document.getElementById(`obs-motorista-${colabId}`) ? document.getElementById(`obs-motorista-${colabId}`).value : '';

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('ss_e_nb_colaborador_id', colabId);
        formData.append('ss_e_tx_data_entrega', dataEntrega);
        formData.append('ss_e_nb_empresa_id', filialGlobalId);
        formData.append('ss_e_tx_observacao', obsVal);
        formData.append('retroativo', retroativoAtivo() ? 1 : 0);

        itensMotorista.forEach((item, idx) => {
            formData.append(`itens[${idx}][epi_id]`, item.epi_id);
            formData.append(`itens[${idx}][quantidade]`, item.quantidade);
            formData.append(`itens[${idx}][empresa_origem_id]`, item.filial_origem);
            if (item.variacao_id) formData.append(`itens[${idx}][variacao_id]`, item.variacao_id);
        });

        fetch('{{ route("epi.entrega.store") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            if (resData && resData.status === 'success') {
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
                Swal.fire('Erro!', (resData && resData.message) || 'Falha ao registrar a entrega do motorista.', 'error');
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
                    retroativo: retroativoAtivo() ? 1 : 0,
                    entregas: []
                };

                colabIds.forEach(id => {
                    const itensMotorista = sacolaItens.filter(i => parseInt(i.colaborador_id) === id);
                    let obsVal = document.getElementById(`obs-motorista-${id}`) ? document.getElementById(`obs-motorista-${id}`).value : '';

                    bulkPayload.entregas.push({
                        ss_e_nb_colaborador_id: id,
                        ss_e_tx_data_entrega: dataEntrega,
                        ss_e_nb_empresa_id: filialGlobalId,
                        ss_e_tx_observacao: obsVal,
                        itens: itensMotorista.map(i => ({
                            epi_id: i.epi_id,
                            quantidade: i.quantidade,
                            empresa_origem_id: i.filial_origem,
                            variacao_id: i.variacao_id || null
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
                    if (resData && resData.status === 'success') {
                        Swal.fire('Sucesso!', resData.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Erro!', (resData && resData.message) || 'Ocorreu uma falha ao registrar as entregas em lote.', 'error');
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

        // Carregar variações
        var container = document.getElementById('container-variacoes');
        if (container) {
            container.innerHTML = '';
            if (epi.variacoes && Array.isArray(epi.variacoes)) {
                epi.variacoes.forEach(function(v) {
                    if (v.ss_ev_tx_status === 'ativo') {
                        window.adicionarVariacao(v.ss_ev_tx_nome);
                    }
                });
            }
            if (container.children.length === 0) {
                window.adicionarVariacao('');
            }
        }

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

    window.abrirModalNovaEntradaEstoque = function(epiId) {
        var modal = document.getElementById('modal-estoque-entrada');
        if (modal) {
            if (epiId) {
                var select = document.getElementById('estoque-epi-select');
                if (select) {
                    select.value = String(epiId);
                    window.atualizarVariacoesEstoque(epiId);
                }
            }
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

    // Regularização manual do vencimento de uma entrega
    window.abrirModalVencimentoEntrega = function(entregaId, itemNome, vencimentoAtual) {
        var modal = document.getElementById('modal-vencimento-entrega');
        if (!modal) return;
        document.getElementById('desc-vencimento-entrega').textContent = 'Ajuste a data de vencimento da entrega do item: "' + itemNome + '".';
        const form = document.getElementById('form-vencimento-entrega');
        form.action = `{{ url('/epi/entrega') }}/${entregaId}/vencimento`;
        document.getElementById('input-vencimento-entrega').value = vencimentoAtual || '';
        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.fecharModalVencimentoEntrega = function() {
        var modal = document.getElementById('modal-vencimento-entrega');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    // Devolução / encerramento de EPI
    let devolucaoPreEntrega = null;

    window.abrirModalDevolucao = function(entregaId, itemNome, colaboradorId) {
        var modal = document.getElementById('modal-devolucao');
        if (!modal) return;
        var desc = document.getElementById('desc-devolucao');
        if (desc) desc.textContent = itemNome
            ? 'Encerrando o controle do item: "' + itemNome + '". Informe o motivo e o destino.'
            : 'Informe o colaborador e a entrega para registrar a devolução.';
        var input = document.getElementById('devolucao-entrega-id');
        if (input) input.value = '';
        document.getElementById('devolucao-qtd').value = '1';
        document.getElementById('devolucao-entrega').innerHTML = '<option value="">-- Selecione a entrega --</option>';
        document.getElementById('devolucao-entrega-info').textContent = '';
        devolucaoPreEntrega = entregaId || null;
        var selColab = document.getElementById('devolucao-colaborador');
        if (selColab) {
            selColab.value = colaboradorId || '';
            if (colaboradorId) carregarEntregasDevolucao(colaboradorId);
        }
        atualizarAvisoDestinoDevolucao();
        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.carregarEntregasDevolucao = function(colaboradorId) {
        var sel = document.getElementById('devolucao-entrega');
        if (!sel) return;
        document.getElementById('devolucao-entrega-info').textContent = '';
        if (!colaboradorId) {
            sel.innerHTML = '<option value="">-- Selecione a entrega --</option>';
            return;
        }
        sel.innerHTML = '<option value="">Carregando...</option>';
        fetch(`{{ url('/epi/colaborador') }}/${colaboradorId}/entregas`)
            .then(r => r.json())
            .then(res => {
                sel.innerHTML = '<option value="">-- Selecione a entrega --</option>';
                var entregas = res.data || [];
                if (!entregas.length) {
                    sel.innerHTML = '<option value="">Nenhuma entrega ativa para este colaborador</option>';
                    return;
                }
                entregas.forEach(function(e) {
                    var opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.item + (e.variacao ? ' (' + e.variacao + ')' : '') + ' - Qtd: ' + e.quantidade + (e.vencimento ? ' - Venc: ' + e.vencimento : '');
                    opt.dataset.quantidade = e.quantidade;
                    opt.dataset.info = (e.ca ? 'CA: ' + e.ca + ' | ' : '') + 'Entrega: ' + (e.data_entrega || '-') + (e.vencimento ? ' | Venc: ' + e.vencimento : '');
                    sel.appendChild(opt);
                });
                if (devolucaoPreEntrega) {
                    sel.value = String(devolucaoPreEntrega);
                    atualizarQtdMaxDevolucao();
                    devolucaoPreEntrega = null;
                }
            })
            .catch(() => {
                sel.innerHTML = '<option value="">Falha ao carregar entregas</option>';
            });
    };

    window.atualizarQtdMaxDevolucao = function() {
        var sel = document.getElementById('devolucao-entrega');
        var qtd = document.getElementById('devolucao-qtd');
        var info = document.getElementById('devolucao-entrega-info');
        var hiddenId = document.getElementById('devolucao-entrega-id');
        var opt = sel.selectedOptions[0];
        if (opt && opt.dataset.quantidade) {
            qtd.max = opt.dataset.quantidade;
            qtd.value = opt.dataset.quantidade;
            if (info) info.textContent = opt.dataset.info || '';
            if (hiddenId) hiddenId.value = sel.value;
        } else {
            qtd.max = 9999;
            if (info) info.textContent = '';
            if (hiddenId) hiddenId.value = '';
        }
    };

    window.atualizarAvisoDestinoDevolucao = function() {
        var sel = document.getElementById('devolucao-destino');
        var aviso = document.getElementById('devolucao-destino-aviso');
        if (!sel || !aviso) return;
        var mapa = {
            'estoque': 'O item voltará ao estoque e ficará disponível para novas entregas.',
            'descarte': 'O controle de validade deste item será encerrado. Nada retorna ao estoque.',
            'inspecao': 'O item ficará na fila de inspeção. O gestor decidirá depois se volta ao estoque ou é descartado.'
        };
        aviso.textContent = mapa[sel.value] || '';
        aviso.className = 'mt-1 text-[10px] font-semibold ' + (sel.value === 'estoque' ? 'text-emerald-600' : sel.value === 'descarte' ? 'text-rose-600' : 'text-amber-600');
    };

    window.fecharModalDevolucao = function() {
        var modal = document.getElementById('modal-devolucao');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    // Funcionários com um EPI específico (seta na lista de CAs com atenção)
    window.abrirModalEpiEntregas = function(epiId, itemNome) {
        var modal = document.getElementById('modal-epi-entregas');
        if (!modal) return;
        document.getElementById('modal-epi-entregas-titulo').textContent = 'Funcionários com "' + itemNome + '"';
        var corpo = document.getElementById('modal-epi-entregas-corpo');
        corpo.innerHTML = '<div class="text-center text-gray-400 py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="text-sm mt-2">Carregando...</p></div>';
        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');

        fetch(`{{ url('/epi') }}/${epiId}/entregas`)
            .then(r => r.json())
            .then(res => {
                var entregas = res.data || [];
                if (!entregas.length) {
                    corpo.innerHTML = '<div class="p-6 text-center text-gray-400 text-sm">Nenhum funcionário com este EPI no momento (entregas ativas).</div>';
                    return;
                }
                var html = '<table class="min-w-full divide-y divide-gray-200 text-xs">' +
                    '<thead class="bg-gray-50"><tr>' +
                    '<th class="px-3 py-2 text-left font-bold text-gray-500">Funcionário</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Matrícula</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Cargo</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Variação</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Entrega</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Vencimento</th>' +
                    '<th class="px-3 py-2 text-center font-bold text-gray-500">Assinatura</th>' +
                    '</tr></thead><tbody class="divide-y divide-gray-100">';
                entregas.forEach(function(e) {
                    var vencida = '';
                    if (e.vencimento) {
                        var partes = e.vencimento.split('/');
                        var d = new Date(partes[2], partes[1] - 1, partes[0]);
                        if (d < new Date()) {
                            vencida = ' <span class="px-1 py-0.5 bg-rose-100 text-rose-700 rounded text-[9px] font-bold">Vencida</span>';
                        }
                    }
                    var ass = e.status_assinatura === 'assinada'
                        ? '<span class="px-1 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[9px] font-bold">Assinada</span>'
                        : e.status_assinatura === 'negada'
                            ? '<span class="px-1 py-0.5 bg-rose-100 text-rose-800 rounded text-[9px] font-bold">Recusada</span>'
                            : '<span class="px-1 py-0.5 bg-amber-100 text-amber-800 rounded text-[9px] font-bold">Pendente</span>';
                    html += '<tr>' +
                        '<td class="px-3 py-2 font-bold text-gray-900">' + escFardamento(e.colaborador) + '</td>' +
                        '<td class="px-3 py-2 text-center text-gray-600">' + escFardamento(e.matricula) + '</td>' +
                        '<td class="px-3 py-2 text-center text-gray-600">' + escFardamento(e.cargo) + '</td>' +
                        '<td class="px-3 py-2 text-center text-gray-600">' + (e.variacao ? escFardamento(e.variacao) : '-') + '</td>' +
                        '<td class="px-3 py-2 text-center font-bold text-gray-800">' + e.quantidade + '</td>' +
                        '<td class="px-3 py-2 text-center text-gray-600">' + (e.data_entrega || '-') + '</td>' +
                        '<td class="px-3 py-2 text-center text-gray-600">' + (e.vencimento || '-') + vencida + '</td>' +
                        '<td class="px-3 py-2 text-center">' + ass + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table>';
                corpo.innerHTML = html;
            })
            .catch(() => {
                corpo.innerHTML = '<div class="p-6 text-center text-rose-500 text-sm">Falha ao carregar as entregas deste EPI.</div>';
            });
    };

    window.fecharModalEpiEntregas = function() {
        var modal = document.getElementById('modal-epi-entregas');
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

    // ========== FARDAMENTO (UNIFORMES) ==========

    let fardamentoDadosJs = @json($fardamentoDados);

    window.verFardamentoFuncionarios = function(tipo, tamanho) {
        var modal = document.getElementById('modal-fardamento-funcionarios');
        var lista = document.getElementById('modal-fardamento-func-lista');
        var titulo = document.getElementById('modal-fardamento-func-titulo');
        if (!modal || !lista) return;

        var grupos = (fardamentoDadosJs[tipo] && fardamentoDadosJs[tipo].grupos) || {};
        var info = grupos[tamanho];

        if (!info || !info.funcionarios || info.funcionarios.length === 0) {
            lista.innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-user-slash text-3xl mb-2 text-gray-300"></i><p class="text-sm font-semibold text-gray-500">Nenhum colaborador encontrado com este tamanho.</p></div>';
        } else {
            lista.innerHTML = info.funcionarios.map(function(f, idx) {
                var detalhe = [];
                if (f.cargo) detalhe.push('<i class="fas fa-briefcase mr-1 text-emerald-600"></i>' + window.escFardamento(f.cargo));
                if (f.setor) detalhe.push('<i class="fas fa-th-large mr-1 text-emerald-600"></i>' + window.escFardamento(f.setor));
                if (f.empresa) detalhe.push('<i class="fas fa-building mr-1 text-emerald-600"></i>' + window.escFardamento(f.empresa));
                return '<div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">' +
                    '<div class="flex items-center space-x-3 min-w-0">' +
                    '<div class="w-9 h-9 rounded-full bg-emerald-900 text-amber-400 font-black flex items-center justify-center text-xs shrink-0">' + window.iniciaisFardamento(f.nome) + '</div>' +
                    '<div class="min-w-0">' +
                    '<div class="font-bold text-gray-900 text-sm truncate">' + window.escFardamento(f.nome) + '</div>' +
                    '<div class="text-xs text-gray-500 space-x-3 truncate">' + detalhe.join('') + '</div>' +
                    '</div></div>' +
                    '<span class="text-xs font-black text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full shrink-0 ml-3">' + (tipo === 'bota' ? 'Nº ' : 'Tamanho ') + window.escFardamento(tamanho) + '</span>' +
                    '</div>';
            }).join('');
        }

        titulo.textContent = 'Colaboradores - ' + (tipo === 'camisa' ? 'Camisa' : tipo === 'calca' ? 'Calça' : 'Bota') + ' ' + (tipo === 'bota' ? 'Nº ' : 'Tamanho ') + tamanho + ' (' + (info ? info.funcionarios.length : 0) + ')';

        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.iniciaisFardamento = function(nome) {
        var partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
        var ini = '';
        for (var i = 0; i < partes.length && ini.length < 2; i++) ini += partes[i][0].toUpperCase();
        return ini || 'U';
    };

    window.escFardamento = function(valor) {
        return String(valor == null ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    window.fecharModalFardamentoFuncionarios = function() {
        var modal = document.getElementById('modal-fardamento-funcionarios');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    // ========== LISTA GERAL DE FUNCIONÁRIOS E FARDAMENTO ==========

    let fardamentoFuncionariosLista = @json($fardamentoFuncionarios);

    var opcoesFiltroFardamento = {
        camisa: ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG'],
        calca: ['36', '38', '40', '42', '44', '46', '48', '50', '52'],
        bota: ['33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47']
    };

    window.abrirModalListaFuncionariosFardamento = function() {
        var modal = document.getElementById('modal-lista-funcionarios-fardamento');
        if (!modal) return;

        Object.keys(opcoesFiltroFardamento).forEach(function(tipo) {
            var sel = document.getElementById('filtro-' + tipo + '-funcionario');
            if (sel && sel.options.length === 1) {
                opcoesFiltroFardamento[tipo].forEach(function(v) {
                    var opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = (tipo === 'bota' ? 'Nº ' : '') + v;
                    sel.appendChild(opt);
                });
            }
        });

        window.filtrarListaFuncionariosFardamento();

        modal.classList.remove('hidden');
        modal.style.setProperty('display', 'flex', 'important');
    };

    window.fecharModalListaFuncionariosFardamento = function() {
        var modal = document.getElementById('modal-lista-funcionarios-fardamento');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
        }
    };

    function badgeTamanhoFardamento(valor, tipo) {
        if (!valor) return '<span class="text-gray-300 font-bold">—</span>';
        var prefixo = (tipo === 'bota') ? 'Nº ' : '';
        return '<span class="inline-block px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-black">' + prefixo + window.escFardamento(valor) + '</span>';
    }

    // Filtro da lista de colaboradores (aba Fichas & Histórico)
    window.filtrarListaColaboradores = function() {
        var input = document.getElementById('input-filtro-colaborador');
        var lista = document.getElementById('lista-colaboradores-fichas');
        var vazio = document.getElementById('lista-colaboradores-vazia');
        if (!lista) return;

        var busca = ((input && input.value) || '').trim().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        var linhas = lista.querySelectorAll('.colab-item-row');
        var visiveis = 0;

        linhas.forEach(function(linha) {
            var nomeEl = linha.querySelector('.colab-nome');
            var nome = (nomeEl ? nomeEl.textContent : '').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            var corresponde = !busca || nome.indexOf(busca) !== -1;
            if (corresponde) {
                linha.classList.remove('hidden');
                linha.style.display = '';
            } else {
                linha.classList.add('hidden');
                linha.style.display = 'none';
            }
            if (corresponde) visiveis++;
        });

        if (vazio) vazio.classList.toggle('hidden', visiveis > 0);
    };

    window.filtrarListaFuncionariosFardamento = function() {
        var nome = (document.getElementById('filtro-nome-funcionario').value || '').trim().toLowerCase();
        var camisa = document.getElementById('filtro-camisa-funcionario').value;
        var calca = document.getElementById('filtro-calca-funcionario').value;
        var bota = document.getElementById('filtro-bota-funcionario').value;

        var filtrados = (fardamentoFuncionariosLista || []).filter(function(f) {
            if (nome && !String(f.nome || '').toLowerCase().includes(nome)) return false;
            if (camisa && String(f.camisa || '') !== camisa) return false;
            if (calca && String(f.calca || '') !== calca) return false;
            if (bota && String(f.bota || '') !== bota) return false;
            return true;
        });

        filtrados.sort(function(a, b) {
            return String(a.nome).localeCompare(String(b.nome));
        });

        document.getElementById('contador-funcionarios-fardamento').textContent = filtrados.length;

        var tbody = document.getElementById('tbody-funcionarios-fardamento');
        if (!tbody) return;

        if (filtrados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">' +
                '<i class="fas fa-user-slash text-3xl mb-2 text-gray-300"></i>' +
                '<p class="text-sm font-semibold text-gray-500">Nenhum funcionário encontrado com os filtros selecionados.</p></td></tr>';
            return;
        }

        tbody.innerHTML = filtrados.map(function(f) {
            return '<tr class="hover:bg-gray-50">' +
                '<td class="px-5 py-3">' +
                '<div class="font-bold text-gray-900">' + window.escFardamento(f.nome) + '</div>' +
                '</td>' +
                '<td class="px-4 py-3 text-center">' + badgeTamanhoFardamento(f.camisa) + '</td>' +
                '<td class="px-4 py-3 text-center">' + badgeTamanhoFardamento(f.calca) + '</td>' +
                '<td class="px-4 py-3 text-center">' + badgeTamanhoFardamento(f.bota, 'bota') + '</td>' +
                '<td class="px-4 py-3 text-xs text-gray-600">' +
                (f.cargo ? window.escFardamento(f.cargo) : '—') +
                (f.setor ? ' <span class="text-gray-300">·</span> ' + window.escFardamento(f.setor) : '') +
                '</td>' +
                '</tr>';
        }).join('');
    };

    window.limparFiltrosListaFuncionariosFardamento = function() {
        document.getElementById('filtro-nome-funcionario').value = '';
        document.getElementById('filtro-camisa-funcionario').value = '';
        document.getElementById('filtro-calca-funcionario').value = '';
        document.getElementById('filtro-bota-funcionario').value = '';
        window.filtrarListaFuncionariosFardamento();
    };

    // ========== VARIAÇÕES DE EPI ==========

    window.adicionarVariacao = function(valor) {
        var container = document.getElementById('container-variacoes');
        var div = document.createElement('div');
        div.className = 'flex space-x-2 variacao-row';
        div.innerHTML = '<input type="text" name="variacoes[]" value="' + (valor || '') + '" class="flex-1 text-xs border-gray-300 rounded-lg shadow-sm" placeholder="Ex: Tamanho 38"><button type="button" onclick="removerVariacao(this)" class="text-rose-500 hover:text-rose-700 p-1"><i class="fas fa-times"></i></button>';
        container.appendChild(div);
    };

    window.removerVariacao = function(btn) {
        var row = btn.closest('.variacao-row');
        if (row) row.remove();
    };

    function recalcularTotalVariacoes() {
        var rows = document.querySelectorAll('#estoque-variacoes-rows tr');
        var total = 0;
        rows.forEach(function(row) {
            var input = row.querySelector('input.variacao-qtd');
            if (input) total += parseInt(input.value) || 0;
        });
        document.getElementById('estoque-variacoes-total').textContent = total;
    }

    window.atualizarVariacoesEstoque = function(epiId) {
        var varTable = document.getElementById('estoque-variacoes-table');
        var varRows = document.getElementById('estoque-variacoes-rows');
        var qtdUnicaContainer = document.getElementById('estoque-qtd-unica-container');

        if (!varTable || !varRows || !qtdUnicaContainer) return;

        // Limpar linhas
        varRows.innerHTML = '';

        if (!epiId || typeof variacoesPorEpi === 'undefined') {
            varTable.style.display = 'none';
            qtdUnicaContainer.style.display = 'block';
            return;
        }

        var variacoes = variacoesPorEpi[epiId];

        if (!Array.isArray(variacoes) || variacoes.length === 0) {
            // EPI sem variações: mostrar quantidade única
            varTable.style.display = 'none';
            qtdUnicaContainer.style.display = 'block';
            return;
        }

        // EPI com variações: mostrar tabela de variações
        varTable.style.display = 'block';
        qtdUnicaContainer.style.display = 'none';

        variacoes.forEach(function(v) {
            var tr = document.createElement('tr');
            tr.className = 'border-b border-amber-100';
            tr.innerHTML = '<td class="py-1.5 pr-2 font-semibold text-gray-800">' + v.ss_ev_tx_nome + '</td>' +
                '<td class="text-center py-1.5 px-2"><input type="number" name="variacoes[' + v.ss_ev_nb_id + '][qtd]" class="variacao-qtd w-20 text-center text-xs border-amber-300 rounded-lg shadow-sm" min="0" value="0" onchange="recalcularTotalVariacoes()" onkeyup="recalcularTotalVariacoes()"></td>';
            tr.querySelector('input').dataset.varNome = v.ss_ev_tx_nome;
            varRows.appendChild(tr);
        });

        recalcularTotalVariacoes();
    };

    // ===== CONTROLE DE ESTOQUE (ABA 3) =====
    let filtroEstoque = { busca: '', status: 'todos', limiar: 5, ordem: 'nome' };
    let filtroMovimentacoes = { tipo: 'todos', periodo: 0, busca: '' };

    function setTextConteudo(id, valor) {
        var el = document.getElementById(id);
        if (el) el.textContent = valor;
    }

    function statusItemEstoque(item) {
        if (item.saldo_rede <= 0) return 'esgotado';
        if (item.saldo_rede <= filtroEstoque.limiar) return 'baixo';
        return 'ok';
    }

    function setFiltroStatusEstoque(status) {
        filtroEstoque.status = status;
        [
            ['filtro-estoque-todos', 'todos'],
            ['filtro-estoque-ok', 'ok'],
            ['filtro-estoque-baixo', 'baixo'],
            ['filtro-estoque-esgotado', 'esgotado']
        ].forEach(function(par) {
            var btn = document.getElementById(par[0]);
            if (!btn) return;
            var ativo = par[1] === status;
            btn.className = ativo
                ? 'px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-emerald-900 text-white'
                : 'px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200';
        });
        renderizarControleEstoque();
    }

    function renderizarControleEstoque() {
        var corpo = document.getElementById('estoque-linhas');
        if (!corpo) return;
        var dados = Array.isArray(matrizEstoqueApi) ? matrizEstoqueApi : [];
        var busca = filtroEstoque.busca.trim().toLowerCase();

        // KPIs
        var saldoRede = 0, saldoFilial = 0, comSaldo = 0, esgotados = 0, baixos = 0;
        dados.forEach(function(i) {
            saldoRede += i.saldo_rede || 0;
            saldoFilial += i.saldo_atual || 0;
            if ((i.saldo_rede || 0) <= 0) {
                esgotados++;
            } else {
                comSaldo++;
                if ((i.saldo_rede || 0) <= filtroEstoque.limiar) baixos++;
            }
        });
        setTextConteudo('kpi-saldo-rede', saldoRede);
        setTextConteudo('kpi-saldo-filial', saldoFilial);
        setTextConteudo('kpi-com-saldo', comSaldo);
        setTextConteudo('kpi-esgotados', esgotados);

        var selFilial = document.getElementById('select-filial-global');
        var nomeFilial = (selFilial && selFilial.options && selFilial.options[selFilial.selectedIndex])
            ? selFilial.options[selFilial.selectedIndex].text : '';
        setTextConteudo('kpi-filial-nome', nomeFilial);

        var chipBaixo = document.getElementById('filtro-estoque-baixo');
        if (chipBaixo) chipBaixo.textContent = 'Baixo estoque (' + baixos + ')';

        // Filtros
        var filtrados = dados.filter(function(i) {
            if (busca) {
                var alvo = ((i.item || '') + ' ' + (i.grupo || '') + ' ' + (i.subgrupo || '') + ' ' + (i.ca || '')).toLowerCase();
                if (alvo.indexOf(busca) === -1) return false;
            }
            var st = statusItemEstoque(i);
            if (filtroEstoque.status !== 'todos' && st !== filtroEstoque.status) return false;
            return true;
        });

        // Ordenação
        if (filtroEstoque.ordem === 'saldo-asc') {
            filtrados.sort(function(a, b) { return (a.saldo_rede || 0) - (b.saldo_rede || 0); });
        } else if (filtroEstoque.ordem === 'saldo-desc') {
            filtrados.sort(function(a, b) { return (b.saldo_rede || 0) - (a.saldo_rede || 0); });
        } else {
            filtrados.sort(function(a, b) { return (a.item || '').localeCompare(b.item || ''); });
        }

        var maxSaldo = 1;
        dados.forEach(function(i) { if ((i.saldo_rede || 0) > maxSaldo) maxSaldo = i.saldo_rede; });

        var html = '';
        filtrados.forEach(function(i) {
            var st = statusItemEstoque(i);
            var stCfg = {
                ok: ['bg-emerald-100 text-emerald-800', 'Em estoque'],
                baixo: ['bg-amber-100 text-amber-800', 'Estoque baixo'],
                esgotado: ['bg-rose-100 text-rose-800', 'Esgotado']
            }[st];
            var corBarra = st === 'ok' ? 'bg-emerald-500' : (st === 'baixo' ? 'bg-amber-400' : 'bg-rose-400');
            var pct = Math.max(3, Math.round((i.saldo_rede || 0) / maxSaldo * 100));

            var chipsVars = (i.variacoes && i.variacoes.length)
                ? i.variacoes.map(function(v) {
                    var cor = (v.saldo_rede || 0) <= 0 ? 'bg-gray-100 text-gray-400' : 'bg-gray-100 text-gray-800';
                    return '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold ' + cor + '" title="Saldo: ' + (v.saldo_rede || 0) + '">' + (v.nome || '-') + ' <strong>(' + (v.saldo_rede || 0) + ')</strong></span>';
                  }).join(' ')
                : '<span class="text-gray-400 text-xs">-</span>';

            var outras = (i.outras_filiais && i.outras_filiais.length)
                ? i.outras_filiais.map(function(o) {
                    return '<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">' + (o.filial_nome || '') + '</span><strong>' + o.saldo + '</strong></div>';
                  }).join('')
                : '<p class="text-xs text-gray-400">Sem saldo em outras filiais.</p>';

            var detId = 'estoque-det-' + i.id;
            html += '<tr class="hover:bg-gray-50 border-b border-gray-100">'
                + '<td class="px-4 py-3">'
                +   '<button type="button" onclick="toggleDetalheEstoque(' + i.id + ')" class="text-gray-400 hover:text-gray-700 mr-1 cursor-pointer" title="Ver distribuição por filial"><i class="fas fa-chevron-down text-[10px]"></i></button>'
                +   '<span class="font-bold text-emerald-950">' + (i.item || 'EPI #' + i.id) + '</span>'
                +   '<div class="text-[10px] text-gray-400 uppercase">' + (i.grupo || '') + (i.subgrupo ? ' / ' + i.subgrupo : '') + '</div>'
                + '</td>'
                + '<td class="px-4 py-3"><div class="flex flex-wrap gap-1 justify-center">' + chipsVars + '</div></td>'
                + '<td class="px-4 py-3">'
                +   '<div class="flex items-center gap-2">'
                +     '<div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full ' + corBarra + '" style="width:' + pct + '%"></div></div>'
                +     '<strong class="text-sm ' + (st === 'esgotado' ? 'text-rose-600' : 'text-gray-900') + '">' + (i.saldo_rede || 0) + '</strong>'
                +   '</div>'
                + '</td>'
                + '<td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded text-[10px] font-bold ' + stCfg[0] + '">' + stCfg[1] + '</span></td>'
                + '<td class="px-4 py-3 text-center text-xs font-semibold ' + ((i.saldo_atual || 0) <= 0 ? 'text-gray-400' : 'text-gray-800') + '">' + (i.saldo_atual || 0) + '</td>'
                + '<td class="px-4 py-3 text-center">'
                +   '<button type="button" onclick="abrirModalNovaEntradaEstoque(' + i.id + ')" class="px-2 py-1 text-[10px] font-bold bg-gray-800 text-white rounded hover:bg-gray-900 cursor-pointer"><i class="fas fa-plus mr-0.5"></i> Lançamento</button>'
                + '</td>'
                + '</tr>'
                + '<tr id="' + detId + '" class="hidden bg-gray-50/70 border-b border-gray-100">'
                +   '<td colspan="6" class="px-6 py-3">'
                +     '<div class="grid md:grid-cols-2 gap-4">'
                +       '<div><p class="text-[10px] font-bold text-gray-500 uppercase mb-1"><i class="fas fa-building mr-1"></i>Distribuição por filial</p>' + outras + '</div>'
                +       '<div><p class="text-[10px] font-bold text-gray-500 uppercase mb-1"><i class="fas fa-info-circle mr-1"></i>Resumo do item</p>'
                +         '<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">Saldo na filial selecionada</span><strong>' + (i.saldo_atual || 0) + '</strong></div>'
                +         '<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">Saldo total (rede)</span><strong>' + (i.saldo_rede || 0) + '</strong></div>'
                +         '<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">CA</span><strong>' + (i.ca || '-') + '</strong></div>'
                +         '<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">Vida útil</span><strong>' + (i.vida_util_dias || 0) + ' dias</strong></div>'
                +       '</div>'
                +     '</div>'
                +   '</td>'
                + '</tr>';
        });

        corpo.innerHTML = html;
        setTextConteudo('estoque-contagem', 'Exibindo ' + filtrados.length + ' de ' + dados.length + ' itens');
        var vazio = document.getElementById('estoque-vazio');
        if (vazio) vazio.classList.toggle('hidden', filtrados.length > 0);
    }

    function toggleDetalheEstoque(id) {
        var tr = document.getElementById('estoque-det-' + id);
        if (tr) tr.classList.toggle('hidden');
    }

    // ===== FILTROS DAS MOVIMENTAÇÕES =====
    function setFiltroTipoMovimentacao(tipo) {
        filtroMovimentacoes.tipo = tipo;
        [
            ['filtro-mov-todos', 'todos'],
            ['filtro-mov-entrada', 'entrada'],
            ['filtro-mov-saida', 'saida'],
            ['filtro-mov-devolucao', 'devolucao'],
            ['filtro-mov-substituicao', 'substituicao']
        ].forEach(function(par) {
            var btn = document.getElementById(par[0]);
            if (!btn) return;
            var ativo = par[1] === tipo;
            btn.className = ativo
                ? 'px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-emerald-900 text-white'
                : 'px-3 py-1.5 text-xs font-bold rounded-full cursor-pointer bg-gray-100 text-gray-600 hover:bg-gray-200';
        });
        aplicarFiltroMovimentacoes();
    }

    function aplicarFiltroMovimentacoes() {
        var tabela = document.getElementById('tabela-movimentacoes');
        if (!tabela) return;
        var rows = tabela.querySelectorAll('tbody tr');
        var busca = filtroMovimentacoes.busca.trim().toLowerCase();
        var agora = Date.now();
        var entradas = 0, saidas = 0, visiveis = 0;

        rows.forEach(function(tr) {
            if (!tr.dataset.tipo) return;
            var ok = true;
            if (filtroMovimentacoes.tipo !== 'todos' && tr.dataset.tipo !== filtroMovimentacoes.tipo) ok = false;
            if (ok && filtroMovimentacoes.periodo > 0 && (agora - (parseInt(tr.dataset.ts) || 0)) > filtroMovimentacoes.periodo * 86400000) ok = false;
            if (ok && busca && (tr.dataset.busca || '').indexOf(busca) === -1) ok = false;
            tr.classList.toggle('hidden', !ok);
            if (ok) {
                visiveis++;
                var q = parseInt(tr.dataset.qtd) || 0;
                if (tr.dataset.tipo === 'entrada' || tr.dataset.tipo === 'devolucao') {
                    entradas += q;
                } else {
                    saidas += q;
                }
            }
        });

        setTextConteudo('mov-total-entradas', entradas);
        setTextConteudo('mov-total-saidas', saidas);
        setTextConteudo('mov-contagem', visiveis);
        var vazio = document.getElementById('mov-vazio');
        if (vazio) vazio.classList.toggle('hidden', visiveis > 0);
        var semRegistro = document.getElementById('mov-sem-registro');
        if (semRegistro) semRegistro.classList.toggle('hidden', rows.length > 1);
    }
</script>
@endsection
