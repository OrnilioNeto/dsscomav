@extends('layout')

@section('title', 'Relatório de Treinamentos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-chart-line text-blue-900 mr-3"></i>Relatório de Treinamentos
    </h1>

    <!-- Estatísticas -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Total de Assistências</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalAssistencias }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Concluídas</p>
            <p class="text-3xl font-bold text-green-600">{{ $concluidas }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Taxa Geral</p>
            <p class="text-3xl font-bold text-purple-900">{{ $taxaGeral }}%</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Tempo Médio</p>
            <p class="text-3xl font-bold text-orange-600">{{ $tempoMedioFormatado }}</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">Filtros</h2>

        <form method="GET" action="{{ route('relatorios.treinamentos') }}" class="space-y-4">
            <div class="flex gap-2 mb-4">
                <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-300">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-3 py-2 rounded-lg hover:bg-blue-800">Dashboard</a>
            </div>
            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                    <select name="tipo_usuario" id="tipo_usuario" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-1">Usuário (nome)</label>
                    <select name="usuario_id" id="usuario_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($users as $usuario)
                            <option value="{{ $usuario->id }}" @if(request('usuario_id') == $usuario->id) selected @endif>
                                {{ $usuario->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="training_id" class="block text-sm font-semibold text-gray-700 mb-1">Treinamento</label>
                    <select name="training_id" id="training_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if(request('training_id') == $training->id) selected @endif>
                                {{ $training->titulo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="concluido" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="concluido" id="concluido" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1" @if(request('concluido') === '1') selected @endif>Concluído</option>
                        <option value="0" @if(request('concluido') === '0') selected @endif>Pendente</option>
                    </select>
                </div>

                <div class="flex gap-2 items-end">
                    <button type="submit" class="flex-1 bg-blue-900 text-white py-2 px-4 rounded-lg hover:bg-blue-800 transition">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="{{ route('relatorios.treinamentos') }}" class="flex-1 bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                        <i class="fas fa-redo mr-2"></i>Limpar
                    </a>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-1">Data Início (De)</label>
                    <input type="date" name="data_inicio" id="data_inicio" 
                        value="{{ request('data_inicio') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-1">Data Fim (Até)</label>
                    <input type="date" name="data_fim" id="data_fim" 
                        value="{{ request('data_fim') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="training_tipo" class="block text-sm font-semibold text-gray-700 mb-1">Tipo Treinamento</label>
                    <select name="training_tipo" id="training_tipo"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                        <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                    <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                    <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                    <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                    <th class="px-4 py-3 text-center font-semibold">Data Início</th>
                </tr>
            </thead>
            <tbody>
                @forelse($progressos as $progresso)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $progresso->user->nome }}</div>
                            <div class="text-sm text-gray-600">{{ $progresso->user->getCpfFormatted() }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $progresso->training->titulo }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ gmdate('H:i:s', $progresso->tempo_assistido ?? 0) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progresso->percentual_conclusao ?? 0 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-700">{{ $progresso->percentual_conclusao ?? 0 }}%</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($progresso->concluido)
                                <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ optional($progresso->data_inicio_assistencia)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-600">
                            <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                            <p class="mt-2">Nenhum registro encontrado</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Paginação -->
    @if($progressos->hasPages())
        <div class="mt-6">
            {{ $progressos->links() }}
        </div>
    @endif
</div>
@endsection
