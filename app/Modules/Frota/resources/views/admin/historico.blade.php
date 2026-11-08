@extends('layout')

@section('title', 'Histórico de Jornadas')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-clock-rotate-left text-blue-900 mr-2"></i>Histórico de Jornadas
            </h1>
            <p class="text-gray-500 mt-1">Login e logout de motoristas capturados do rastreamento.</p>
        </div>
        <a href="{{ route('admin.frota.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.frota.historico') }}" class="grid md:grid-cols-6 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">De</label>
                    <input type="date" name="de" value="{{ request('de') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Até</label>
                    <input type="date" name="ate" value="{{ request('ate') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Motorista</label>
                    <select name="motorista_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach($motoristas as $motorista)
                            <option value="{{ $motorista->id }}" @selected((string) request('motorista_id') === (string) $motorista->id)>
                                {{ $motorista->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Veículo</label>
                    <select name="veiculo_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach($veiculos as $veiculo)
                            <option value="{{ $veiculo->id }}" @selected((string) request('veiculo_id') === (string) $veiculo->id)>
                                {{ $veiculo->name }}{{ $veiculo->license_plate ? ' • '.$veiculo->license_plate : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Situação</label>
                    <select name="situacao" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todas</option>
                        <option value="aberta" @selected(request('situacao') === 'aberta')>Aberta</option>
                        <option value="fechada" @selected(request('situacao') === 'fechada')>Fechada</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">Filtrar</button>
                    <a href="{{ route('admin.frota.historico') }}" class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-600">Limpar</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Motorista</th>
                        <th class="px-4 py-3 text-left">CPF</th>
                        <th class="px-4 py-3 text-left">Veículo</th>
                        <th class="px-4 py-3 text-left">Login</th>
                        <th class="px-4 py-3 text-left">Logout</th>
                        <th class="px-4 py-3 text-left">Duração</th>
                        <th class="px-4 py-3 text-left">Situação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($jornadas as $jornada)
                        @php
                            $minutos = $jornada->duracao_minutos ?? 0;
                            $duracao = intdiv($minutos, 60).'h '.($minutos % 60).'min';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $jornada->driver_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $jornada->driver_cpf ? format_cpf($jornada->driver_cpf) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $jornada->veiculo?->identificacao ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $jornada->login_time?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $jornada->logout_time?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $duracao }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $jornada->aberta ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $jornada->aberta ? 'Aberta' : 'Fechada' }}
                                </span>
                                @if($jornada->excess_journey)
                                    <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">Jornada excedida</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Nenhuma jornada encontrada para o período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $jornadas->links() }}
        </div>
    </div>
</div>
@endsection
