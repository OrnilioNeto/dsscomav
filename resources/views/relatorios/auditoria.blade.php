@extends('layout')

@section('title', 'Relatório de Auditoria')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-audit text-purple-900 mr-3"></i>Relatório de Auditoria
    </h1>

    <!-- Estatísticas Principais -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Total de Usuários</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
            <p class="text-xs text-green-600 mt-1">{{ $usuariosAtivos }} ativos</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Treinamentos</p>
            <p class="text-3xl font-bold text-purple-900">{{ $totalTreinamentos }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Certificados Emitidos</p>
            <p class="text-3xl font-bold text-orange-600">{{ $totalCertificados }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Usuários sem Treinamento</p>
            <p class="text-3xl font-bold text-red-600">{{ $usuariosSemTreinamento }}</p>
        </div>
    </div>

    <!-- Filtros Rápidos -->
    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <form method="GET" action="{{ route('relatorios.auditoria') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                <select name="tipo_usuario" onchange="this.form.submit()" class="px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    @foreach($userTypes as $type)
                        <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuário</label>
                <select name="usuario_id" class="px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @if(request('usuario_id') == $u->id) selected @endif>{{ $u->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo Treinamento</label>
                <select name="training_tipo" class="px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                    <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-purple-900 text-white px-4 py-2 rounded-lg">Filtrar</button>
                <a href="javascript:history.back()" class="bg-gray-200 px-4 py-2 rounded-lg">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-4 py-2 rounded-lg">Dashboard</a>
            </div>
        </form>
    </div>

    <!-- Usuários por Tipo -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-chart-pie text-purple-900 mr-2"></i>Distribuição de Usuários por Tipo
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($usuariosPorTipo as $tipo)
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-700 font-semibold capitalize">{{ str_replace('_', ' ', $tipo->tipo_usuario) }}</p>
                    <p class="text-2xl font-bold text-blue-900 mt-1">{{ $tipo->total }}</p>
                    @php
                        $percentual = ($tipo->total / $totalUsuarios) * 100;
                    @endphp
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentual }}%"></div>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">{{ number_format($percentual, 1, ',', '.') }}%</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Treinamentos Mais Assistidos -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-video text-purple-900 mr-2"></i>Treinamentos Mais Assistidos
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Assistências</th>
                        <th class="px-4 py-3 text-center font-semibold">Taxa Conclusão</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($treinamentosMaisAssistidos as $training)
                        @php
                            $taxa = isset($taxaConclusao[$training->id]) ? $taxaConclusao[$training->id] : 0;
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $training->titulo }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $training->progress_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="w-full bg-gray-200 rounded-full h-2 inline-block" style="width: 100px;">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $taxa }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 ml-2">{{ number_format($taxa, 1, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-600">
                                Nenhum treinamento encontrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Informações Gerais -->
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <!-- Tempo Médio -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-clock text-orange-600 mr-2"></i>Tempo Médio de Assistência
            </h2>
            <p class="text-3xl font-bold text-orange-600">{{ $tempoMedioFormatado }}</p>
            <p class="text-sm text-gray-600 mt-2">Tempo médio que usuários assistem os treinamentos</p>
        </div>

        <!-- Estatísticas de Certificados -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-certificate text-green-600 mr-2"></i>Certificados Emitidos
            </h2>
            <p class="text-3xl font-bold text-green-600">{{ $totalCertificados }}</p>
            <p class="text-sm text-gray-600 mt-2">Total de certificados válidos emitidos no sistema</p>
        </div>
    </div>

    <!-- Relatório por Período -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Certificados por Mês (Últimos 12 Meses)
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Período</th>
                        <th class="px-4 py-3 text-center font-semibold">Certificados</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificadosPorMes as $cert)
                        @php
                            $data = \Carbon\Carbon::createFromDate($cert->ano, $cert->mes, 1);
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $data->format('F/Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full font-semibold">{{ $cert->total }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-600">
                                Nenhum dado disponível
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Aviso de Auditoria -->
    <div class="bg-yellow-50 border-2 border-yellow-200 p-6 rounded-lg mt-8">
        <h3 class="text-lg font-bold text-yellow-900 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>Relatório de Auditoria
        </h3>
        <p class="text-yellow-800 text-sm">
            Este relatório é atualizado em tempo real e reflete o estado atual do sistema. Todos os dados são automaticamente registrados e podem ser auditados.
        </p>
    </div>
</div>
@endsection
