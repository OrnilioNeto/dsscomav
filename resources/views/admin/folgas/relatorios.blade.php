@extends('layout')

@section('title', 'Relatórios de Folgas')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-file-alt text-blue-600 mr-2"></i>Relatórios de Folgas
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.folgas.relatorios.csv', ['month' => $mes, 'year' => $ano, 'user_id' => $userId]) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                <i class="fas fa-file-csv mr-1"></i>Exportar CSV
            </a>
            <a href="{{ route('admin.folgas.relatorios.pdf', ['month' => $mes, 'year' => $ano]) }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                <i class="fas fa-file-pdf mr-1"></i>Exportar PDF
            </a>
            <a href="{{ route('admin.folgas.index', ['month' => $mes, 'year' => $ano]) }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Voltar
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="mb-6">
        <div class="flex flex-wrap items-end gap-3 bg-white rounded-xl shadow p-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Mês</label>
                <select name="month" class="border-gray-300 rounded-lg text-sm">
                    @foreach(['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'] as $i => $nome)
                        <option value="{{ $i + 1 }}" {{ $mes == ($i + 1) ? 'selected' : '' }}>{{ $nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Ano</label>
                <select name="year" class="border-gray-300 rounded-lg text-sm">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ $ano == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Motorista</label>
                <select name="user_id" class="border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    @foreach($motoristas as $m)
                        <option value="{{ $m->id }}" {{ $userId == $m->id ? 'selected' : '' }}>{{ $m->nome }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
        </div>
    </form>

    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800">
        <i class="fas fa-calendar-check mr-1"></i>
        Previsão do mês (6x1): <strong>{{ $previsaoMes }}</strong> folga(s) por motorista — a cada 6 dias trabalhados ganha 1 folga e a folga não conta para o próximo crédito.
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="text-lg font-bold text-gray-800">Resumo por Motorista</h2>
        </div>
        <div class="overflow-x-auto table-responsive">
            <table class="w-full text-sm">
                <thead class="bg-emerald-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Motorista</th>
                        <th class="p-3 text-center">CPF</th>
                        <th class="p-3 text-center">Tiradas</th>
                        <th class="p-3 text-center">Atestado</th>
                        <th class="p-3 text-center">Licença</th>
                        <th class="p-3 text-center">Ajustes</th>
                        <th class="p-3 text-center">Saldo Anterior</th>
                        <th class="p-3 text-center">Saldo Acumulado</th>
                        <th class="p-3 text-center">Domingo</th>
                        <th class="p-3 text-center">Banco Dom.</th>
                        <th class="p-3 text-center">Dias Trab.</th>
                        <th class="p-3 text-center">Atestado</th>
                        <th class="p-3 text-center">Licença</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($motoristas as $motorista)
                        @php $s = $relatorio[$motorista->id]; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 font-semibold">{{ $motorista->nome }}</td>
                            <td class="p-3 text-center text-gray-500">{{ $motorista->cpf }}</td>
                            <td class="p-3 text-center font-bold text-green-600">{{ $s['tiradas_mes'] }}</td>
                            <td class="p-3 text-center font-bold text-yellow-600">{{ $s['atestados_mes'] }}</td>
                            <td class="p-3 text-center font-bold text-purple-600">{{ $s['licencas_mes'] }}</td>
                            <td class="p-3 text-center font-bold {{ $s['ajustes'] != 0 ? 'text-yellow-600' : 'text-gray-400' }}">{{ $s['ajustes'] }}</td>
                            <td class="p-3 text-center text-gray-600">{{ $s['saldo_anterior'] }}</td>
                            <td class="p-3 text-center font-bold {{ $s['saldo_acumulado'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $s['saldo_acumulado'] }}</td>
                            <td class="p-3 text-center">
                                @if($s['domingo_cumprido'])
                                    <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                @else
                                    <span class="text-red-500"><i class="fas fa-times-circle"></i></span>
                                @endif
                            </td>
                            <td class="p-3 text-center font-bold {{ ($s['domingo_saldo'] ?? 0) > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                                {{ $s['domingo_saldo'] ?? 0 }}
                            </td>
                            <td class="p-3 text-center">{{ $s['dias_trabalhados'] }}</td>
                            <td class="p-3 text-center text-yellow-600">{{ $s['dias_atestado'] }}</td>
                            <td class="p-3 text-center text-purple-600">{{ $s['dias_licenca'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="p-8 text-center text-gray-400">Nenhum registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
