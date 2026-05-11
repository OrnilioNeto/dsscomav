@extends('layout')

@section('title', 'Relatório de Treinamentos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col gap-2 mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-chart-line text-blue-900 mr-3"></i>Relatório Gerencial de Treinamentos
        </h1>
        <p class="text-gray-600 max-w-3xl">
            Indicadores de assistência, conclusão e consumo de conteúdo para apoiar decisões operacionais e de governança.
        </p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
        <p class="text-sm font-semibold text-blue-900 mb-2">Legenda rápida dos filtros</p>
        <p class="text-sm text-blue-900/80 leading-6">
            <strong>Tipo de usuário</strong> filtra quem está assistindo; <strong>Usuário</strong> seleciona uma pessoa específica; <strong>Nome livre</strong> faz busca parcial; <strong>Treinamento</strong> aponta o conteúdo analisado; <strong>Status</strong> diferencia <em>concluído</em>, <em>pendente</em> e <em>não iniciado</em>.
            O indicador <strong>Registros de Progresso</strong> representa participações registradas, não pessoas únicas. Os campos de tempo exibem o formato exato do certificado: <strong>HH:MM:SS</strong>.
        </p>
    </div>

    <!-- KPIs -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Registros de Progresso</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalAssistencias }}</p>
            <p class="text-xs text-gray-500 mt-1">Cada linha representa uma participação registrada</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Concluídas</p>
            <p class="text-3xl font-bold text-green-600">{{ $concluidas }}</p>
            <p class="text-xs text-gray-500 mt-1">Treinamentos finalizados com sucesso</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Taxa Geral</p>
            <p class="text-3xl font-bold text-purple-900">{{ $taxaGeral }}%</p>
            <p class="text-xs text-gray-500 mt-1">Proporção de conclusão sobre a base analisada</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Tempo assistido exato</p>
            <p class="text-3xl font-bold text-orange-600">{{ $tempoTotalFormatado }}</p>
            <p class="text-xs text-gray-500 mt-1">Acumulado de consumo de conteúdo</p>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Tempo médio assistido</p>
            <p class="text-3xl font-bold text-blue-900">{{ $tempoMedioFormatado }}</p>
            <p class="text-xs text-gray-500 mt-1">Média por registro de progresso</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Treinamentos Cadastrados</p>
            <p class="text-3xl font-bold text-slate-700">{{ $treinamentos->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Conteúdos disponíveis no catálogo</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Usuários em Destaque</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $usuariosEmDestaque->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Ranking dos maiores consumidores de conteúdo</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border border-gray-100">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Filtros</h2>
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-300">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-3 py-2 rounded-lg hover:bg-blue-800">Dashboard</a>
            </div>
        </div>

        <div class="mb-4 rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-700">
            Para visualizar quem ainda não começou, selecione o status <strong>Não iniciado</strong>. Se um treinamento for informado, a lista mostra quem ainda não iniciou esse conteúdo; sem treinamento, mostra quem não possui nenhum progresso registrado.
        </div>

        <form method="GET" action="{{ route('relatorios.treinamentos') }}" class="space-y-4">
            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                    <select name="tipo_usuario" id="tipo_usuario" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-1">Usuário</label>
                    <select name="usuario_id" id="usuario_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($users as $usuario)
                            <option value="{{ $usuario->id }}" @if(request('usuario_id') == $usuario->id) selected @endif>{{ $usuario->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_nome" class="block text-sm font-semibold text-gray-700 mb-1">Nome livre</label>
                    <input type="text" name="usuario_nome" id="usuario_nome" value="{{ request('usuario_nome') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Pesquisar por nome">
                </div>

                <div>
                    <label for="training_id" class="block text-sm font-semibold text-gray-700 mb-1">Treinamento</label>
                    <select name="training_id" id="training_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if(request('training_id') == $training->id) selected @endif>{{ $training->titulo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label for="status_progresso" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status_progresso" id="status_progresso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="concluido" @if(request('status_progresso') === 'concluido' || request('concluido') === '1') selected @endif>Concluído</option>
                        <option value="pendente" @if(request('status_progresso') === 'pendente' || request('concluido') === '0') selected @endif>Pendente</option>
                        <option value="nao_iniciado" @if(request('status_progresso') === 'nao_iniciado') selected @endif>Não iniciado</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Concluído = finalizou; Pendente = começou, mas ainda não concluiu; Não iniciado = nunca abriu esse conteúdo.</p>
                </div>

                <div>
                    <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-1">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" value="{{ request('data_inicio') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-1">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" value="{{ request('data_fim') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="training_tipo" class="block text-sm font-semibold text-gray-700 mb-1">Tipo de conteúdo</label>
                    <select name="training_tipo" id="training_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                        <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2 items-end">
                <button type="submit" class="flex-1 md:flex-none bg-blue-900 text-white py-2 px-4 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
                <a href="{{ route('relatorios.treinamentos') }}" class="flex-1 md:flex-none bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                    <i class="fas fa-redo mr-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Resumo por conteúdo -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100 mb-8">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Desempenho por Conteúdo</h2>
                <p class="text-sm text-gray-500">Ordenado pelos conteúdos com maior volume de assistência.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Participações</th>
                        <th class="px-4 py-3 text-center font-semibold">Concluídas</th>
                        <th class="px-4 py-3 text-center font-semibold">Taxa Conclusão</th>
                        <th class="px-4 py-3 text-center font-semibold">Tempo exato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($treinamentosResumo as $resumo)
                        @php
                            $taxa = $resumo->assistencias > 0 ? ($resumo->concluidas / $resumo->assistencias) * 100 : 0;
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ optional($resumo->training)->titulo ?? 'Conteúdo removido' }}</div>
                                <div class="text-xs text-gray-500">{{ optional($resumo->training)->tipo ? ucfirst(optional($resumo->training)->tipo) : 'Sem tipo' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $resumo->assistencias }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $resumo->concluidas }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full font-semibold text-sm">{{ number_format($taxa, 1, ',', '.') }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($resumo->tempo_total_assistido ?? 0)) }}</td>
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
                <i class="fas fa-user-clock text-purple-900 mr-2"></i>Usuários em Destaque
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-semibold">Usuário</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Participações</th>
                            <th class="px-3 py-2 text-center text-sm font-semibold">Tempo exato</th>
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
                                <td class="px-3 py-2 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($item->tempo_total_assistido ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-gray-600">Sem dados para o ranking</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bloco rápido de conteúdo -->
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-layer-group text-green-900 mr-2"></i>Conteúdos por Tipo
            </h2>
            <div class="space-y-4">
                @forelse($conteudosPorTipo as $tipo)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $tipo->tipo)) }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ $tipo->total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $treinamentos->count() > 0 ? (($tipo->total / $treinamentos->count()) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600">Nenhum conteúdo cadastrado.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tabela detalhada -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                        <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Início</th>
                        <th class="px-4 py-3 text-center font-semibold">Conclusão</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($progressos as $progresso)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $progresso->user->nome }}</div>
                                <div class="text-sm text-gray-600">{{ $progresso->user->getCpfFormatted() }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                <div class="font-semibold">{{ optional($progresso->training)->titulo ?? 'Nenhum treinamento iniciado' }}</div>
                                <div class="text-xs text-gray-500">{{ $progresso->training ? ucfirst(str_replace('_', ' ', $progresso->training->tipo ?? '')) : '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($progresso->tempo_assistido ?? 0)) }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progresso->porcentagem_assistida ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm text-gray-700">{{ $progresso->porcentagem_assistida ?? 0 }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(($progresso->status_progresso ?? null) === 'nao_iniciado')
                                    <span class="bg-slate-100 text-slate-900 px-3 py-1 rounded-full text-sm font-semibold">○ Não iniciado</span>
                                @elseif($progresso->concluido)
                                    <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                                @else
                                    <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $progresso->data_inicio ? \Carbon\Carbon::parse($progresso->data_inicio)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $progresso->data_conclusao ? \Carbon\Carbon::parse($progresso->data_conclusao)->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600">
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
