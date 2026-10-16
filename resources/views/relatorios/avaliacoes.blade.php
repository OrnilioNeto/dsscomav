@extends('layout')

@section('title', 'Relatório de Avaliações e Notas')

@section('content')
@php
    $f = fn ($campo, $padrao = null) => request($campo, $padrao);
    $tempoTotal = gmdate('H:i:s', max(0, (int) ($resumo['tempo_total_segundos'] ?? 0)));
    $mediaNotas = $resumo['media_notas'] !== null ? number_format($resumo['media_notas'], 1, ',', '.') : '—';
    $taxa = number_format($resumo['taxa_aprovacao'] ?? 0, 1, ',', '.');
    $desempenho = $desempenhoPorTreinamento ?? collect();
    $queryString = request()->getQueryString();
@endphp

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-indigo-950 to-blue-900 px-6 py-8 text-white shadow-2xl ring-1 ring-white/10 md:px-10">
        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-cyan-500/20 blur-3xl"></div>
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold tracking-wide text-white/90 ring-1 ring-white/15">
                    <i class="fas fa-graduation-cap"></i>
                    Relatório gerencial de avaliações
                </div>
                <h1 class="text-3xl font-black tracking-tight md:text-4xl">Avaliações, notas e resultados por usuário e treinamento</h1>
                <p class="max-w-2xl text-sm leading-7 text-white/80 md:text-base">
                    Notas das avaliações, aprovados e reprovados, tentativas, datas e horários exatos de início e conclusão, tempo assistido e certificado emitido — com histórico de submissões para fins de auditoria.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('relatorios.avaliacoes.csv') }}{{ $queryString ? '?'.$queryString : '' }}"
                   class="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-400">
                    <i class="fas fa-file-csv mr-1"></i> CSV Resultados
                </a>
                <a href="{{ route('relatorios.avaliacoes.csv') }}?{{ http_build_query(array_merge(request()->query(), ['tipo_exportacao' => 'tentativas'])) }}"
                   class="rounded-xl bg-sky-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-sky-400">
                    <i class="fas fa-history mr-1"></i> CSV Tentativas
                </a>
                <a href="{{ route('relatorios.avaliacoes.pdf') }}{{ $queryString ? '?'.$queryString : '' }}"
                   class="rounded-xl bg-rose-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-rose-400">
                    <i class="fas fa-file-pdf mr-1"></i> PDF Executivo
                </a>
            </div>
        </div>
    </section>

    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-sm leading-6 text-indigo-900">
        <p class="font-bold">Base de cálculo e transparência</p>
        <p class="mt-1 text-indigo-900/85">
            Este relatório desconsidera apenas <strong>super_admin</strong> e <strong>usuários marcados como teste</strong>
            ({{ (int) ($metricasExclusao['total'] ?? 0) }} cadastro(s) fora da base:
            {{ (int) ($metricasExclusao['super_admin'] ?? 0) }} super_admin e
            {{ (int) ($metricasExclusao['teste'] ?? 0) }} de teste).
            Notas seguem a escala do treinamento (0–100) com o critério mínimo de aprovação configurado.
            O resultado exibido é o <strong>último estado registrado</strong> da avaliação; o histórico completo de tentativas está na tabela inferior e nos CSVs.
        </p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Resultados no filtro</p>
            <p class="mt-3 text-4xl font-black text-slate-900">{{ number_format($resumo['total_resultados'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($resumo['total_com_nota'], 0, ',', '.') }} com nota • {{ number_format($resumo['total_sem_nota'], 0, ',', '.') }} sem nota</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Aprovação</p>
            <p class="mt-3 text-4xl font-black text-emerald-600">{{ $taxa }}%</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($resumo['total_aprovados'], 0, ',', '.') }} aprovados • {{ number_format($resumo['total_reprovados'], 0, ',', '.') }} reprovados</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Média das notas</p>
            <p class="mt-3 text-4xl font-black text-indigo-600">{{ $mediaNotas }}</p>
            <p class="mt-1 text-xs text-slate-500">Considera apenas avaliações com nota registrada</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-100">
            <p class="text-sm font-semibold text-slate-500">Tentativas / Tempo</p>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format($resumo['tentativas_registradas'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Tempo assistido no filtro: <span class="font-mono font-bold">{{ $tempoTotal }}</span></p>
        </div>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Filtros avançados</h2>
                <p class="text-sm text-slate-500">O período considera a data de conclusão (padrão) ou de início, conforme a base selecionada.</p>
            </div>
            <a href="{{ route('relatorios.avaliacoes') }}" class="text-sm font-semibold text-indigo-700 hover:underline">Limpar filtros</a>
        </div>

        <form method="GET" action="{{ route('relatorios.avaliacoes') }}" class="mt-5 space-y-4">
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
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Base da data</label>
                    <select name="base_data" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="conclusao" @if($f('base_data', 'conclusao') === 'conclusao') selected @endif>Data de conclusão</option>
                        <option value="inicio" @if($f('base_data') === 'inicio') selected @endif>Data de início</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Resultado</label>
                    <select name="resultado" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        <option value="aprovado" @if($f('resultado') === 'aprovado') selected @endif>Aprovados</option>
                        <option value="reprovado" @if($f('resultado') === 'reprovado') selected @endif>Reprovados</option>
                        <option value="sem_nota" @if($f('resultado') === 'sem_nota') selected @endif>Sem nota registrada</option>
                        <option value="concluido" @if($f('resultado') === 'concluido') selected @endif>Concluídos</option>
                        <option value="em_andamento" @if($f('resultado') === 'em_andamento') selected @endif>Em andamento</option>
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
                    <input type="text" name="busca" value="{{ $f('busca') }}" placeholder="Ex.: Maria ou 12345678901" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
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
                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Treinamento</label>
                    <select name="training_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" @if((string) $f('training_id') === (string) $training->id) selected @endif>{{ $training->titulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Nota mínima (%)</label>
                    <input type="number" name="nota_min" min="0" max="100" value="{{ $f('nota_min') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Nota máxima (%)</label>
                    <input type="number" name="nota_max" min="0" max="100" value="{{ $f('nota_max') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Setor</label>
                    <input type="text" name="setor" value="{{ $f('setor') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Cargo</label>
                    <input type="text" name="cargo" value="{{ $f('cargo') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Empresa</label>
                    <input type="text" name="empresa" value="{{ $f('empresa') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Eventos no histórico</label>
                    <select name="evento_avaliacao" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                        @foreach($eventos as $valor => $label)
                            <option value="{{ $valor }}" @if($f('evento_avaliacao', 'submetidas') === $valor) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-4 xl:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="somente_com_prova" value="1" @checked($f('somente_com_prova'))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-700 focus:ring-indigo-500">
                        Somente conteúdos com prova
                    </label>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-indigo-900 px-6 py-3 font-semibold text-white transition hover:bg-indigo-800">
                    <i class="fas fa-filter mr-2"></i>Aplicar filtros
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Desempenho por treinamento</h2>
                <p class="text-sm text-slate-500">Média de notas, aprovação e volume por conteúdo no filtro atual.</p>
            </div>
            <span class="rounded-2xl bg-indigo-50 px-4 py-3 text-sm font-bold text-indigo-800 ring-1 ring-indigo-100">
                {{ $desempenho->count() }} conteúdo(s)
            </span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Treinamento</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Participações</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Com nota</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Aprovados</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Média</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Taxa de aprovação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($desempenho->take(15) as $linha)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $linha['training']->titulo }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ ucfirst($linha['training']->tipo) }}
                                    @if($linha['training']->tipo_treinamento) • {{ $linha['training']->tipo_treinamento_label }} @endif
                                    • Nota mínima: {{ (int) ($linha['training']->nota_minima_aprovacao ?? 70) }}%
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ number_format($linha['total'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ number_format($linha['com_nota'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-emerald-700">{{ number_format($linha['aprovados'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-bold text-indigo-700">{{ $linha['media'] !== null ? number_format($linha['media'], 1, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">{{ number_format($linha['taxa_aprovacao'], 1, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum resultado para o filtro atual.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($desempenho->count() > 15)
                <p class="mt-3 text-xs text-slate-500">Mostrando os 15 conteúdos com mais notas. Exporte o CSV para a visão completa.</p>
            @endif
        </div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Resultados por usuário × treinamento</h2>
                <p class="text-sm text-slate-500">Último estado da avaliação, tentativas, datas/horas e certificado emitido.</p>
            </div>
            <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ $resultados->total() }} registro(s)</span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Usuário</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Treinamento</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Nota</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Resultado</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Tentativas</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Início</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Conclusão</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Tempo</th>
                        <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Certificado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resultados as $progresso)
                        @php
                            $certificado = $progresso->certificado_anexado ?? null;
                            $aprovado = (bool) $progresso->avaliacao_aprovada;
                            $temNota = $progresso->avaliacao_nota !== null;
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">{{ optional($progresso->user)->nome ?? 'Usuário removido' }}</div>
                                <div class="text-xs text-slate-500">{{ optional($progresso->user)?->getCpfFormatted() ?? '—' }} • {{ tipo_usuario_label(optional($progresso->user)->tipo_usuario) }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-800">{{ optional($progresso->training)->titulo ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ ucfirst(optional($progresso->training)->tipo ?? '') }} @if(optional($progresso->training)->tipo_treinamento) • {{ ucfirst($progresso->training->tipo_treinamento) }} @endif</div>
                            </td>
                            <td class="px-3 py-3 text-center font-bold {{ $temNota ? ($aprovado ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-400' }}">
                                {{ $temNota ? $progresso->avaliacao_nota.'%' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($aprovado)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Aprovado</span>
                                @elseif($temNota)
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Reprovado</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Pendente</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-semibold text-slate-700">{{ (int) ($progresso->avaliacao_tentativas ?? 0) }}</td>
                            <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($progresso->data_inicio)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-3 py-3 text-center text-xs text-slate-600">{{ optional($progresso->data_conclusao)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-3 py-3 text-center font-mono text-xs text-slate-800">{{ gmdate('H:i:s', (int) ($progresso->tempo_assistido ?? 0)) }}</td>
                            <td class="px-3 py-3 text-center text-xs">
                                @if($certificado)
                                    <div class="font-mono font-bold text-slate-800">{{ $certificado->codigo_certificado }}</div>
                                    <div class="text-slate-500">{{ optional($certificado->data_emissao)->format('d/m/Y H:i') }}</div>
                                    @php
                                        $statusValidade = $certificado->status_validade;
                                    @endphp
                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-bold
                                        {{ $statusValidade === 'valido' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $statusValidade === 'vencendo' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $statusValidade === 'vencido' ? 'bg-rose-100 text-rose-800' : '' }}
                                        {{ $statusValidade === 'sem_validade' ? 'bg-slate-100 text-slate-600' : '' }}">
                                        {{ ['valido' => 'Válido', 'vencendo' => 'Vencendo', 'vencido' => 'Vencido', 'sem_validade' => 'Sem validade'][$statusValidade] ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-10 text-center text-sm text-slate-500">Nenhum resultado encontrado para os filtros selecionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $resultados->links() }}</div>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Histórico de tentativas de avaliação</h2>
                <p class="text-sm text-slate-500">Cada submissão registrada com data e hora exatas — evidência de auditoria.</p>
            </div>
            <span class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">{{ $tentativas->total() }} evento(s)</span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Data/Hora</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Usuário</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Treinamento</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Evento</th>
                        <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-[0.15em] text-slate-500">Detalhe</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tentativas as $log)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-3 font-mono text-xs text-slate-700">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900 text-sm">{{ optional($log->user)->nome ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ optional($log->user)?->getCpfFormatted() ?? '' }}</div>
                            </td>
                            <td class="px-3 py-3 text-sm text-slate-800">{{ optional($log->training)->titulo ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold
                                    {{ $log->evento === 'avaliacao_submetida' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $log->evento === 'avaliacao_reset' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $log->evento === 'avaliacao_senha_invalida' ? 'bg-rose-100 text-rose-800' : '' }}">
                                    {{ ['avaliacao_submetida' => 'Submetida', 'avaliacao_reset' => 'Reset', 'avaliacao_senha_invalida' => 'Senha inválida'][$log->evento] ?? $log->evento }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">{{ $log->detalhe ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-10 text-center text-sm text-slate-500">Nenhuma tentativa registrada no filtro atual.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $tentativas->links() }}</div>
    </section>

    <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-sm leading-6 text-slate-600">
        <strong>Nota metodológica:</strong> a nota exibida é a última registrada em <em>user_progress.avaliacao_nota</em> (escala 0–100).
        Reprovações duplas zeram a nota para nova tentativa após reassistir — por isso o histórico de tentativas (training_logs) é a evidência completa.
        Os arquivos CSV e PDF incluem data/hora de geração, filtros aplicados e o SHA-256 do recorte de dados.
        Base com exclusão apenas de super_admin e usuários de teste.
    </div>
</div>
@endsection
