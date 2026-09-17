@extends('layout')

@section('title', 'Plataforma — Clientes')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-building text-indigo-600 mr-3"></i>Clientes (Plataforma)
        </h1>
        <a href="{{ route('plataforma.create') }}" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-plus mr-2"></i>Novo Cliente
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">#</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Cliente</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Slug / Domínio</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Módulos ativos</th>
                        <th class="px-6 py-3 text-right text-sm font-bold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-gray-600 font-mono">#{{ $tenant->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $tenant->nome_exibicao ?? $tenant->nome }}</div>
                                <div class="text-xs text-gray-500">Plano: {{ $tenant->plano ?: '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="font-mono text-xs">{{ $tenant->slug }}</div>
                                <div class="font-mono text-xs text-gray-400">{{ $tenant->dominio ?: '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $badge = match ($tenant->status) {
                                        'ativo' => 'bg-green-100 text-green-700',
                                        'trial' => 'bg-yellow-100 text-yellow-700',
                                        'suspenso' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $badge }}">{{ ucfirst($tenant->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700">{{ $tenant->modules_count }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('plataforma.edit', $tenant) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                                    <i class="fas fa-cog mr-1"></i>Gerenciar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">Nenhum cliente cadastrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection