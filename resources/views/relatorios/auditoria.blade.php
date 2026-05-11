@extends('layout')

@section('title', 'Relatório de Auditoria')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col gap-2 mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-shield-alt text-purple-900 mr-3"></i>Relatório Executivo de Auditoria
        </h1>
        <p class="text-gray-600 max-w-4xl">
            Painel consolidado para governança, compliance e decisão gerencial. Aqui você acompanha usuários, conteúdos, certificados, tempo assistido e evolução histórica.
        </p>
    </div>

    <!-- KPIs principais -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Total de Usuários</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
            <p class="text-xs text-gray-500 mt-1">Base visível no filtro atual</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Treinamentos</p>
            <p class="text-3xl font-bold text-purple-900">{{ $totalTreinamentos }}</p>
            <p class="text-xs text-gray-500 mt-1">Catálogo de conteúdos ativos</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Certificados Emitidos</p>
            <p class="text-3xl font-bold text-orange-600">{{ $totalCertificados }}</p>
            <p class="text-xs text-gray-500 mt-1">Documentos gerados no sistema</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Usuários sem Treinamento</p>
            <p class="text-3xl font-bold text-red-600">{{ $usuariosSemTreinamento }}</p>
            <p class="text-xs text-gray-500 mt-1">Ponto de atenção para engajamento</p>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 md:col-span-2">
            <p class="text-gray-600 text-sm">Tempo Total Assistido</p>
            <p class="text-3xl font-bold text-blue-900">{{ $tempoTotalFormatado }}</p>
            <p class="text-xs text-gray-500 mt-1">Consumo total de conteúdo monitorado</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Tempo Médio</p>
            <p class="text-3xl font-bold text-green-600">{{ $tempoMedioFormatado }}</p>
            <p class="text-xs text-gray-500 mt-1">Média por ocorrência de progresso</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Taxa Geral de Conclusão</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $taxaGeral }}%</p>
            <p class="text-xs text-gray-500 mt-1">Conclusões sobre assistências</p>
        </div>
    </div>

    <!-- Filtros rápidos -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border border-gray-100">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Filtros Rápidos</h2>
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-300">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-3 py-2 rounded-lg hover:bg-blue-800">Dashboard</a>
            </div>
        </div>

        <form method="GET" action="{{ route('relatorios.auditoria') }}" class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                <select name="tipo_usuario" onchange="this.form.submit()" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    @foreach($userTypes as $type)
                        <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuário</label>
                <select name="usuario_id" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @if(request('usuario_id') == $u->id) selected @endif>{{ $u->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de conteúdo</label>
                <select name="training_tipo" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Todos</option>
                    <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                    <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="bg-purple-900 text-white px-4 py-2 rounded-lg hover:bg-purple-800 transition">
                    <i class="fas fa-search mr-2"></i>Aplicar Filtros
                </button>
                <a href="{{ route('relatorios.auditoria') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-redo mr-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <!-- Distribuição por tipo de usuário -->
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-users text-purple-900 mr-2"></i>Distribuição de Usuários
            </h2>
            <div class="space-y-4">
                @forelse($usuariosPorTipo as $tipo)
                    @php
                        $percentual = $totalUsuarios > 0 ? ($tipo->total / $totalUsuarios) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $tipo->tipo_usuario)) }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ $tipo->total }} usuários</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $percentual }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ number_format($percentual, 1, ',', '.') }}% da base filtrada</p>
                    </div>
                @empty
                    <p class="text-gray-600">Nenhum dado disponível.</p>
                @endforelse
            </div>
        </div>

        <!-- Conteúdos por tipo -->
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-layer-group text-blue-900 mr-2"></i>Conteúdos por Tipo
            </h2>
            <div class="space-y-4">
                @forelse($conteudosPorTipo as $tipo)
                    @php
                        $percentualConteudo = $totalTreinamentos > 0 ? ($tipo->total / $totalTreinamentos) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $tipo->tipo)) }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ $tipo->total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentualConteudo }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600">Nenhum conteúdo cadastrado.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Ranking de conteúdos -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border border-gray-100">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Treinamentos Mais Assistidos</h2>
                <p class="text-sm text-gray-500">Ranking dos conteúdos com maior volume e taxa de conclusão.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Assistências</th>
                        <th class="px-4 py-3 text-center font-semibold">Conclusões</th>
                        <th class="px-4 py-3 text-center font-semibold">Taxa</th>
                        <th class="px-4 py-3 text-center font-semibold">Tempo Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($treinamentosMaisAssistidos as $training)
                        @php
                            $taxa = $training->progress_count > 0 ? ($training->concluidos_count / $training->progress_count) * 100 : 0;
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $training->titulo }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($training->tipo) }} • {{ $training->carga_horaria }} min</div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $training->progress_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $training->concluidos_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">{{ number_format($taxa, 1, ',', '.') }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($training->tempo_total_assistido ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-600">Nenhum treinamento encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <!-- Usuários em destaque -->
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-user-clock text-orange-600 mr-2"></i>Usuários em Destaque
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-semibold">Usuário</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Assistências</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Conclusões</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosEmDestaque as $item)
                            <tr class="border-b">
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gray-800">{{ optional($item->user)->nome ?? 'Usuário removido' }}</div>
                                    <div class="text-xs text-gray-500">{{ optional($item->user)->tipo_usuario ? ucfirst(str_replace('_', ' ', optional($item->user)->tipo_usuario)) : '—' }}</div>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-700">{{ $item->assistencias }}</td>
                                <td class="px-3 py-2 text-center text-gray-700">{{ $item->concluidas }}</td>
                                <td class="px-3 py-2 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($item->tempo_total_assistido ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-600">Sem dados para o ranking</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Certificados por mês -->
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Certificados por Mês
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-semibold">Período</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Certificados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($certificadosPorMes as $cert)
                            <tr class="border-b">
                                <td class="px-3 py-2 text-gray-700">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $cert->periodo)->format('m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full font-semibold">{{ $cert->total }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-3 py-6 text-center text-gray-600">Nenhum dado disponível</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Painel de compliance -->
    <div class="bg-yellow-50 border-2 border-yellow-200 p-6 rounded-lg mt-8">
        <h3 class="text-lg font-bold text-yellow-900 mb-2 flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>Leitura Gerencial
        </h3>
        <p class="text-yellow-800 text-sm">
            Este relatório consolida a base atual do sistema em tempo real. O foco principal está em engajamento, aderência aos conteúdos, tempo de consumo e emissão de evidências para auditoria.
        </p>
    </div>
</div>
@endsection
