@extends('layout')

@section('title', 'Auditoria')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-clipboard-list text-emerald-700 mr-2"></i> Auditoria
            </h1>
            <p class="text-gray-500 mt-1">Trilha de acessos e ações: quem fez, o quê, quando e de onde.</p>
        </div>
        <a href="{{ route('auditoria.export', request()->query()) }}"
           class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-700 text-white font-semibold hover:bg-emerald-800 transition">
            <i class="fas fa-file-csv mr-2"></i> Exportar CSV
        </a>
    </div>

    <form method="GET" action="{{ route('auditoria.index') }}"
          class="bg-white rounded-2xl shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Busca (descrição, rota, IP)</label>
            <input type="text" name="busca" value="{{ request('busca') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: João, usuarios, 191...">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Usuário</label>
            <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((int) request('user_id') === $usuario->id)>{{ $usuario->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Evento</label>
            <select name="event" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($eventos as $evento)
                    <option value="{{ $evento }}" @selected(request('event') === $evento)>{{ $evento }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Módulo</label>
            <select name="module" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($modulos as $modulo)
                    <option value="{{ $modulo }}" @selected(request('module') === $modulo)>{{ $modulo }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2 md:col-span-2">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">De</label>
                <input type="date" name="inicio" value="{{ request('inicio') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Até</label>
                <input type="date" name="fim" value="{{ request('fim') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex items-end gap-2 md:col-span-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 transition">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            <a href="{{ route('auditoria.index') }}" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition">
                Limpar
            </a>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Data/Hora</th>
                        <th class="px-4 py-3 text-left">Usuário</th>
                        <th class="px-4 py-3 text-left">Evento</th>
                        <th class="px-4 py-3 text-left">Módulo</th>
                        <th class="px-4 py-3 text-left">Descrição</th>
                        <th class="px-4 py-3 text-left">IP</th>
                        <th class="px-4 py-3 text-left">Detalhes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $badge = match ($log->event) {
                                'created' => 'bg-emerald-100 text-emerald-800',
                                'updated' => 'bg-amber-100 text-amber-800',
                                'deleted' => 'bg-red-100 text-red-800',
                                'login' => 'bg-blue-100 text-blue-800',
                                'login_failed', 'login_blocked' => 'bg-rose-100 text-rose-800',
                                'logout' => 'bg-gray-100 text-gray-700',
                                'downloaded', 'exported' => 'bg-indigo-100 text-indigo-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 text-gray-800">
                                {{ $log->user?->nome ?? '—' }}
                                @if($log->user_id)
                                    <span class="block text-xs text-gray-400">#{{ $log->user_id }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                                    {{ $log->evento_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $log->modulo_label }}</td>
                            <td class="px-4 py-3 text-gray-700 max-w-md">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs whitespace-nowrap">{{ $log->ip }}</td>
                            <td class="px-4 py-3">
                                @if($log->old_values || $log->new_values)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-blue-700 hover:underline">ver</summary>
                                        @if($log->old_values)
                                            <p class="mt-2 font-semibold text-gray-500">Antes</p>
                                            <pre class="bg-gray-50 border border-gray-200 rounded p-2 overflow-x-auto max-w-md">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                        @if($log->new_values)
                                            <p class="mt-2 font-semibold text-gray-500">Depois</p>
                                            <pre class="bg-gray-50 border border-gray-200 rounded p-2 overflow-x-auto max-w-md">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </details>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                                Nenhum registro de auditoria encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        Exibindo {{ $logs->count() }} de {{ $logs->total() }} registros. A auditoria não registra senhas ou tokens (valores mascarados).
    </p>
</div>
@endsection
