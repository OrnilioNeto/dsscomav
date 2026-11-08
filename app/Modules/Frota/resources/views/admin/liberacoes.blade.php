@extends('layout')

@section('title', 'Liberações de Treinamento')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-unlock-keyhole text-blue-900 mr-2"></i>Liberações de Treinamento
            </h1>
            <p class="text-gray-500 mt-1">Histórico de liberações manuais com responsável, validade e motivo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.frota.liberacoes.exportar', request()->only(['busca', 'situacao'])) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="{{ route('admin.frota.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800">
                <i class="fas fa-arrow-left"></i> Motoristas
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-blue-100">
            <p class="text-sm text-gray-500">Vigentes</p>
            <p class="text-2xl font-bold text-blue-600">{{ $resumo['vigentes'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-800">{{ $resumo['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-red-100">
            <p class="text-sm text-gray-500">Revogadas</p>
            <p class="text-2xl font-bold text-red-600">{{ $resumo['revogadas'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.frota.liberacoes.index') }}" class="flex flex-col md:flex-row gap-2">
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Nome ou CPF do motorista"
                       class="w-full md:max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <select name="situacao" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todas as situações</option>
                    <option value="vigente" @selected(request('situacao') === 'vigente')>Vigentes</option>
                    <option value="agendada" @selected(request('situacao') === 'agendada')>Agendadas</option>
                    <option value="expirada" @selected(request('situacao') === 'expirada')>Expiradas</option>
                    <option value="revogada" @selected(request('situacao') === 'revogada')>Revogadas</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">Filtrar</button>
                @if(request()->filled('busca') || request()->filled('situacao'))
                    <a href="{{ route('admin.frota.liberacoes.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600">Limpar</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Motorista</th>
                        <th class="px-4 py-3 text-left">Período</th>
                        <th class="px-4 py-3 text-left">Motivo</th>
                        <th class="px-4 py-3 text-left">Liberado por</th>
                        <th class="px-4 py-3 text-left">Situação</th>
                        <th class="px-4 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($liberacoes as $liberacao)
                        @php
                            $situacaoClasses = match ($liberacao->situacao) {
                                'Vigente' => 'bg-green-100 text-green-700',
                                'Agendada' => 'bg-blue-100 text-blue-700',
                                'Expirada' => 'bg-gray-100 text-gray-600',
                                default => 'bg-red-100 text-red-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $liberacao->user?->nome ?? '—' }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $liberacao->user?->cpf ? format_cpf($liberacao->user->cpf) : '' }}
                                    {{ $liberacao->user?->tipo_usuario ? ' • '.tipo_usuario_label($liberacao->user->tipo_usuario) : '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $liberacao->inicio?->format('d/m/Y H:i') }}
                                <span class="text-gray-400">→</span>
                                {{ $liberacao->fim?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs">
                                {{ $liberacao->motivo ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $liberacao->liberadoPor?->nome ?? '—' }}
                                <span class="block text-xs text-gray-400">{{ $liberacao->created_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $situacaoClasses }}">
                                    {{ $liberacao->situacao }}
                                </span>
                                @if($liberacao->revogado_em)
                                    <span class="block text-[11px] text-gray-500 mt-1">
                                        por {{ $liberacao->revogadoPor?->nome ?? '—' }} em {{ $liberacao->revogado_em->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($liberacao->revogado_em === null && $liberacao->fim?->isFuture() && auth()->user()->hasPermission('frota', 'edit'))
                                    <form method="POST" action="{{ route('admin.frota.liberacoes.revogar', $liberacao) }}"
                                          onsubmit="return confirm('Revogar esta liberação?');">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-xs font-semibold hover:bg-red-50">
                                            <i class="fas fa-ban mr-1"></i> Revogar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                Nenhuma liberação encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $liberacoes->links() }}
        </div>
    </div>
</div>
@endsection
