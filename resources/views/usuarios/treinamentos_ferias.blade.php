@extends('layout')

@section('title', 'Isenções de Treinamento por Férias')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-calendar-minus text-indigo-600 mr-3"></i>Isenções de Treinamento
            </h1>
            <p class="text-gray-600 mt-1">
                Gerencie isenções de treinamento por período de férias para
                <strong>{{ $usuario->nome }}</strong>
                ({{ $usuario->getCpfFormatted() }})
            </p>
        </div>
        <a href="{{ route('usuarios.show', $usuario) }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-indigo-900">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Como funciona:</strong> Ao isentar um treinamento por férias, ele não aparecerá nas listas de
            "pendentes" e "não iniciados" nos relatórios, mesmo que o usuário tenha iniciado o conteúdo mas não tenha
            concluído.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Tipo</th>
                        <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Isento?</th>
                        <th class="px-4 py-3 text-center font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lista as $item)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $item->training->titulo }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                {{ $item->training->tipo ? ucfirst($item->training->tipo) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_progresso)
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-1 max-w-[80px] mx-auto">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->porcentagem }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ $item->porcentagem }}%</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->status === 'concluido')
                                    <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                                @elseif($item->status === 'pendente')
                                    <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                                @else
                                    <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm font-semibold">○ Não iniciado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_isencao)
                                    <span class="bg-indigo-100 text-indigo-900 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas fa-check mr-1"></i>Isento
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $item->isencao->data_inicio->format('d/m/Y') }} a {{ $item->isencao->data_fim->format('d/m/Y') }}
                                    </div>
                                    @if($item->isencao->motivo)
                                        <div class="text-xs text-gray-500 mt-0.5 italic">{{ $item->isencao->motivo }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_isencao)
                                    <form action="{{ route('usuarios.treinamentos_ferias.destroy', [$usuario, $item->isencao]) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta isenção?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-700 px-3 py-1 rounded-lg hover:bg-red-200 transition text-sm font-semibold">
                                            <i class="fas fa-trash mr-1"></i>Remover
                                        </button>
                                    </form>
                                @else
                                    <button onclick="abrirModalIsencao({{ $item->training->id }}, '{{ addslashes($item->training->titulo) }}')" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                                        <i class="fas fa-calendar-minus mr-1"></i>Isentar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                                <p>Nenhum treinamento encontrado para este usuário</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Isenção -->
<div id="modalIsencao" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-calendar-minus text-indigo-600 mr-2"></i>Isentar Treinamento
                </h3>
                <button onclick="fecharModalIsencao()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form action="{{ route('usuarios.treinamentos_ferias.store', $usuario) }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <input type="hidden" name="training_id" id="modal_training_id">

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Treinamento:</p>
                    <p class="text-gray-800 font-semibold" id="modal_training_nome"></p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-1">Início das férias</label>
                        <input type="date" name="data_inicio" id="data_inicio" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-1">Fim das férias</label>
                        <input type="date" name="data_fim" id="data_fim" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-1">Motivo (opcional)</label>
                    <input type="text" name="motivo" id="motivo" maxlength="500"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ex: Período de férias programado">
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="fecharModalIsencao()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-check mr-2"></i>Confirmar Isenção
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalIsencao(trainingId, trainingNome) {
    document.getElementById('modal_training_id').value = trainingId;
    document.getElementById('modal_training_nome').textContent = trainingNome;
    document.getElementById('modalIsencao').classList.remove('hidden');
}

function fecharModalIsencao() {
    document.getElementById('modalIsencao').classList.add('hidden');
}

document.getElementById('modalIsencao').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalIsencao();
    }
});
</script>
@endsection
