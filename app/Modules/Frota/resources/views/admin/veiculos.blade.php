@extends('layout')

@section('title', 'Veículos da Frota')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-truck-moving text-blue-900 mr-2"></i>Veículos da Frota
            </h1>
            <p class="text-gray-500 mt-1">Cadastro importado da Autotrac (código, nome, endereço e placa).</p>
        </div>
        <a href="{{ route('admin.frota.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.frota.veiculos') }}" class="flex flex-col md:flex-row gap-2">
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Nome, placa ou código"
                       class="w-full md:max-w-sm border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">Filtrar</button>
                @if(request()->filled('busca'))
                    <a href="{{ route('admin.frota.veiculos') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600">Limpar</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Veículo</th>
                        <th class="px-4 py-3 text-left">Placa</th>
                        <th class="px-4 py-3 text-left">Endereço</th>
                        <th class="px-4 py-3 text-left">Plano de viagem</th>
                        <th class="px-4 py-3 text-left">Sincronizado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($veiculos as $veiculo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600">#{{ $veiculo->code }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $veiculo->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $veiculo->license_plate ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $veiculo->address ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $veiculo->trip_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $veiculo->synced_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                Nenhum veículo importado ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $veiculos->links() }}
        </div>
    </div>
</div>
@endsection
