@extends('layout')

@section('title', 'Cronograma de Conteúdo')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-900 mr-2"></i>Cronograma de Conteúdo
            </h1>
            <p class="text-gray-500 mt-1">
                Acompanhe os conteúdos já liberados e programe os próximos. O status é verificado automaticamente contra os treinamentos cadastrados.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-800">{{ $resumo['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-slate-100">
            <p class="text-sm text-gray-500">Listados</p>
            <p class="text-2xl font-bold text-slate-600">{{ $resumo['listados'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-yellow-100">
            <p class="text-sm text-gray-500">Previsões</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $resumo['previsoes'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-blue-100">
            <p class="text-sm text-gray-500">Programados</p>
            <p class="text-2xl font-bold text-blue-600">{{ $resumo['programados'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-green-100">
            <p class="text-sm text-gray-500">Realizados</p>
            <p class="text-2xl font-bold text-green-600">{{ $resumo['realizados'] }}</p>
        </div>
    </div>

    {{-- Formulário de cadastro --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-plus mr-2 text-blue-900"></i>Novo conteúdo</h2>
        <form method="POST" action="{{ route('admin.cronograma.store') }}" class="grid md:grid-cols-4 gap-3 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Nome do conteúdo *</label>
                <input type="text" name="nome" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: DSS 28 - Segurança na Operação">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Data prevista de liberação</label>
                <input type="date" name="data_prevista_liberacao" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 transition text-sm">
                    <i class="fas fa-save mr-1"></i>Adicionar
                </button>
            </div>
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Observações</label>
                <textarea name="observacoes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Observações (opcional)"></textarea>
            </div>
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Materiais prontos</label>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="flex items-center gap-1"><input type="checkbox" name="pptx_ok" value="1" class="rounded"> PPTX</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="pdf_ok" value="1" class="rounded"> PDF</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="video_ok" value="1" class="rounded"> Vídeo</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="youtube_ok" value="1" class="rounded"> YouTube</label>
                </div>
            </div>
        </form>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.cronograma.index') }}" class="bg-white rounded-2xl shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Busca (nome)</label>
            <input type="text" name="busca" value="{{ request('busca') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: DSS 28">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach(['Listado','Previsão','Programado','Realizado'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 transition text-sm">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            <a href="{{ route('admin.cronograma.index') }}" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition text-sm">
                <i class="fas fa-redo mr-1"></i>Limpar
            </a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Nome do conteúdo</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Data prevista</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Materiais</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Treinamento vinculado</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itens as $item)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-sm text-gray-800 font-semibold">{{ $item->nome }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $item->data_prevista_liberacao?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $item->statusColor() }} px-3 py-1 rounded-full text-xs font-semibold">{{ $item->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                <div class="flex flex-wrap gap-1 justify-center">
                                    <span class="{{ $item->pptx_ok ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400' }} px-2 py-0.5 rounded">PPTX</span>
                                    <span class="{{ $item->pdf_ok ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400' }} px-2 py-0.5 rounded">PDF</span>
                                    <span class="{{ $item->video_ok ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400' }} px-2 py-0.5 rounded">Vídeo</span>
                                    <span class="{{ $item->youtube_ok ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400' }} px-2 py-0.5 rounded">YT</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @php $tr = $item->matchedTraining(); @endphp
                                @if($tr)
                                    <a href="{{ route('treinamentos.show', $tr) }}" class="text-blue-700 hover:underline">{{ $tr->titulo }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" onclick="toggleEdit({{ $item->id }})" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mr-2">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </button>
                                <form method="POST" action="{{ route('admin.cronograma.destroy', $item) }}" class="inline" onsubmit="return confirm('Remover este conteúdo do cronograma?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                        <i class="fas fa-trash mr-1"></i>Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-row-{{ $item->id }}" class="hidden bg-gray-50">
                            <td colspan="6" class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.cronograma.update', $item) }}" class="grid md:grid-cols-4 gap-3 items-end">
                                    @csrf
                                    @method('PUT')
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Nome do conteúdo *</label>
                                        <input type="text" name="nome" value="{{ $item->nome }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Data prevista de liberação</label>
                                        <input type="date" name="data_prevista_liberacao" value="{{ $item->data_prevista_liberacao?->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition text-sm">
                                            <i class="fas fa-save mr-1"></i>Salvar
                                        </button>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Observações</label>
                                        <textarea name="observacoes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $item->observacoes }}</textarea>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-semibold text-gray-500 mb-2">Materiais prontos</label>
                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <label class="flex items-center gap-1"><input type="checkbox" name="pptx_ok" value="1" @checked($item->pptx_ok) class="rounded"> PPTX</label>
                                            <label class="flex items-center gap-1"><input type="checkbox" name="pdf_ok" value="1" @checked($item->pdf_ok) class="rounded"> PDF</label>
                                            <label class="flex items-center gap-1"><input type="checkbox" name="video_ok" value="1" @checked($item->video_ok) class="rounded"> Vídeo</label>
                                            <label class="flex items-center gap-1"><input type="checkbox" name="youtube_ok" value="1" @checked($item->youtube_ok) class="rounded"> YouTube</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="training_id" value="{{ $item->training_id }}">
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                <i class="fas fa-calendar-alt text-3xl text-gray-300 mb-2"></i>
                                <p>Nenhum conteúdo cadastrado no cronograma.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($itens->hasPages())
        <div class="mt-6">{{ $itens->links() }}</div>
    @endif
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-row-' + id);
    if (row) row.classList.toggle('hidden');
}
</script>
@endsection