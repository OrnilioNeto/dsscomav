@extends('layout')

@section('title', 'Auditoria — Folgas')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-history text-gray-600 mr-2"></i>Auditoria — Folgas
        </h1>
        <a href="{{ route('admin.folgas.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Voltar ao Painel
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="mb-6">
        <div class="flex flex-wrap items-end gap-3 bg-white rounded-xl shadow p-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Motorista</label>
                <select name="user_id" class="border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    @foreach($motoristas as $m)
                        <option value="{{ $m->id }}" {{ request('user_id') == $m->id ? 'selected' : '' }}>{{ $m->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Ação</label>
                <select name="acao" class="border-gray-300 rounded-lg text-sm">
                    <option value="">Todas</option>
                    <option value="criar_dia" {{ request('acao') == 'criar_dia' ? 'selected' : '' }}>Criar Dia</option>
                    <option value="editar_dia" {{ request('acao') == 'editar_dia' ? 'selected' : '' }}>Editar Dia</option>
                    <option value="excluir_dia" {{ request('acao') == 'excluir_dia' ? 'selected' : '' }}>Excluir Dia</option>
                    <option value="criar_movimento" {{ request('acao') == 'criar_movimento' ? 'selected' : '' }}>Criar Movimento</option>
                    <option value="estorno_movimento" {{ request('acao') == 'estorno_movimento' ? 'selected' : '' }}>Estorno</option>
                    <option value="importar_csv" {{ request('acao') == 'importar_csv' ? 'selected' : '' }}>Importar CSV</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">De</label>
                <input type="date" name="de" value="{{ request('de') }}" class="border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Até</label>
                <input type="date" name="ate" value="{{ request('ate') }}" class="border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            <a href="{{ route('admin.folgas.auditoria') }}" class="text-gray-500 hover:text-gray-700 text-sm">Limpar</a>
        </div>
    </form>

    {{-- Tabela de Logs --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto table-responsive">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="p-3 text-left font-bold text-gray-700">Data/Hora</th>
                        <th class="p-3 text-left font-bold text-gray-700">Motorista</th>
                        <th class="p-3 text-left font-bold text-gray-700">Ação</th>
                        <th class="p-3 text-left font-bold text-gray-700">Tabela</th>
                        <th class="p-3 text-left font-bold text-gray-700">Registro</th>
                        <th class="p-3 text-left font-bold text-gray-700">Realizado por</th>
                        <th class="p-3 text-left font-bold text-gray-700">Dados</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="p-3">
                                <span class="font-semibold">{{ $log->user->nome ?? 'N/A' }}</span>
                            </td>
                            <td class="p-3">
                                @php
                                    $acaoLabel = match($log->acao) {
                                        'criar_dia' => ['Criação', 'bg-green-100 text-green-700'],
                                        'editar_dia' => ['Edição', 'bg-blue-100 text-blue-700'],
                                        'excluir_dia' => ['Exclusão', 'bg-red-100 text-red-700'],
                                        'criar_movimento' => ['Movimento', 'bg-yellow-100 text-yellow-700'],
                                        'estorno_movimento' => ['Estorno', 'bg-orange-100 text-orange-700'],
                                        'importar_csv' => ['Importação', 'bg-purple-100 text-purple-700'],
                                        default => [$log->acao, 'bg-gray-100 text-gray-700'],
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $acaoLabel[1] }}">{{ $acaoLabel[0] }}</span>
                            </td>
                            <td class="p-3 text-xs text-gray-500">{{ $log->tabela ?? '—' }}</td>
                            <td class="p-3 text-xs text-gray-500">{{ $log->registro_id ?? '—' }}</td>
                            <td class="p-3 text-xs text-gray-500">{{ $log->creator->nome ?? 'Sistema' }}</td>
                            <td class="p-3">
                                @if($log->dados_depois)
                                    <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-blue-600 hover:text-blue-800 text-xs">
                                        <i class="fas fa-eye mr-1"></i>Ver
                                    </button>
                                    <pre class="hidden mt-1 bg-gray-50 rounded p-2 text-xs max-w-xs overflow-auto">{{ json_encode($log->dados_depois, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400">
                                <i class="fas fa-history text-3xl mb-2"></i><br>Nenhum registro de auditoria encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $logs->links() }}
        </div>
    </div>

</div>
@endsection
