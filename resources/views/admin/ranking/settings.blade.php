@extends('layout')

@section('title', 'Configurações de Elite')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Cabeçalho com Título e Ação de Recálculo -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold text-gray-800">⚙️ Parâmetros de Elite</h1>
            <p class="text-gray-600 mt-2">Gerencie os pesos e critérios que definem a pontuação automática dos colaboradores.</p>
        </div>
        <form action="{{ route('admin.ranking.recalculate') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-900 text-white px-6 py-3 rounded-xl hover:bg-blue-800 transition shadow-lg font-bold">
                <i class="fas fa-sync-alt mr-2"></i>Recalcular Dados Históricos
            </button>
        </form>
    </div>

    <!-- Configurações Gerais do Sistema -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mb-10">
        <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Configurações Gerais</h2>
        <form method="POST" action="{{ route('admin.ranking.settings.update') }}" class="grid gap-6 md:grid-cols-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Status do Ranking</label>
                <select name="is_active" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-900">
                    <option value="1" {{ $settings->is_active ? 'selected' : '' }}>✓ Ativo (Visível para todos)</option>
                    <option value="0" {{ !$settings->is_active ? 'selected' : '' }}>✗ Inativo (Oculto)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Visualização Padrão</label>
                <select name="default_period" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-900">
                    <option value="monthly" {{ $settings->default_period === 'monthly' ? 'selected' : '' }}>Mensal (Consolidado)</option>
                    <option value="content" {{ $settings->default_period === 'content' ? 'selected' : '' }}>Por Conteúdo (Individual)</option>
                </select>
            </div>
            <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold">
                Salvar Mudanças Globais
            </button>
        </form>
    </div>

    <!-- Listagem de Critérios e Regras -->
    <div class="grid lg:grid-cols-1 gap-8">
        @foreach($criteria as $criterion)
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex flex-col">
                <!-- Cabeçalho do Card de Critério -->
                <div class="p-6 bg-gradient-to-br from-slate-50 to-gray-50 border-b flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800">{{ $criterion->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $criterion->description }}</p>
                    </div>
                    
                    <!-- Badge de Unidade Identificadora -->
                    <div class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full flex items-center gap-2">
                        <i class="fas fa-ruler-combined"></i>
                        <span class="text-xs font-black uppercase tracking-wider">
                            Unidade: 
                            @if($criterion->slug === 'start_time') Horas Decimais
                            @elseif($criterion->slug === 'completion_time') Dias de Calendário
                            @else Tentativas na Prova
                            @endif
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Tabela de Regras Existentes -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-400 border-b">
                                    <th class="pb-3 font-semibold">Identificação da Regra</th>
                                    <th class="pb-3 font-semibold text-center">Faixa de Valor</th>
                                    <th class="pb-3 font-semibold text-center">Pontuação</th>
                                    <th class="pb-3 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($criterion->rules->sortBy('sort_order') as $rule)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-4">
                                            <span class="font-bold text-slate-700 block">{{ $rule->label }}</span>
                                            <span class="text-xs text-gray-400">Ordem: {{ $rule->sort_order }}</span>
                                        </td>
                                        <td class="py-4 text-center text-gray-600">
                                            {{ $rule->min_value }} a {{ $rule->max_value >= 9999 ? '∞' : $rule->max_value }}
                                        </td>
                                        <td class="py-4 text-center">
                                            <span class="inline-block px-3 py-1 bg-green-50 text-green-700 font-black rounded-lg border border-green-200">
                                                +{{ (int)$rule->points }} pts
                                            </span>
                                        </td>
                                        <td class="py-4 text-right space-x-2">
                                            <button 
                                                onclick="openEditModal({{ json_encode($rule) }})"
                                                class="text-blue-600 hover:text-blue-800 p-2" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.ranking.rules.destroy', $rule) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 p-2" onclick="return confirm('Excluir esta regra permanentemente?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Rodapé do Card: Adicionar Nova Faixa -->
                    <div class="mt-6 bg-gray-50 rounded-xl p-4 border border-dashed border-gray-300">
                        <h3 class="text-sm font-bold text-gray-700 mb-3">Nova Regra para {{ $criterion->name }}</h3>
                        <form method="POST" action="{{ route('admin.ranking.rules.store', $criterion) }}" class="grid gap-3 md:grid-cols-5 items-end">
                            @csrf
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Rótulo/Nome</label>
                                <input type="text" name="label" class="w-full border-gray-300 rounded px-3 py-1.5 text-sm" placeholder="Ex: Rápido">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Mín.</label>
                                <input type="number" step="0.0001" name="min_value" class="w-full border-gray-300 rounded px-3 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Máx.</label>
                                <input type="number" step="0.0001" name="max_value" class="w-full border-gray-300 rounded px-3 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1">Pontos</label>
                                <input type="number" name="points" class="w-full border-gray-300 rounded px-3 py-1.5 text-sm" required>
                            </div>
                            <button type="submit" class="bg-blue-900 text-white rounded px-4 py-2 text-sm font-bold hover:bg-blue-800 transition">
                                <i class="fas fa-plus mr-1"></i>Adicionar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Bloco Informativo Estratégico -->
    <div class="mt-12 bg-amber-50 rounded-2xl p-8 border-l-8 border-amber-400 shadow-sm">
        <div class="flex gap-6">
            <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt text-amber-600 text-xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-amber-900">Estratégia de Lançamento (Segunda-feira 08:30)</h3>
                <p class="text-amber-800 leading-relaxed mt-2">
                    O sistema utiliza a <strong>Data de Liberação</strong> do treinamento como o ponto de partida. 
                    Para que a pontuação de Elite (0 a 1 hora) seja calculada corretamente, garanta que os treinamentos de segunda-feira estejam configurados para liberar exatamente às <strong>08:30</strong>. 
                    Quem iniciar o conteúdo até às 09:30 cairá na regra máxima de pontuação, criando um diferencial competitivo real no ranking.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição de Regra -->
<div id="edit-rule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Editar Regra</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="edit-rule-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Rótulo da Regra</label>
                <input type="text" name="label" id="edit_label" class="w-full border-gray-300 rounded-lg shadow-sm" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Valor Mínimo</label>
                    <input type="number" step="0.0001" name="min_value" id="edit_min_value" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Valor Máximo</label>
                    <input type="number" step="0.0001" name="max_value" id="edit_max_value" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Pontuação</label>
                    <input type="number" name="points" id="edit_points" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Ordem</label>
                    <input type="number" name="sort_order" id="edit_sort_order" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-blue-900 text-white rounded-xl font-bold hover:bg-blue-800 transition shadow-lg">
                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    /**
     * Função para abrir o modal de edição e preencher os dados
     */
    function openEditModal(rule) {
        const modal = document.getElementById('edit-rule-modal');
        const form = document.getElementById('edit-rule-form');
        
        // Define a rota de destino com o ID da regra
        form.action = `/admin/ranking/regras/${rule.id}`;
        
        // Preenche os inputs
        document.getElementById('edit_label').value = rule.label;
        document.getElementById('edit_min_value').value = rule.min_value;
        document.getElementById('edit_max_value').value = rule.max_value;
        document.getElementById('edit_points').value = rule.points;
        document.getElementById('edit_sort_order').value = rule.sort_order;

        // Exibe o modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('edit-rule-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
