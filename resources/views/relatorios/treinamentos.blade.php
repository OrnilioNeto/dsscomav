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

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 mb-8 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Justificativa operacional</p>
                <h2 class="mt-1 text-xl font-black text-amber-950">Usuários em férias no período filtrado</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-amber-900/80">
                    Essa lista ajuda a justificar porque alguns usuários não aparecem na base de conclusão do período. Se o usuário assistir durante as férias, o registro passa a contar normalmente nos KPIs.
                </p>
            </div>
            <div class="rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-amber-100 text-center min-w-[180px]">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Em férias</p>
                <p class="mt-2 text-4xl font-black text-amber-900">{{ $usuariosEmFerias }}</p>
            </div>
        </div>

        @if(($usuariosEmFeriasLista ?? collect())->count())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($usuariosEmFeriasLista as $usuarioFerias)
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-200">
                        {{ $usuarioFerias->nome }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-sm text-amber-900/70">Nenhum usuário em férias foi encontrado para o filtro atual.</p>
        @endif
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
                    <select name="usuario_id" id="usuario_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <select name="training_id" id="training_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if(request('training_id') == $training->id) selected @endif>{{ $training->titulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" name="somente_ferias" value="1" {{ request('somente_ferias') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-500">
                        Mostrar somente usuários em férias
                    </label>
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
                        <option value="nao_finalizados" @if(request('status_progresso') === 'nao_finalizados') selected @endif>Não finalizados (pendente + não iniciado)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Concluído = finalizou; Pendente = começou, mas não concluiu; Não iniciado = nunca abriu; Não finalizados = pendente + não iniciado.</p>
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
                    <a href="{{ route('relatorios.treinamentos.pdf') }}{{ request()->getQueryString() ? ('?' . request()->getQueryString()) : '' }}" class="flex-1 md:flex-none bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-500 transition text-center">
                        <i class="fas fa-file-pdf mr-2"></i>Baixar PDF
                    </a>
                </div>
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- FOCO NO USUÁRIO: lista completa de treinamentos do usuário --}}
    {{-- ============================================================ --}}
    @if($focoUsuario)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-blue-200 mb-8">
            <div class="p-6 border-b border-blue-200 bg-blue-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-blue-900 flex items-center gap-2">
                            <i class="fas fa-user-check"></i> Foco no Usuário
                        </h2>
                        <p class="text-sm text-blue-800/70 mt-1">
                            Visão completa de todos os treinamentos de <strong>{{ $focoUsuario->nome }}</strong>
                            ({{ $focoUsuario->getCpfFormatted() }})
                            @if($statusProgresso)
                                <span class="ml-2 inline-flex items-center gap-1 bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full text-xs font-semibold">
                                    <i class="fas fa-filter"></i>
                                    Filtrado por:
                                    @if($statusProgresso === 'concluido') Concluídos
                                    @elseif($statusProgresso === 'pendente') Pendentes
                                    @elseif($statusProgresso === 'nao_iniciado') Não iniciados
                                    @elseif($statusProgresso === 'nao_finalizados') Não finalizados
                                    @endif
                                </span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('relatorios.treinamentos') }}" class="bg-white border border-blue-300 text-blue-800 px-4 py-2 rounded-lg hover:bg-blue-100 transition text-sm font-semibold">
                        <i class="fas fa-times mr-1"></i>Fechar foco
                    </a>
                </div>
            </div>

            {{-- Cards de resumo --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b border-gray-200">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 text-center">
                    <p class="text-sm text-gray-600">Total de Treinamentos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $focoUsuarioResumo['total'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-green-200 text-center">
                    <p class="text-sm text-green-700">Concluídos</p>
                    <p class="text-2xl font-bold text-green-600">{{ $focoUsuarioResumo['concluidos'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-yellow-200 text-center">
                    <p class="text-sm text-yellow-700">Pendentes</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $focoUsuarioResumo['pendentes'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 text-center">
                    <p class="text-sm text-slate-600">Não Iniciados</p>
                    <p class="text-2xl font-bold text-slate-500">{{ $focoUsuarioResumo['nao_iniciados'] }}</p>
                </div>
            </div>

            {{-- Barra de progresso geral --}}
            @if($focoUsuarioResumo['total'] > 0)
                <div class="px-6 pt-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">Progresso Geral</span>
                        <span class="font-bold text-blue-900">{{ number_format(($focoUsuarioResumo['concluidos'] / $focoUsuarioResumo['total']) * 100, 1, ',', '.') }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full" style="width: {{ ($focoUsuarioResumo['concluidos'] / $focoUsuarioResumo['total']) * 100 }}%"></div>
                    </div>
                </div>
            @endif

            {{-- Tabela detalhada --}}
            <div class="overflow-x-auto p-6">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                            <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-3 text-center font-semibold">Carga Horária</th>
                            <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                            <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                            <th class="px-4 py-3 text-center font-semibold">Status</th>
                            <th class="px-4 py-3 text-center font-semibold">Avaliação</th>
                            <th class="px-4 py-3 text-center font-semibold">Início</th>
                            <th class="px-4 py-3 text-center font-semibold">Conclusão</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($focoUsuarioTreinamentos as $item)
                            <tr class="border-b hover:bg-blue-50/50 transition">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800">{{ $item->training->titulo }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $item->training->tipo ? ucfirst($item->training->tipo) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->training->carga_horaria ? $item->training->carga_horaria . 'h' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->tem_progresso)
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->porcentagem_assistida }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">{{ $item->porcentagem_assistida }}%</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->tem_progresso ? gmdate('H:i:s', (int) $item->tempo_assistido) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->status === 'concluido')
                                        <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                                    @elseif($item->status === 'pendente')
                                        <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                                    @else
                                        <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm font-semibold">○ Não iniciado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($item->concluido && $item->avaliacao_aprovada)
                                        <span class="text-green-600 font-semibold">Aprovado</span>
                                    @elseif($item->tem_progresso && !$item->concluido)
                                        <span class="text-yellow-600">Pendente</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->data_inicio ? \Carbon\Carbon::parse($item->data_inicio)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->data_conclusao ? \Carbon\Carbon::parse($item->data_conclusao)->format('d/m/Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                                    <p>Nenhum treinamento encontrado para este usuário</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- FOCO NO TREINAMENTO: lista completa de usuários --}}
    {{-- ============================================================ --}}
    @if($focoTreinamento)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-purple-200 mb-8">
            <div class="p-6 border-b border-purple-200 bg-purple-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-purple-900 flex items-center gap-2">
                            <i class="fas fa-graduation-cap"></i> Foco no Treinamento
                        </h2>
                        <p class="text-sm text-purple-800/70 mt-1">
                            Todos os usuários elegíveis para <strong>{{ $focoTreinamento->titulo }}</strong>
                            @if($focoTreinamento->tipo)
                                <span class="ml-1 text-xs bg-purple-200 text-purple-800 px-2 py-0.5 rounded-full">{{ ucfirst($focoTreinamento->tipo) }}</span>
                            @endif
                            @if($statusProgresso)
                                <span class="ml-2 inline-flex items-center gap-1 bg-purple-200 text-purple-800 px-2 py-0.5 rounded-full text-xs font-semibold">
                                    <i class="fas fa-filter"></i>
                                    Filtrado por:
                                    @if($statusProgresso === 'concluido') Concluídos
                                    @elseif($statusProgresso === 'pendente') Pendentes
                                    @elseif($statusProgresso === 'nao_iniciado') Não iniciados
                                    @elseif($statusProgresso === 'nao_finalizados') Não finalizados
                                    @endif
                                </span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('relatorios.treinamentos') }}" class="bg-white border border-purple-300 text-purple-800 px-4 py-2 rounded-lg hover:bg-purple-100 transition text-sm font-semibold">
                        <i class="fas fa-times mr-1"></i>Fechar foco
                    </a>
                </div>
            </div>

            {{-- Cards de resumo --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b border-gray-200">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 text-center">
                    <p class="text-sm text-gray-600">Total de Usuários</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $focoTreinamentoResumo['total'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-green-200 text-center">
                    <p class="text-sm text-green-700">Concluíram</p>
                    <p class="text-2xl font-bold text-green-600">{{ $focoTreinamentoResumo['concluidos'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-yellow-200 text-center">
                    <p class="text-sm text-yellow-700">Pendentes</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $focoTreinamentoResumo['pendentes'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 text-center">
                    <p class="text-sm text-slate-600">Não Iniciaram</p>
                    <p class="text-2xl font-bold text-slate-500">{{ $focoTreinamentoResumo['nao_iniciados'] }}</p>
                </div>
            </div>

            {{-- Barra de progresso geral --}}
            @if($focoTreinamentoResumo['total'] > 0)
                <div class="px-6 pt-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">Taxa de Conclusão</span>
                        <span class="font-bold text-purple-900">{{ number_format(($focoTreinamentoResumo['concluidos'] / $focoTreinamentoResumo['total']) * 100, 1, ',', '.') }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full" style="width: {{ ($focoTreinamentoResumo['concluidos'] / $focoTreinamentoResumo['total']) * 100 }}%"></div>
                    </div>
                </div>
            @endif

            {{-- Tabela detalhada --}}
            <div class="overflow-x-auto p-6">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                            <th class="px-4 py-3 text-left font-semibold">CPF</th>
                            <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                            <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                            <th class="px-4 py-3 text-center font-semibold">Status</th>
                            <th class="px-4 py-3 text-center font-semibold">Avaliação</th>
                            <th class="px-4 py-3 text-center font-semibold">Início</th>
                            <th class="px-4 py-3 text-center font-semibold">Conclusão</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($focoTreinamentoUsuarios as $item)
                            <tr class="border-b hover:bg-purple-50/50 transition">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800">{{ $item->user->nome }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $item->user->getCpfFormatted() }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $item->user->tipo_usuario ? ucfirst(str_replace('_', ' ', $item->user->tipo_usuario)) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->tem_progresso)
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $item->porcentagem_assistida }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">{{ $item->porcentagem_assistida }}%</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->tem_progresso ? gmdate('H:i:s', (int) $item->tempo_assistido) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->status === 'concluido')
                                        <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                                    @elseif($item->status === 'pendente')
                                        <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                                    @else
                                        <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm font-semibold">○ Não iniciado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($item->concluido && $item->avaliacao_aprovada)
                                        <span class="text-green-600 font-semibold">Aprovado</span>
                                    @elseif($item->tem_progresso && !$item->concluido)
                                        <span class="text-yellow-600">Pendente</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->data_inicio ? \Carbon\Carbon::parse($item->data_inicio)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->data_conclusao ? \Carbon\Carbon::parse($item->data_conclusao)->format('d/m/Y') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                                    <p>Nenhum usuário encontrado para este treinamento</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Resumo por conteúdo -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100 mb-8">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Desempenho por Conteúdo</h2>
                <p class="text-sm text-gray-500">
                    @if($statusProgresso === 'nao_finalizados')
                        Usuários que <strong>não finalizaram</strong> cada treinamento (pendentes + não iniciados).
                    @elseif($statusProgresso === 'nao_iniciado')
                        Usuários que <strong>não iniciaram</strong> cada treinamento.
                    @elseif($statusProgresso === 'pendente')
                        Usuários com progresso <strong>pendente</strong> em cada treinamento.
                    @elseif($statusProgresso === 'concluido')
                        Usuários que <strong>concluíram</strong> cada treinamento.
                    @else
                        Ordenado pelos conteúdos com maior volume de assistência.
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('relatorios.treinamentos.resumo_pdf', request()->query()) }}"
                   class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-500 transition text-sm font-semibold inline-flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </a>
                <a href="{{ route('relatorios.treinamentos') }}?{{ http_build_query(array_merge(request()->query(), ['exportar_resumo' => 1])) }}"
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-500 transition text-sm font-semibold inline-flex items-center gap-2">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
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
                            $usuariosTreino = $usuariosPorTreinamento[$resumo->training_id] ?? null;
                            $todosUsuarios = collect();
                            if ($usuariosTreino) {
                                $todosUsuarios = $todosUsuarios->merge($usuariosTreino['concluidos']->map(fn($u) => ['user' => $u, 'status' => 'concluido']));
                                $todosUsuarios = $todosUsuarios->merge($usuariosTreino['pendentes']->map(fn($u) => ['user' => $u, 'status' => 'pendente']));
                                $todosUsuarios = $todosUsuarios->merge($usuariosTreino['nao_iniciados']->map(fn($u) => ['user' => $u, 'status' => 'nao_iniciado']));
                            }
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
                        @if($todosUsuarios->isNotEmpty())
                            <tr class="bg-blue-50/50">
                                <td colspan="5" class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($todosUsuarios as $item)
                                            @if($item['status'] === 'concluido')
                                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-semibold">
                                                    <i class="fas fa-check-circle text-[10px]"></i> {{ $item['user']->nome }}
                                                </span>
                                            @elseif($item['status'] === 'pendente')
                                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-xs font-semibold">
                                                    <i class="fas fa-clock text-[10px]"></i> {{ $item['user']->nome }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full text-xs font-semibold">
                                                    <i class="fas fa-minus-circle text-[10px]"></i> {{ $item['user']->nome }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
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
                        <th class="px-4 py-3 text-left font-semibold">Ocupação</th>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                        <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Pergunta da Avaliação</th>
                        <th class="px-4 py-3 text-left font-semibold">Resposta do Usuário</th>
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
                                {{ $progresso->user->tipo_usuario ? ucfirst(str_replace('_', ' ', $progresso->user->tipo_usuario)) : '—' }}
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
                            <td class="px-4 py-3 text-gray-700">
                                {{ optional($progresso->training)->avaliacao_pergunta ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                @php
                                    $opcoesAvaliacao = optional($progresso->training)->avaliacao_opcoes;
                                    $respostaUsuario = $progresso->avaliacao_resposta_usuario ?? null;
                                    $respostaCorreta = optional($progresso->training)->avaliacao_resposta_correta;
                                @endphp
                                @if($progresso->concluido && $progresso->avaliacao_aprovada)
                                    @if(is_array($opcoesAvaliacao) && $respostaUsuario !== null)
                                        {{ $opcoesAvaliacao[$respostaUsuario] ?? 'Resposta inválida' }}
                                    @elseif(is_array($opcoesAvaliacao) && $respostaCorreta !== null)
                                        {{ $opcoesAvaliacao[$respostaCorreta] ?? 'Resposta correta não encontrada' }}
                                        <div class="text-xs text-gray-500">(registrado no momento da liberação)</div>
                                    @else
                                        —
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $progresso->data_inicio ? \Carbon\Carbon::parse($progresso->data_inicio)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $progresso->data_conclusao ? \Carbon\Carbon::parse($progresso->data_conclusao)->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-600">
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
