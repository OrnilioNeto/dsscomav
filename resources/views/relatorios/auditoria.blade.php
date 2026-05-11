@extends('layout')

@section('title', 'Relatório de Auditoria')

@section('content')
@php
    $completionPct = max(0, min(100, (float) ($taxaGeralPercentual ?? 0)));
    $engagementPct = max(0, min(100, (float) ($taxaEngajamentoPercentual ?? 0)));
    $certificationPct = max(0, min(100, (float) ($taxaCertificacaoPercentual ?? 0)));
    $maxUsuariosPorTipo = max(1, (int) ($usuariosPorTipo->max('total') ?? 1));
    $maxConteudosPorTipo = max(1, (int) ($conteudosPorTipo->max('total') ?? 1));
    $maxAtividadesMes = max(1, (int) ($atividadesPorMes->max('total') ?? 1));
    $maxCertificadosMes = max(1, (int) ($certificadosPorMes->max('total') ?? 1));
    $selectedTraining = isset($treinamentos)
        ? $treinamentos->firstWhere('id', (int) request('training_id'))
        : null;
    $pendencias = max(0, (int) $totalAssistencias - (int) $concluidas);
@endphp

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-900 px-6 py-8 text-white shadow-2xl ring-1 ring-white/10 md:px-10">
        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-10 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold tracking-wide text-white/90 ring-1 ring-white/15 backdrop-blur">
                    <i class="fas fa-shield-alt"></i>
                    Painel executivo em tempo real
                </div>
                <div>
                    <h1 class="text-4xl font-black tracking-tight md:text-5xl">Auditoria gerencial com leitura imediata</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-white/80 md:text-lg">
                        Um painel para gestão enxergar, em segundos, quem está participando, quais conteúdos geram mais impacto, quanto tempo foi realmente consumido e onde estão os gargalos da operação.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-white/90">
                    <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15">Tempo em HH:MM:SS</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15">Participações registradas</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15">Conteúdos e usuários</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15">Filtros por período e treinamento</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:w-full">
                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 md:p-8 backdrop-blur ring-1 ring-white/15 text-center">
                    <p class="text-xs uppercase tracking-wide text-white/60">Usuários</p>
                    <p class="mt-3 sm:mt-4 text-3xl sm:text-4xl md:text-5xl font-black leading-none">{{ $totalUsuarios }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 md:p-8 backdrop-blur ring-1 ring-white/15 text-center">
                    <p class="text-xs uppercase tracking-wide text-white/60">Conteúdos</p>
                    <p class="mt-3 sm:mt-4 text-3xl sm:text-4xl md:text-5xl font-black leading-none">{{ $totalTreinamentos }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 md:p-8 backdrop-blur ring-1 ring-white/15 text-center">
                    <p class="text-xs uppercase tracking-wide text-white/60">Certificados</p>
                    <p class="mt-3 sm:mt-4 text-3xl sm:text-4xl md:text-5xl font-black leading-none">{{ $totalCertificados }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 md:p-8 backdrop-blur ring-1 ring-white/15 text-center">
                    <p class="text-xs uppercase tracking-wide text-white/60">Tempo</p>
                    <p class="mt-3 sm:mt-4 font-mono text-lg sm:text-xl md:text-2xl lg:text-2xl font-black leading-none tracking-tight whitespace-nowrap">{{ $tempoTotalFormatado }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 shadow-sm">
        <p class="text-sm font-bold text-blue-900 mb-2">Legenda rápida</p>
        <p class="text-sm leading-6 text-blue-900/80">
            <strong>Tipo de usuário</strong> filtra o perfil; <strong>Usuário</strong> aponta um cadastro específico; <strong>Tipo de conteúdo</strong> e <strong>Treinamento</strong> refinam o foco da auditoria; <strong>Período</strong> concentra os dados no intervalo escolhido.
            Os números de tempo seguem o mesmo padrão do certificado: <strong>HH:MM:SS</strong>. Quando aparecer <strong>participações</strong>, isso representa registros de progresso, não pessoas únicas.
        </p>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500">Taxa de conclusão</p>
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800">Impacto</span>
            </div>
            <div class="mt-5 relative mx-auto flex h-40 w-40 items-center justify-center rounded-full" style="background: conic-gradient(#7c3aed 0 {{ $completionPct }}%, #e2e8f0 {{ $completionPct }}% 100%);">
                <div class="flex h-28 w-28 flex-col items-center justify-center rounded-full bg-white shadow-inner">
                    <span class="text-3xl font-black text-slate-900">{{ $taxaGeral }}%</span>
                    <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Concluídos</span>
                </div>
            </div>
            <p class="mt-4 text-center text-xs text-slate-500">{{ $concluidas }} de {{ $totalAssistencias }} registros finalizados</p>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500">Engajamento</p>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Base ativa</span>
            </div>
            <p class="mt-4 text-4xl font-black text-slate-900">{{ $taxaEngajamento }}%</p>
            <p class="text-sm text-slate-500">Usuários com pelo menos uma participação</p>
            <div class="mt-5 h-3 rounded-full bg-slate-100">
                <div class="h-3 rounded-full bg-emerald-500" style="width: {{ $engagementPct }}%"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $usuariosComProgresso }} de {{ $totalUsuarios }} usuários</p>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500">Certificação</p>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Evidência</span>
            </div>
            <p class="mt-4 text-4xl font-black text-slate-900">{{ $taxaCertificacao }}%</p>
            <p class="text-sm text-slate-500">Usuários com pelo menos um certificado</p>
            <div class="mt-5 h-3 rounded-full bg-slate-100">
                <div class="h-3 rounded-full bg-amber-500" style="width: {{ $certificationPct }}%"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $usuariosComCertificados }} de {{ $totalUsuarios }} usuários</p>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500">Usuários sem progresso</p>
                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Atenção</span>
            </div>
            <p class="mt-4 text-4xl font-black text-slate-900">{{ $usuariosSemTreinamento }}</p>
            <p class="text-sm text-slate-500">Pessoas sem nenhum registro de treinamento</p>
            <div class="mt-5 h-3 rounded-full bg-slate-100">
                <div class="h-3 rounded-full bg-rose-500" style="width: {{ $totalUsuarios > 0 ? (($usuariosSemTreinamento / $totalUsuarios) * 100) : 0 }}%"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $totalUsuarios > 0 ? number_format(($usuariosSemTreinamento / $totalUsuarios) * 100, 1, ',', '.') : '0,0' }}% da base</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-12">
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100 lg:col-span-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Saúde da operação</h2>
                    <p class="mt-1 text-sm text-slate-500">Visão rápida do comportamento da base filtrada.</p>
                </div>
                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-right">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tempo médio</p>
                    <p class="text-2xl font-black text-slate-900">{{ $tempoMedioFormatado }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-sm font-semibold text-slate-500">Participações registradas</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $totalAssistencias }}</p>
                    <p class="mt-1 text-xs text-slate-500">Cada linha da base representa uma participação monitorada.</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-sm font-semibold text-slate-500">Pendências</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $pendencias }}</p>
                    <p class="mt-1 text-xs text-slate-500">Registros iniciados, mas ainda não concluídos.</p>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Concluídos</span>
                        <span class="font-bold text-slate-900">{{ $taxaGeral }}%</span>
                    </div>
                    <div class="h-3 rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-violet-600" style="width: {{ $completionPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Engajamento</span>
                        <span class="font-bold text-slate-900">{{ $taxaEngajamento }}%</span>
                    </div>
                    <div class="h-3 rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-emerald-500" style="width: {{ $engagementPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Certificação</span>
                        <span class="font-bold text-slate-900">{{ $taxaCertificacao }}%</span>
                    </div>
                    <div class="h-3 rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-amber-500" style="width: {{ $certificationPct }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100 lg:col-span-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Distribuição e conteúdo</h2>
                    <p class="mt-1 text-sm text-slate-500">O que está puxando a atenção da base e onde está o volume.</p>
                </div>
                <div class="rounded-2xl bg-purple-50 px-4 py-3 text-right ring-1 ring-purple-100">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-500">Filtro atual</p>
                    <p class="text-sm font-bold text-slate-800">{{ request('training_tipo') ? strtoupper(request('training_tipo')) : 'Todos os tipos' }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Usuários por perfil</h3>
                        <span class="text-xs text-slate-500">{{ $totalUsuarios }} total</span>
                    </div>
                    <div class="space-y-4">
                        @forelse($usuariosPorTipo as $tipo)
                            @php
                                $percentual = $totalUsuarios > 0 ? ($tipo->total / $totalUsuarios) * 100 : 0;
                            @endphp
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $tipo->tipo_usuario)) }}</span>
                                    <span class="font-bold text-slate-900">{{ $tipo->total }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-white">
                                    <div class="h-2.5 rounded-full bg-purple-600" style="width: {{ $percentual }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum dado disponível.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Conteúdos por tipo</h3>
                        <span class="text-xs text-slate-500">{{ $totalTreinamentos }} conteúdos</span>
                    </div>
                    <div class="space-y-4">
                        @forelse($conteudosPorTipo as $tipo)
                            @php
                                $percentualConteudo = $totalTreinamentos > 0 ? ($tipo->total / $totalTreinamentos) * 100 : 0;
                            @endphp
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $tipo->tipo)) }}</span>
                                    <span class="font-bold text-slate-900">{{ $tipo->total }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-white">
                                    <div class="h-2.5 rounded-full bg-blue-600" style="width: {{ $percentualConteudo }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum conteúdo cadastrado.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Filtros inteligentes</h2>
                <p class="text-sm text-slate-500">Use o período para focar a operação, ou traga um treinamento específico para comparar conteúdo, usuários e tempo.</p>
            </div>
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="rounded-xl bg-slate-100 px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-200">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-purple-900 px-4 py-2 font-semibold text-white transition hover:bg-purple-800">Dashboard</a>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            O filtro de <strong>Treinamento</strong> detalha um conteúdo específico. O filtro de <strong>Período</strong> restringe os registros de início e emissão para a janela escolhida.
        </div>

        <form method="GET" action="{{ route('relatorios.auditoria') }}" class="mt-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de usuário</label>
                    <select name="tipo_usuario" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none ring-0 focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Usuário</label>
                    <select name="usuario_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @if(request('usuario_id') == $u->id) selected @endif>{{ $u->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de conteúdo</label>
                    <select name="training_tipo" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                        <option value="">Todos</option>
                        <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                        <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Treinamento</label>
                    <select name="training_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if(request('training_id') == $training->id) selected @endif>{{ $training->titulo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Período inicial</label>
                    <input type="date" name="periodo_inicio" value="{{ request('periodo_inicio') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Período final</label>
                    <input type="date" name="periodo_fim" value="{{ request('periodo_fim') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-200">
                </div>

                <div class="flex items-end gap-2 xl:col-span-2">
                    <button type="submit" class="flex-1 rounded-xl bg-purple-900 px-5 py-3 font-semibold text-white transition hover:bg-purple-800">
                        <i class="fas fa-chart-pie mr-2"></i>Aplicar filtros
                    </button>
                    <a href="{{ route('relatorios.auditoria') }}" class="rounded-xl bg-slate-200 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-300">
                        Limpar
                    </a>
                </div>
            </div>
        </form>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Evolução mensal</h2>
                    <p class="text-sm text-slate-500">Comparativo entre participações e certificados emitidos no período filtrado.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Participações por mês</h3>
                        <span class="text-xs text-slate-500">{{ $maxAtividadesMes }} pico</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($atividadesPorMes as $item)
                            @php
                                $percentualMes = $maxAtividadesMes > 0 ? ($item->total / $maxAtividadesMes) * 100 : 0;
                            @endphp
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y') }}</span>
                                    <span class="font-bold text-slate-900">{{ $item->total }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-white">
                                    <div class="h-2 rounded-full bg-violet-600" style="width: {{ $percentualMes }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhuma participação encontrada.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Certificados por mês</h3>
                        <span class="text-xs text-slate-500">{{ $maxCertificadosMes }} pico</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($certificadosPorMes as $item)
                            @php
                                $percentualCert = $maxCertificadosMes > 0 ? ($item->total / $maxCertificadosMes) * 100 : 0;
                            @endphp
                            <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y') }}</span>
                                    <span class="font-bold text-slate-900">{{ $item->total }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-white">
                                    <div class="h-2 rounded-full bg-cyan-600" style="width: {{ $percentualCert }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum certificado encontrado.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Usuários em destaque</h2>
                    <p class="text-sm text-slate-500">Ranking da base com maior volume de consumo e conclusão.</p>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Usuário</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Participações</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Conclusões</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Tempo exato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosEmDestaque as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ optional($item->user)->nome ?? 'Usuário removido' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ optional($item->user)->tipo_usuario ? ucfirst(str_replace('_', ' ', optional($item->user)->tipo_usuario)) : 'Sem tipo' }}</div>
                                    <div class="text-xs text-slate-400">{{ $item->user ? $item->user->getCpfFormatted() : '—' }}</div>
                                </td>
                                <td class="px-3 py-4 text-center font-semibold text-slate-800">{{ $item->assistencias }}</td>
                                <td class="px-3 py-4 text-center font-semibold text-slate-800">{{ $item->concluidas }}</td>
                                <td class="px-3 py-4 text-center font-mono text-slate-800">{{ gmdate('H:i:s', (int) ($item->tempo_total_assistido ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-sm text-slate-500">Sem dados para destacar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Treinamentos com maior impacto</h2>
                <p class="text-sm text-slate-500">Volume, conclusão e tempo exato consumido por conteúdo.</p>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Treinamento</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Participações</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Conclusões</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Taxa</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Tempo exato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($treinamentosMaisAssistidos as $training)
                        @php
                            $taxa = $training->progress_count > 0 ? ($training->concluidos_count / $training->progress_count) * 100 : 0;
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-slate-900">{{ $training->titulo }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ ucfirst($training->tipo) }} • {{ $training->carga_horaria }} min</div>
                            </td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-800">{{ $training->progress_count }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-800">{{ $training->concluidos_count }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">{{ number_format($taxa, 1, ',', '.') }}%</span>
                            </td>
                            <td class="px-4 py-4 text-center font-mono text-slate-800">{{ gmdate('H:i:s', (int) ($training->tempo_total_assistido ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum treinamento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Mapa de oportunidade</h2>
                    <p class="text-sm text-slate-500">Usuários sem nenhum progresso registrado na base filtrada.</p>
                </div>
                <div class="rounded-2xl bg-rose-50 px-4 py-3 text-right ring-1 ring-rose-100">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-500">Total</p>
                    <p class="text-2xl font-black text-slate-900">{{ $usuariosSemTreinamento }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                @forelse($usuariosSemTreinamentoLista as $usuario)
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $usuario->nome }}</div>
                            <div class="text-xs text-slate-500">{{ $usuario->getCpfFormatted() }} • {{ ucfirst(str_replace('_', ' ', $usuario->tipo_usuario ?? 'sem_tipo')) }}</div>
                        </div>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Sem progresso</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhum usuário nesta condição para o filtro atual.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Leitura gerencial</h2>
                    <p class="text-sm text-slate-500">O que o gestor precisa enxergar antes de tomar decisão.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-violet-50 p-4 ring-1 ring-violet-100">
                    <p class="text-sm font-semibold text-violet-800">Participações</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $totalAssistencias }}</p>
                    <p class="mt-1 text-xs text-slate-600">Volume total de registros de progresso no intervalo filtrado.</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <p class="text-sm font-semibold text-emerald-800">Conclusões</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $concluidas }}</p>
                    <p class="mt-1 text-xs text-slate-600">Ações finalizadas com sucesso.</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                    <p class="text-sm font-semibold text-amber-800">Tempo exato</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $tempoTotalFormatado }}</p>
                    <p class="mt-1 text-xs text-slate-600">Mesmo formato usado no certificado oficial.</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-sm font-semibold text-slate-800">Usuários ativos</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $usuariosAtivos }}</p>
                    <p class="mt-1 text-xs text-slate-600">Base operacional disponível para análise.</p>
                </div>
            </div>
        </section>
    </div>

    <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">
        Este painel foi pensado para abrir com impacto visual, mas sem perder o detalhe operacional: o gestor enxerga onde a base cresce, onde a conclusão trava, quais conteúdos concentram uso e qual público ainda não entrou na jornada.
    </div>
</div>
@endsection
