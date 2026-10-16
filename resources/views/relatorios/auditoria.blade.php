@extends('layout')

@section('title', 'Relatório de Auditoria')

@section('content')
@php
    $f = fn ($campo, $padrao = null) => request($campo, $padrao);
    $qs = request()->getQueryString();
    $conformidadePct = max(0, min(100, (float) ($resumo['indice_conformidade'] ?? 0)));
    $tempoTotal = gmdate('H:i:s', max(0, (int) ($resumo['tempo_total_segundos'] ?? 0)));
    $tempoMedio = gmdate('H:i:s', max(0, (int) ($resumo['tempo_medio_segundos'] ?? 0)));
    $maxParticipacoesMes = max(1, (int) ($evolucao['participacoes']->max('total') ?? 1));
    $maxCertificadosMes = max(1, (int) ($evolucao['certificados']->max('total') ?? 1));
    $situacaoLabels = [
        'concluido_em_dia' => ['label' => 'Concluído em dia', 'classe' => 'bg-emerald-100 text-emerald-800'],
        'concluido_atraso' => ['label' => 'Concluído em atraso', 'classe' => 'bg-amber-100 text-amber-800'],
        'andamento' => ['label' => 'Em andamento', 'classe' => 'bg-sky-100 text-sky-800'],
        'nao_iniciado' => ['label' => 'Não iniciado', 'classe' => 'bg-slate-100 text-slate-600'],
        'vencido' => ['label' => 'Prazo expirado', 'classe' => 'bg-rose-100 text-rose-800'],
    ];
@endphp

<div class="max-w-[96rem] mx-auto px-4 py-8 space-y-8">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-900 px-6 py-8 text-white shadow-2xl ring-1 ring-white/10 md:px-10">
        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-10 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold tracking-wide text-white/90 ring-1 ring-white/15">
                    <i class="fas fa-shield-alt"></i>
                    Auditoria gerencial de alta conformidade
                </div>
                <h1 class="text-3xl font-black tracking-tight md:text-4xl">Conformidade de treinamentos, ausências justificadas e trilha de evidências</h1>
                <p class="max-w-2xl text-sm leading-7 text-white/80 md:text-base">
                    Base auditada com critérios explícitos, status por usuário × conteúdo, tratamento de férias, folgas, atestados, licenças, isenções e prazos estendidos.
                    Exportações com data/hora, filtros aplicados e SHA-256 do recorte de dados.
                </p>
                <p class="text-xs text-white/60">Gerado em {{ now()->format('d/m/Y H:i:s') }} por {{ auth()->user()->nome }} — versão {{ app_version() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('relatorios.auditoria.csv') }}{{ $qs ? '?'.$qs : '' }}"
                   class="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-400">
                    <i class="fas fa-file-csv mr-1"></i> CSV Detalhado
                </a>
                <a href="{{ route('relatorios.auditoria.csv') }}?{{ http_build_query(array_merge(request()->query(), ['tipo_exportacao' => 'ausencias'])) }}"
                   class="rounded-xl bg-sky-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-400">
                    <i class="fas fa-notes-medical mr-1"></i> CSV Ausências
                </a>
                <a href="{{ route('relatorios.auditoria.pdf') }}{{ $qs ? '?'.$qs : '' }}"
                   class="rounded-xl bg-rose-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-rose-400">
                    <i class="fas fa-file-pdf mr-1"></i> PDF Executivo
                </a>
            </div>
        </div>
    </section>

    <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5 text-sm leading-6 text-purple-950">
        <p class="font-bold">Metodologia e transparência (leia antes de usar os números)</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-purple-950/85">
            <li><strong>Exclusões da base:</strong> apenas <strong>super_admin</strong> e <strong>usuários marcados como teste</strong>
                ({{ (int) ($metricasExclusao['total'] ?? 0) }} cadastro(s): {{ (int) ($metricasExclusao['super_admin'] ?? 0) }} super_admin,
                {{ (int) ($metricasExclusao['teste'] ?? 0) }} de teste). Admins, inativos e afastados permanecem visíveis e filtráveis.</li>
            <li><strong>Elegibilidade por conteúdo:</strong> corte por data de cadastro (semana da liberação), público-alvo/atribuição, isenções de férias e férias na data de liberação — mesmas regras de liberação do sistema.</li>
            <li><strong>Prazo efetivo:</strong> extensão concedida pelo gestor quando existir; senão, 23:00 do domingo subsequente à liberação.</li>
            <li><strong>Índice de conformidade:</strong> concluídos dentro do prazo ÷ pares elegíveis.
                @if(($resumo['ausencias']['ferias_pessoas'] ?? 0) > 0)
                    Afastados justificados ficam fora do denominador por padrão ({{ number_format((int) ($resumo['isentos'] ?? 0), 0, ',', '.') }} isento(s) e {{ number_format((int) ($resumo['afastados_liberacao'] ?? 0), 0, ',', '.') }} afastado(s) na liberação) — use o filtro "Incluir afastados no denominador" para o cenário mais rígido.
                @endif</li>
            <li><strong>Período:</strong> aplica-se às participações (data de início), certificados (emissão) e às listas de ausências. A matriz de conformidade reflete o estado atual dos conteúdos filtrados.</li>
            <li><strong>Tempo:</strong> sempre em HH:MM:SS, mesmo padrão do certificado oficial.</li>
        </ul>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Índice de conformidade</p>
            <div class="mt-4 relative mx-auto flex h-36 w-36 items-center justify-center rounded-full" style="background: conic-gradient(#7c3aed 0 {{ $conformidadePct }}%, #e2e8f0 {{ $conformidadePct }}% 100%);">
                <div class="flex h-24 w-24 flex-col items-center justify-center rounded-full bg-white shadow-inner">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($resumo['indice_conformidade'] ?? 0, 1, ',', '.') }}%</span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-slate-500">em dia</span>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-slate-500">{{ number_format($resumo['concluidos_em_dia'] ?? 0, 0, ',', '.') }} de {{ number_format($resumo['pares_elegiveis'] ?? 0, 0, ',', '.') }} pares elegíveis</p>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Base auditada</p>
            <p class="mt-3 text-4xl font-black text-slate-900">{{ number_format($resumo['total_usuarios'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($resumo['total_ativos'] ?? 0, 0, ',', '.') }} ativos • {{ number_format($resumo['total_inativos'] ?? 0, 0, ',', '.') }} inativos</p>
            <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                    <p class="text-2xl font-black text-slate-900">{{ number_format($resumo['total_treinamentos'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-slate-500">Conteúdos no filtro</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                    <p class="text-2xl font-black text-slate-900">{{ number_format($metricasExclusao['total'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-slate-500">Excluídos (super/teste)</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Situação dos pares elegíveis</p>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between"><span class="text-emerald-700 font-semibold">Concluídos em dia</span><span class="font-black text-slate-900">{{ number_format($resumo['concluidos_em_dia'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-amber-700 font-semibold">Concluídos em atraso</span><span class="font-black text-slate-900">{{ number_format($resumo['concluidos_atraso'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-sky-700 font-semibold">Em andamento</span><span class="font-black text-slate-900">{{ number_format($resumo['andamento'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-600 font-semibold">Não iniciados</span><span class="font-black text-slate-900">{{ number_format($resumo['nao_iniciados'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-rose-700 font-semibold">Prazo expirado</span><span class="font-black text-slate-900">{{ number_format($resumo['vencidos'] ?? 0, 0, ',', '.') }}</span></div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Certificados no período</p>
            <p class="mt-3 text-4xl font-black text-slate-900">{{ number_format($resumo['certificados']['total'] ?? 0, 0, ',', '.') }}</p>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between"><span class="text-emerald-700 font-semibold">Válidos</span><span class="font-black">{{ number_format($resumo['certificados']['validos'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-amber-700 font-semibold">Vencendo (30 dias)</span><span class="font-black">{{ number_format($resumo['certificados']['vencendo'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-rose-700 font-semibold">Vencidos</span><span class="font-black">{{ number_format($resumo['certificados']['vencidos'] ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-600 font-semibold">Sem validade</span><span class="font-black">{{ number_format($resumo['certificados']['sem_validade'] ?? 0, 0, ',', '.') }}</span></div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participações no período</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($resumo['participacoes'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Tempo total <span class="font-mono font-bold">{{ $tempoTotal }}</span> • média <span class="font-mono font-bold">{{ $tempoMedio }}</span></p>
        </div>
        <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-100">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Isenções e prazos estendidos</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format(($resumo['isentos'] ?? 0) + ($resumo['ausencias']['isencoes_total'] ?? 0), 0, ',', '.') }} isenções</p>
            <p class="mt-1 text-xs text-slate-600">{{ number_format($resumo['ausencias']['prorrogacoes_total'] ?? 0, 0, ',', '.') }} prorrogação(ões) concedida(s) — justificativas detalhadas abaixo</p>
        </div>
        <div class="rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-100">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Ausências justificadas</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($resumo['ausencias']['ferias_pessoas'] ?? 0, 0, ',', '.') }} em férias</p>
            <p class="mt-1 text-xs text-slate-600">
                {{ number_format($resumo['ausencias']['folgas_dias'] ?? 0, 0, ',', '.') }} folga(s) •
                {{ number_format($resumo['ausencias']['atestados_dias'] ?? 0, 0, ',', '.') }} atestado(s) •
                {{ number_format($resumo['ausencias']['licencas_dias'] ?? 0, 0, ',', '.') }} licença(s) em dias
            </p>
        </div>
        <div class="rounded-2xl bg-rose-50 p-5 ring-1 ring-rose-100">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Afastados na liberação</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($resumo['afastados_liberacao'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-600">Elegíveis ao conteúdo, mas de férias na data de liberação (justificados)</p>
        </div>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Filtros avançados</h2>
                <p class="text-sm text-slate-500">Combine período, perfil, vínculo e conteúdo. Os filtros se aplicam a todas as seções e exportações.</p>
            </div>
            <a href="{{ route('relatorios.auditoria') }}" class="text-sm font-semibold text-purple-800 hover:underline">Limpar filtros</a>
        </div>

        <form method="GET" action="{{ route('relatorios.auditoria') }}" class="mt-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Período inicial</label>
                    <input type="date" name="periodo_inicio" value="{{ $f('periodo_inicio') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Período final</label>
                    <input type="date" name="periodo_fim" value="{{ $f('periodo_fim') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de usuário</label>
                    <select name="tipo_usuario" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        @foreach($userTypes as $tipo)
                            <option value="{{ $tipo }}" @if($f('tipo_usuario') === $tipo) selected @endif>{{ tipo_usuario_label($tipo) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Status do usuário</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        <option value="ativo" @if($f('status') === 'ativo') selected @endif>Ativo</option>
                        <option value="inativo" @if($f('status') === 'inativo') selected @endif>Inativo</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Usuário</label>
                    <select name="usuario_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @if((string) $f('usuario_id') === (string) $u->id) selected @endif>{{ $u->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Busca (nome ou CPF)</label>
                    <input type="text" name="busca" value="{{ $f('busca') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Setor</label>
                    <input type="text" name="setor" value="{{ $f('setor') }}" list="lista-setores" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <datalist id="lista-setores">
                        @foreach($setores as $setor)<option value="{{ $setor }}">@endforeach
                    </datalist>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Cargo</label>
                    <input type="text" name="cargo" value="{{ $f('cargo') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Empresa</label>
                    <input type="text" name="empresa" value="{{ $f('empresa') }}" list="lista-empresas" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <datalist id="lista-empresas">
                        @foreach($empresas as $empresa)<option value="{{ $empresa }}">@endforeach
                    </datalist>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de conteúdo</label>
                    <select name="training_tipo" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        <option value="dss" @if($f('training_tipo') === 'dss') selected @endif>DSS</option>
                        <option value="treinamento" @if($f('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de treinamento</label>
                    <select name="tipo_treinamento" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        <option value="inicial" @if($f('tipo_treinamento') === 'inicial') selected @endif>Inicial</option>
                        <option value="periodico" @if($f('tipo_treinamento') === 'periodico') selected @endif>Periódico</option>
                        <option value="eventual" @if($f('tipo_treinamento') === 'eventual') selected @endif>Eventual</option>
                    </select>
                </div>
                <div class="xl:col-span-3">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Conteúdo específico (abre o detalhe por usuário)</label>
                    <select name="training_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if((string) $f('training_id') === (string) $training->id) selected @endif>{{ $training->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-4 xl:col-span-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="somente_obrigatorios" value="1" @checked($f('somente_obrigatorios'))
                               class="h-4 w-4 rounded border-slate-300 text-purple-800 focus:ring-purple-500">
                        Somente conteúdos obrigatórios
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="incluir_afastados" value="1" @checked($f('incluir_afastados'))
                               class="h-4 w-4 rounded border-slate-300 text-purple-800 focus:ring-purple-500">
                        Incluir afastados/isentos no denominador da conformidade
                    </label>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-purple-900 px-6 py-3 font-semibold text-white transition hover:bg-purple-800">
                    <i class="fas fa-filter mr-2"></i>Aplicar filtros
                </button>
            </div>
        </form>
    </section>

    @if($conteudoSelecionado)
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Detalhe do conteúdo: {{ $conteudoSelecionado->titulo }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ ucfirst($conteudoSelecionado->tipo) }}
                        @if($conteudoSelecionado->tipo_treinamento) • {{ $conteudoSelecionado->tipo_treinamento_label }} @endif
                        • Liberação: {{ optional($conteudoSelecionado->data_liberacao_exibicao)->format('d/m/Y') ?? '—' }}
                        • Prazo: {{ optional($conteudoSelecionado->prazoBaseConclusao())->format('d/m/Y H:i') ?? '—' }}
                        • Nota mínima: {{ (int) ($conteudoSelecionado->nota_minima_aprovacao ?? 70) }}%
                    </p>
                </div>
                <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ $usuariosDoConteudo->total() }} elegível(is)</span>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Usuário</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Situação</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Prazo</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Início</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Conclusão</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Tempo</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Nota</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Certificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosDoConteudo as $usuario)
                            @php
                                $situacao = $situacaoLabels[$usuario->situacao_conteudo] ?? ['label' => '—', 'classe' => 'bg-slate-100 text-slate-600'];
                            @endphp
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-900">{{ $usuario->nome }}</div>
                                    <div class="text-xs text-slate-500">{{ $usuario->getCpfFormatted() }} • {{ tipo_usuario_label($usuario->tipo_usuario) }} • {{ ucfirst($usuario->status) }}</div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $situacao['classe'] }}">{{ $situacao['label'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($usuario->prazo_conteudo)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($usuario->progresso_conteudo?->data_inicio)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($usuario->progresso_conteudo?->data_conclusao)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 text-center font-mono text-xs">{{ gmdate('H:i:s', (int) ($usuario->progresso_conteudo?->tempo_assistido ?? 0)) }}</td>
                                <td class="px-3 py-3 text-center font-bold {{ $usuario->progresso_conteudo?->avaliacao_nota !== null ? ($usuario->progresso_conteudo->avaliacao_aprovada ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-400' }}">
                                    {{ $usuario->progresso_conteudo?->avaliacao_nota !== null ? $usuario->progresso_conteudo->avaliacao_nota.'%' : '—' }}
                                </td>
                                <td class="px-3 py-3 text-center text-xs">
                                    @if($usuario->certificado_conteudo)
                                        <span class="font-mono font-bold text-slate-800">{{ $usuario->certificado_conteudo->codigo_certificado }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-3 py-10 text-center text-sm text-slate-500">Nenhum usuário elegível para este conteúdo com os filtros atuais.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $usuariosDoConteudo->links() }}</div>
        </section>
    @endif

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Conformidade por conteúdo</h2>
                <p class="text-sm text-slate-500">Elegíveis, conclusões dentro/fora do prazo, pendências e justificativas por treinamento.</p>
            </div>
            <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ $conteudos->total() }} conteúdo(s)</span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Conteúdo</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Elegíveis</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Em dia</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Atraso</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Andamento</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Não in.</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Vencidos</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Isentos</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Afastados liberação</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Conformidade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conteudos as $linha)
                        @php
                            $training = $linha['training'];
                            $stats = $linha['stats'];
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-3">
                                <a href="{{ route('relatorios.auditoria', array_merge(request()->query(), ['training_id' => $training->id])) }}" class="font-semibold text-purple-800 hover:underline">{{ $training->titulo }}</a>
                                <div class="text-xs text-slate-500">
                                    {{ ucfirst($training->tipo) }}
                                    @if($training->tipo_treinamento) • {{ $training->tipo_treinamento_label }} @endif
                                    @if($training->obrigatorio) • <span class="font-bold text-rose-700">Obrigatório</span> @endif
                                    • Liberação {{ optional($training->data_liberacao_exibicao)->format('d/m/Y') ?? '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center font-semibold text-slate-800">{{ number_format($stats['elegiveis'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-emerald-700">{{ number_format($stats['em_dia'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-amber-700">{{ number_format($stats['atraso'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center text-sky-700">{{ number_format($stats['andamento'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ number_format($stats['nao_iniciados'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-rose-700">{{ number_format($stats['vencidos'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ number_format($stats['isentos'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ number_format($stats['afastados_liberacao'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $stats['conformidade'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($stats['conformidade'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ number_format($stats['conformidade'], 1, ',', '.') }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-3 py-10 text-center text-sm text-slate-500">Nenhum conteúdo encontrado para os filtros atuais.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $conteudos->links() }}</div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Conformidade por usuário</h2>
                <p class="text-sm text-slate-500">Consolidado dos conteúdos obrigatórios no filtro atual, com ausências justificadas no período.</p>
            </div>
            <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ $usuarios->total() }} usuário(s)</span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Usuário</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Afastamento no período</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Exigíveis</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Em dia</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Atraso</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Andamento</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Não in.</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Vencidos</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Isentos</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Certificados</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Última atividade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        @php $c = $usuario->conformidade; @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">{{ $usuario->nome }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $usuario->getCpfFormatted() }} • {{ tipo_usuario_label($usuario->tipo_usuario) }} •
                                    <span class="{{ $usuario->status === 'ativo' ? 'text-emerald-700' : 'text-slate-500' }}">{{ ucfirst($usuario->status) }}</span>
                                    @if($usuario->setor) • {{ $usuario->setor }} @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">{{ $usuario->afastamento_periodo !== '' ? $usuario->afastamento_periodo : '—' }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-slate-800">{{ $c['elegiveis'] }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-emerald-700">{{ $c['em_dia'] }}</td>
                            <td class="px-3 py-3 text-center font-semibold text-amber-700">{{ $c['atraso'] }}</td>
                            <td class="px-3 py-3 text-center text-sky-700">{{ $c['andamento'] }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $c['nao_iniciados'] }}</td>
                            <td class="px-3 py-3 text-center font-semibold {{ $c['vencidos'] > 0 ? 'text-rose-700' : 'text-slate-500' }}">{{ $c['vencidos'] }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $c['isentos'] }}</td>
                            <td class="px-3 py-3 text-center text-slate-700">{{ $usuario->certificados_total }}</td>
                            <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($usuario->ultima_atividade)->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-3 py-10 text-center text-sm text-slate-500">Nenhum usuário encontrado para os filtros atuais.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $usuarios->links() }}</div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Ausências e justificativas</h2>
                <p class="text-sm text-slate-500">Tudo que explica ausência de atividade no período — com pessoas, datas, dias e responsáveis pelo lançamento.</p>
            </div>
            <a href="{{ route('relatorios.auditoria.csv') }}?{{ http_build_query(array_merge(request()->query(), ['tipo_exportacao' => 'ausencias'])) }}"
               class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Exportar CSV</a>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-amber-950">Férias</h3>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-200">
                        {{ number_format($ausencias['totais']['ferias_pessoas'], 0, ',', '.') }} pessoa(s) • {{ number_format($ausencias['totais']['ferias_dias'], 0, ',', '.') }} dia(s)
                    </span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($ausencias['ferias']->take(10) as $ferias)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-2.5 ring-1 ring-amber-100">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ optional($ferias->user)->nome ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ optional($ferias->user)?->getCpfFormatted() ?? '' }} • {{ $ferias->data_inicio->format('d/m/Y') }} a {{ $ferias->data_fim->format('d/m/Y') }}</div>
                            </div>
                            <span class="text-xs font-bold text-amber-900">{{ $ferias->dias_periodo }}d</span>
                        </div>
                    @empty
                        <p class="text-sm text-amber-900/70">Nenhuma férias no período filtrado.</p>
                    @endforelse
                </div>
                @if($ausencias['ferias']->count() > 10)
                    <p class="mt-3 text-xs text-amber-900/70">Mostrando 10 de {{ $ausencias['ferias']->count() }} registros — exporte o CSV para a lista completa.</p>
                @endif
            </div>

            <div class="rounded-2xl bg-emerald-50 p-5 ring-1 ring-emerald-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-emerald-950">Folgas, atestados e licenças</h3>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-900 ring-1 ring-emerald-200">
                        {{ number_format(($ausencias['totais']['folgas_dias'] ?? 0) + ($ausencias['totais']['atestados_dias'] ?? 0) + ($ausencias['totais']['licencas_dias'] ?? 0), 0, ',', '.') }} dia(s)
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-white px-3 py-1 text-emerald-900 ring-1 ring-emerald-200">Folgas: {{ $ausencias['totais']['folgas_dias'] }}d / {{ $ausencias['totais']['folgas_pessoas'] }} pessoa(s)</span>
                    <span class="rounded-full bg-white px-3 py-1 text-emerald-900 ring-1 ring-emerald-200">Atestados: {{ $ausencias['totais']['atestados_dias'] }}d / {{ $ausencias['totais']['atestados_pessoas'] }} pessoa(s)</span>
                    <span class="rounded-full bg-white px-3 py-1 text-emerald-900 ring-1 ring-emerald-200">Licenças: {{ $ausencias['totais']['licencas_dias'] }}d / {{ $ausencias['totais']['licencas_pessoas'] }} pessoa(s)</span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($ausencias['folgas']->take(10) as $folga)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-2.5 ring-1 ring-emerald-100">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ optional($folga->user)->nome ?? '—' }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $folga->data->format('d/m/Y') }} •
                                    {{ ['folga' => 'Folga', 'atestado' => 'Atestado', 'licenca' => 'Licença'][$folga->tipo] ?? $folga->tipo }}
                                    @if($folga->motivo_folga) • {{ $folga->motivo_folga }} @endif
                                    @if($folga->lancado_por) • lançado por {{ optional($folga->lancadoPor)->nome }} @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-emerald-900/70">Nenhum lançamento no período filtrado.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sky-950">Isenções de conteúdo (férias)</h3>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-sky-900 ring-1 ring-sky-200">{{ $ausencias['totais']['isencoes_total'] }} registro(s)</span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($ausencias['isencoes']->take(10) as $isencao)
                        <div class="rounded-xl bg-white px-4 py-2.5 ring-1 ring-sky-100">
                            <div class="text-sm font-semibold text-slate-900">{{ optional($isencao->user)->nome ?? '—' }}</div>
                            <div class="text-xs text-slate-500">
                                {{ optional($isencao->training)->titulo ?? '—' }} • {{ $isencao->data_inicio->format('d/m/Y') }} a {{ $isencao->data_fim->format('d/m/Y') }}
                                @if($isencao->motivo) • {{ $isencao->motivo }} @endif
                                @if($isencao->created_by) • por {{ optional($isencao->creator)->nome }} @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-sky-900/70">Nenhuma isenção no período filtrado.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-indigo-50 p-5 ring-1 ring-indigo-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-indigo-950">Prazos estendidos (prorrogações)</h3>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-indigo-900 ring-1 ring-indigo-200">{{ $ausencias['totais']['prorrogacoes_total'] }} concessão(ões)</span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($ausencias['prorrogacoes']->take(10) as $prorrogacao)
                        <div class="rounded-xl bg-white px-4 py-2.5 ring-1 ring-indigo-100">
                            <div class="text-sm font-semibold text-slate-900">{{ optional($prorrogacao->user)->nome ?? '—' }}</div>
                            <div class="text-xs text-slate-500">
                                {{ optional($prorrogacao->training)->titulo ?? '—' }} • novo prazo {{ optional($prorrogacao->prazo_estendido_ate)->format('d/m/Y H:i') }}
                                @if($prorrogacao->prazo_estendido_motivo) • Motivo: {{ $prorrogacao->prazo_estendido_motivo }} @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-indigo-900/70">Nenhuma prorrogação no período filtrado.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <h2 class="text-2xl font-black text-slate-900">Participações por mês</h2>
            <p class="text-sm text-slate-500">Registros de progresso iniciados em cada mês (filtro atual).</p>
            <div class="mt-5 space-y-3">
                @forelse($evolucao['participacoes'] as $item)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y') }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item->total, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-slate-100">
                            <div class="h-2.5 rounded-full bg-violet-600" style="width: {{ ($item->total / $maxParticipacoesMes) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sem participações no recorte.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <h2 class="text-2xl font-black text-slate-900">Certificados emitidos por mês</h2>
            <p class="text-sm text-slate-500">Certificados válidos emitidos em cada mês (filtro atual).</p>
            <div class="mt-5 space-y-3">
                @forelse($evolucao['certificados'] as $item)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y') }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item->total, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-slate-100">
                            <div class="h-2.5 rounded-full bg-cyan-600" style="width: {{ ($item->total / $maxCertificadosMes) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sem certificados no recorte.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if($podeVerTrilha && $trilha)
        <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Trilha de auditoria (ações no sistema)</h2>
                    <p class="text-sm text-slate-500">Eventos registrados em audit_logs no período filtrado — logins, alterações, exportações e downloads.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ number_format($trilha['total'], 0, ',', '.') }} evento(s)</span>
                    <a href="{{ route('auditoria.index', array_filter(['inicio' => request('periodo_inicio'), 'fim' => request('periodo_fim')])) }}"
                       class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-700">Abrir trilha completa</a>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
                    <h3 class="font-bold text-slate-900">Eventos por tipo</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($trilha['por_evento'] as $evento => $total)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">{{ ucfirst(str_replace('_', ' ', (string) $evento)) }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Sem eventos no período.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
                    <h3 class="font-bold text-slate-900">Módulos mais acionados</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($trilha['por_modulo'] as $modulo => $total)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">{{ ucfirst(str_replace('_', ' ', (string) $modulo)) }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Sem eventos de módulo no período.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-100">
                    <h3 class="font-bold text-slate-900">Usuários mais ativos na trilha</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($trilha['top_usuarios'] as $linha)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">{{ $linha->nome }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($linha->total, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Sem eventos de usuário no período.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Data/Hora</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Usuário</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Evento</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Módulo</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trilha['recentes'] as $log)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2.5 font-mono text-xs text-slate-700">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                <td class="px-3 py-2.5 text-sm text-slate-800">{{ optional($log->user)->nome ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-sm text-slate-800">{{ $log->evento_label }}</td>
                                <td class="px-3 py-2.5 text-sm text-slate-600">{{ $log->modulo_label }}</td>
                                <td class="px-3 py-2.5 text-xs text-slate-600">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @elseif(! $podeVerTrilha)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            A seção de trilha de auditoria (audit_logs) é exibida para usuários com a permissão <strong>auditoria</strong>.
        </div>
    @endif

    <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-sm leading-6 text-slate-600">
        <strong>Nota metodológica final:</strong> este relatório exclui somente super_admin e usuários marcados como teste; qualquer outro grupo aparece na base e pode ser isolado pelos filtros.
        Ausências justificadas (férias, folgas, atestados, licenças, isenções e prorrogações) são listadas com autor e motivo para que nenhuma pendência seja lida de forma indevida.
        As exportações CSV/PDF registram data/hora, autor, filtros aplicados e o SHA-256 do recorte de dados — o mesmo valor nos dois arquivos para o mesmo conjunto de filtros.
    </div>
</div>
@endsection
