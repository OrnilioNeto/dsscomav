@extends('layout')

@section('title', 'Folgas — Banco de Folgas & Escala 6x1')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-calendar-day text-emerald-700 mr-2"></i>Folgas
        </h1>
        <div class="flex flex-wrap gap-2">
            @if(Auth::user()->hasPermission('folgas', 'edit'))
            <button onclick="openImportModal()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm">
                <i class="fas fa-file-csv mr-1"></i>Importar CSV
            </button>
            <button onclick="openAjusteModal()" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition text-sm">
                <i class="fas fa-sliders-h mr-1"></i>Ajustar Saldo
            </button>
            <form action="{{ route('admin.folgas.recalcular') }}" method="POST" class="inline" onsubmit="return confirm('Recalcular todos os saldos do mês?')">
                @csrf
                <input type="hidden" name="month" value="{{ $mes }}">
                <input type="hidden" name="year" value="{{ $ano }}">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                    <i class="fas fa-sync-alt mr-1"></i>Recalcular
                </button>
            </form>
            @endif
            <a href="{{ route('admin.folgas.relatorios', ['month' => $mes, 'year' => $ano]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                <i class="fas fa-file-alt mr-1"></i>Relatórios
            </a>
            <a href="{{ route('admin.folgas.auditoria') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm">
                <i class="fas fa-history mr-1"></i>Auditoria
            </a>
            @if(Auth::user()->isSuperAdmin())
            <a href="{{ route('admin.folgas.config') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
                <i class="fas fa-cog mr-1"></i>Config
            </a>
            @endif
        </div>
    </div>

    {{-- Navegação de Mês --}}
    <form method="GET" class="mb-6">
        <div class="flex items-center gap-3 bg-white rounded-xl shadow p-4">
            <a href="?month={{ $mes == 1 ? 12 : $mes - 1 }}&year={{ $mes == 1 ? $ano - 1 : $ano }}&busca={{ $busca }}"
               class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg transition">
                <i class="fas fa-chevron-left"></i>
            </a>
            <select name="month" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach(['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'] as $i => $nome)
                    <option value="{{ $i + 1 }}" {{ $mes == ($i + 1) ? 'selected' : '' }}>{{ $nome }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm focus:border-emerald-500 focus:ring-emerald-500">
                @for($y = date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ $ano == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar motorista..." class="border-gray-300 rounded-lg text-sm focus:border-emerald-500 focus:ring-emerald-500 w-48">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-800 transition">
                <i class="fas fa-search"></i>
            </button>
            <a href="?month={{ $mes }}&year={{ $ano }}" class="text-gray-500 hover:text-gray-700 text-sm">Limpar</a>
            <a href="?month={{ $mes == 12 ? 1 : $mes + 1 }}&year={{ $mes == 12 ? $ano + 1 : $ano }}&busca={{ $busca }}"
               class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg transition ml-auto">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </form>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-emerald-600">
            <div class="text-xs text-gray-500 font-semibold">Motoristas</div>
            <div class="text-2xl font-bold text-gray-800">{{ $totalMotoristas }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-blue-600">
            <div class="text-xs text-gray-500 font-semibold">Prévistas (disponível)</div>
            <div class="text-2xl font-bold text-blue-600">{{ $totalPrevistas }}</div>
            <div class="text-xs text-gray-400">mês: {{ $totalPrevistasMes }} @if($totalPrevistas - $totalPrevistasMes != 0) <span class="text-blue-500">+{{ $totalPrevistas - $totalPrevistasMes }} acúm.</span> @endif</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-green-600">
            <div class="text-xs text-gray-500 font-semibold">Tiradas (mês)</div>
            <div class="text-2xl font-bold text-green-600">{{ $totalTiradasMes }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 {{ $totalSaldo >= 0 ? 'border-emerald-600' : 'border-red-600' }}">
            <div class="text-xs text-gray-500 font-semibold">Saldo Banco</div>
            <div class="text-2xl font-bold {{ $totalSaldo >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $totalSaldo }}</div>
            @if($totalAjustes != 0)
                <div class="text-xs {{ $totalAjustes > 0 ? 'text-emerald-600' : 'text-red-600' }}">ajustes mês: {{ $totalAjustes > 0 ? '+' : '' }}{{ $totalAjustes }}</div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 {{ $totalDomingoPendente > 0 ? 'border-red-500' : 'border-green-500' }}">
            <div class="text-xs text-gray-500 font-semibold">Domingo Pendente</div>
            <div class="text-2xl font-bold {{ $totalDomingoPendente > 0 ? 'text-red-500' : 'text-green-500' }}">{{ $totalDomingoPendente }}</div>
        </div>
    </div>

    {{-- Tabela de Motoristas --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-users mr-2 text-emerald-700"></i>Motoristas — {{ ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][$mes] }}/{{ $ano }}
            </h2>
            <span class="text-sm text-gray-500">{{ $motoristas->count() }} registro(s)</span>
        </div>

        <div class="overflow-x-auto table-responsive">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="p-3 text-left font-bold text-gray-700">Motorista</th>
                        <th class="p-3 text-center font-bold text-gray-700">Prévistas</th>
                        <th class="p-3 text-center font-bold text-gray-700">Tiradas</th>
                        <th class="p-3 text-center font-bold text-gray-700">Atestado</th>
                        <th class="p-3 text-center font-bold text-gray-700">Licença</th>
                        <th class="p-3 text-center font-bold text-gray-700">Saldo</th>
                        <th class="p-3 text-center font-bold text-gray-700">Domingo</th>
                        <th class="p-3 text-center font-bold text-gray-700" title="Banco de folgas de domingo — saldo acumulado">Banco Dom.</th>
                        <th class="p-3 text-center font-bold text-gray-700">Dias Contínuos</th>
                        <th class="p-3 text-center font-bold text-gray-700">Últ. Folga</th>
                        <th class="p-3 text-center font-bold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($motoristas as $motorista)
                        @php $s = $dados[$motorista->id]; @endphp
                        <tr class="hover:bg-emerald-50/50 cursor-pointer transition" onclick="abrirCalendario({{ $motorista->id }}, '{{ addslashes($motorista->nome) }}', '{{ $motorista->cpf }}')">
                            <td class="p-3">
                                <div class="font-semibold text-gray-800">{{ $motorista->nome }}</div>
                                <div class="text-xs text-gray-400">CPF: {{ $motorista->cpf }}</div>
                            </td>
                            <td class="p-3 text-center">
                                <span class="font-bold text-blue-600">{{ $s['previstas'] }}</span>
                                @if($s['saldo_anterior'] > 0 || $s['ajustes'] != 0)
                                    <div class="text-[10px] text-blue-400">
                                        mês {{ $s['previstas_mes'] }}
                                        @if($s['saldo_anterior'] != 0) + ant. {{ $s['saldo_anterior'] }}@endif
                                        @if($s['ajustes'] != 0) {{ $s['ajustes'] > 0 ? '+' : '' }}{{ $s['ajustes'] }} aj.@endif
                                    </div>
                                @endif
                            </td>
                            <td class="p-3 text-center font-bold text-green-600">{{ $s['tiradas_mes'] }}</td>
                            <td class="p-3 text-center font-bold text-yellow-600">{{ $s['atestados_mes'] }}</td>
                            <td class="p-3 text-center font-bold text-purple-600">{{ $s['licencas_mes'] }}</td>
                            <td class="p-3 text-center">
                                <span class="font-bold {{ $s['saldo_acumulado'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $s['saldo_acumulado'] }}
                                </span>
                                @if($s['ajustes'] != 0)
                                    <div class="mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $s['ajustes'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}" title="Ajuste de saldo lançado no mês">
                                            <i class="fas fa-sliders-h mr-0.5"></i>{{ $s['ajustes'] > 0 ? '+' : '' }}{{ $s['ajustes'] }}
                                        </span>
                                    </div>
                                @endif
                                @if($s['saldo_anterior'] != 0)
                                    <div class="text-[10px] text-gray-400">ant. {{ $s['saldo_anterior'] }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($s['domingo_cumprido'])
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                        <i class="fas fa-check mr-1"></i>OK
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Pendente
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @php $sd = $s['domingo_saldo'] ?? 0; @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold {{ $sd > 0 ? 'bg-indigo-100 text-indigo-700' : ($sd < 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">
                                    <i class="fas fa-sun mr-1"></i>{{ $sd }}
                                </span>
                                @if(($s['domingo_saldo_anterior'] ?? 0) != 0)
                                    <div class="text-[10px] text-indigo-400">ant. {{ $s['domingo_saldo_anterior'] }}</div>
                                @endif
                                @if(isset($s['domingo_creditos_ganhos']) && $s['domingo_creditos_ganhos'] > 0)
                                    <div class="text-[10px] text-indigo-600 mt-0.5">+{{ $s['domingo_creditos_ganhos'] }} ganho(s)</div>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @php $dc = $s['dias_continuos'] ?? 0; @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold {{ $dc >= $settings->dias_para_folga ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $dc }} {{ $dc === 1 ? 'dia' : 'dias' }}
                                </span>
                                @if($dc >= $settings->dias_para_folga)
                                    <div class="text-[10px] text-amber-600 mt-0.5">já pode tirar folga</div>
                                @endif
                            </td>
                            <td class="p-3 text-center text-xs text-gray-500">
                                @if($s['ultima_folga_real'])
                                    <span class="font-mono">{{ \Carbon\Carbon::parse($s['ultima_folga_real'])->format('d/m/Y') }}</span>
                                @elseif($motorista->ultima_folga_data)
                                    <span class="font-mono text-amber-600">{{ $motorista->ultima_folga_data->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <button class="bg-emerald-600 text-white px-3 py-1 rounded-lg hover:bg-emerald-700 transition text-xs" onclick="event.stopPropagation(); abrirCalendario({{ $motorista->id }}, '{{ addslashes($motorista->nome) }}', '{{ $motorista->cpf }}')">
                                    <i class="fas fa-calendar-alt mr-1"></i>Abrir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center text-gray-400">
                                <i class="fas fa-calendar-times text-3xl mb-2"></i><br>
                                Nenhum motorista encontrado para este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gráfico --}}
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-chart-bar mr-2 text-blue-600"></i>Folgas por Motorista</h2>
        <canvas id="folgasChart" height="100"></canvas>
    </div>

</div>

{{-- Modal: Calendário do Motorista --}}
<div id="calendarioModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b p-4 flex justify-between items-center z-10">
            <div>
                <h3 class="text-xl font-bold text-gray-800" id="calNome"></h3>
                <p class="text-sm text-gray-500" id="calCPF"></p>
            </div>
            <button onclick="fecharCalendario()" class="text-gray-400 hover:text-gray-600 text-xl"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-4">
            {{-- Snapshot do motorista --}}
            <div class="grid grid-cols-2 md:grid-cols-7 gap-3 mb-4">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-blue-600 font-semibold">Prévistas</div>
                    <div class="text-xl font-bold text-blue-700" id="calPrevistas">0</div>
                    <div class="text-[10px] text-blue-400" id="calPrevistasDet"></div>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-green-600 font-semibold">Tiradas</div>
                    <div class="text-xl font-bold text-green-700" id="calTiradas">0</div>
                </div>
                <div class="rounded-lg p-3 text-center" id="calSaldoCard">
                    <div class="text-xs font-semibold">Saldo</div>
                    <div class="text-xl font-bold" id="calSaldo">0</div>
                    <div class="text-[10px] text-gray-400" id="calSaldoDet"></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-600 font-semibold">Trabalhados</div>
                    <div class="text-xl font-bold text-gray-700" id="calTrabalhados">0</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-yellow-600 font-semibold">Atestado</div>
                    <div class="text-xl font-bold text-yellow-700" id="calAtestado">0</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-purple-600 font-semibold">Licença</div>
                    <div class="text-xl font-bold text-purple-700" id="calLicenca">0</div>
                </div>
                <div class="bg-indigo-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-indigo-600 font-semibold">Banco Dom.</div>
                    <div class="text-xl font-bold text-indigo-700" id="calDomingoSaldo">0</div>
                    <div class="text-[10px] text-indigo-400" id="calDomingoDet"></div>
                </div>
            </div>

            {{-- Registrar Última Folga --}}
            @if(Auth::user()->hasPermission('folgas', 'edit'))
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                <form action="{{ route('admin.folgas.ultima-folga') }}" method="POST" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="user_id" id="calUserId">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-bold text-amber-800 mb-1">
                            <i class="fas fa-calendar-check mr-1"></i>Registrar Última Folga (data de referência)
                        </label>
                        <input type="date" name="ultima_folga_data" id="calUltimaFolga"
                               class="w-full border-amber-300 rounded-lg text-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-700 transition">
                        <i class="fas fa-save mr-1"></i>Salvar
                    </button>
                </form>
                <p class="text-xs text-amber-700 mt-1">Use quando o motorista ainda não tem registros no sistema. A data reinicia a contagem dos dias trabalhados.</p>
            </div>
            @endif

            {{-- Calendário --}}
            <div class="grid grid-cols-7 gap-1 mb-4" id="calGrid"></div>

            {{-- Legenda --}}
            <div class="flex flex-wrap gap-4 text-xs text-gray-600 mb-4">
                <span><span class="inline-block w-3 h-3 rounded bg-emerald-100 border border-emerald-300 mr-1"></span>Trabalho (presumido)</span>
                <span><span class="inline-block w-3 h-3 rounded bg-green-500 mr-1"></span>Folga</span>
                <span><span class="inline-block w-3 h-3 rounded bg-yellow-400 mr-1"></span>Atestado</span>
                <span><span class="inline-block w-3 h-3 rounded bg-purple-500 mr-1"></span>Licença</span>
                <span><span class="inline-block w-3 h-3 rounded bg-blue-400 mr-1"></span>Trabalho (registrado)</span>
                <span><span class="inline-block w-3 h-3 rounded border-2 border-red-500 mr-1"></span>Domingo</span>
            </div>

            {{-- Ações em massa --}}
            @if(Auth::user()->hasPermission('folgas', 'edit'))
            <div class="flex flex-wrap gap-2 mb-4">
                <form action="{{ route('admin.folgas.recalcular') }}" method="POST" class="inline" onsubmit="return confirm('Recalcular este motorista no mês?')">
                    @csrf
                    <input type="hidden" name="month" value="{{ $mes }}">
                    <input type="hidden" name="year" value="{{ $ano }}">
                    <button type="submit" class="bg-gray-200 text-gray-700 px-3 py-1 rounded text-xs hover:bg-gray-300">
                        <i class="fas fa-sync mr-1"></i>Recalcular Este Motorista
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal: Lançar Dia --}}
<div id="diaModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="border-b p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="diaModalTitle">Lançar Dia</h3>
            <button onclick="fecharDiaModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="diaForm" method="POST" class="p-4 space-y-4">
            @csrf
            <input type="hidden" name="user_id" id="diaUserId">
            <input type="hidden" name="data" id="diaData">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Data</label>
                <input type="text" id="diaDataDisplay" class="w-full border-gray-300 rounded-lg text-sm" readonly>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                <select name="tipo" id="diaTipo" class="w-full border-gray-300 rounded-lg text-sm" required>
                    <option value="trabalho">Trabalho</option>
                    <option value="folga">Folga</option>
                    <option value="atestado">Atestado</option>
                    <option value="licenca">Licença</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo da Folga</label>
                <input type="text" name="motivo_folga" id="diaMotivo" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Ex: Folga compensatória">
            </div>
            <div id="domingoRefGroup" class="hidden">
                <label class="block text-sm font-semibold text-indigo-700 mb-1">
                    <i class="fas fa-sun mr-1"></i>Referente a qual domingo?
                </label>
                <input type="date" name="domingo_ref" id="diaDomingoRef" class="w-full border-indigo-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="text-xs text-indigo-600 mt-1">Selecione o domingo ao qual esta folga se refere (banco de domingos).</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Observação</label>
                <textarea name="observacao" id="diaObs" class="w-full border-gray-300 rounded-lg text-sm" rows="2"></textarea>
            </div>
            <div id="diaStreakInfo" class="bg-gray-50 rounded-lg p-3 text-sm"></div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharDiaModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm">Cancelar</button>
                <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-lg hover:bg-emerald-800 text-sm">
                    <i class="fas fa-save mr-1"></i>Salvar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Ajuste de Saldo --}}
<div id="ajusteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="border-b p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Ajustar Saldo</h3>
            <button onclick="fecharAjusteModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('admin.folgas.ajuste') }}" method="POST" class="p-4 space-y-4">
            @csrf
            <input type="hidden" name="month" value="{{ $mes }}">
            <input type="hidden" name="year" value="{{ $ano }}">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Motorista</label>
                <select name="user_id" class="w-full border-gray-300 rounded-lg text-sm" required>
                    <option value="">Selecione...</option>
                    @foreach($motoristas as $m)
                        <option value="{{ $m->id }}">{{ $m->nome }} ({{ $m->cpf }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Ajuste</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer border rounded-lg p-3 text-center transition has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-500 border-gray-200">
                        <input type="radio" name="tipo_ajuste" value="credito" checked class="hidden">
                        <div class="text-2xl text-emerald-600"><i class="fas fa-plus-circle"></i></div>
                        <div class="text-sm font-bold text-gray-700">Crédito</div>
                        <div class="text-xs text-gray-500">Adicionar folgas</div>
                    </label>
                    <label class="cursor-pointer border rounded-lg p-3 text-center transition has-[:checked]:bg-red-50 has-[:checked]:border-red-500 border-gray-200">
                        <input type="radio" name="tipo_ajuste" value="debito" class="hidden">
                        <div class="text-2xl text-red-600"><i class="fas fa-minus-circle"></i></div>
                        <div class="text-sm font-bold text-gray-700">Débito</div>
                        <div class="text-xs text-gray-500">Remover folgas</div>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade</label>
                <input type="number" name="quantidade" id="ajusteQuantidade" min="1" max="30" value="1"
                       class="w-full border-gray-300 rounded-lg text-sm" required placeholder="Ex: 2">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Justificativa (obrigatória)</label>
                <textarea name="justificativa" class="w-full border-gray-300 rounded-lg text-sm" rows="3" required placeholder="Motivo do ajuste..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharAjusteModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm">Cancelar</button>
                <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm">
                    <i class="fas fa-save mr-1"></i>Confirmar Ajuste
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Importar CSV --}}
<div id="importModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="border-b p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Importar Escala (CSV)</h3>
            <button onclick="fecharImportModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('admin.folgas.importar') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-4">
            @csrf
            <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 space-y-1">
                <p class="font-bold">Formato esperado (cabeçalho):</p>
                <code>cpf;data;tipo;motivo;observacao</code>
                <p>Tipo: <code>trabalho</code>, <code>folga</code>, <code>atestado</code>, <code>licenca</code></p>
                <p>Data: <code>YYYY-MM-DD</code></p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Arquivo CSV</label>
                <input type="file" name="arquivo_csv" accept=".csv,.txt" class="w-full text-sm" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharImportModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 text-sm">Cancelar</button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">
                    <i class="fas fa-upload mr-1"></i>Importar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const MESES = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const MESES_CURTO = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const DIAS_SEG = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

let currentUserId = null;
let currentMonth = {{ $mes }};
let currentYear = {{ $ano }};

// ── Calendário ──
function abrirCalendario(userId, nome, cpf) {
    currentUserId = userId;
    document.getElementById('calNome').textContent = nome;
    document.getElementById('calCPF').textContent = 'CPF: ' + cpf;
    const calUserIdEl = document.getElementById('calUserId');
    if (calUserIdEl) calUserIdEl.value = userId;
    document.getElementById('calendarioModal').classList.remove('hidden');
    document.getElementById('calendarioModal').classList.add('flex');
    carregarDadosMotorista();
}

function fecharCalendario() {
    document.getElementById('calendarioModal').classList.add('hidden');
    document.getElementById('calendarioModal').classList.remove('flex');
}

function carregarDadosMotorista() {
    fetch(`{{ route('admin.folgas.motorista-dados') }}?user_id=${currentUserId}&month=${currentMonth}&year=${currentYear}`)
        .then(r => r.json())
        .then(data => {
            renderizarSnapshot(data.snapshot);
            renderizarCalendario(data.dias);
            const ultimaFolgaEl = document.getElementById('calUltimaFolga');
            if (ultimaFolgaEl) ultimaFolgaEl.value = data.ultima_folga_data || '';
        });
}

function renderizarSnapshot(s) {
    document.getElementById('calPrevistas').textContent = s.previstas;
    document.getElementById('calTiradas').textContent = s.tiradas_mes;
    document.getElementById('calTrabalhados').textContent = s.dias_trabalhados;
    document.getElementById('calAtestado').textContent = s.dias_atestado;
    document.getElementById('calLicenca').textContent = s.dias_licenca;
    document.getElementById('calDomingoSaldo').textContent = s.domingo_saldo ?? 0;

    // Detalhes competência vs acumulado
    const prevDet = document.getElementById('calPrevistasDet');
    if (prevDet) {
        let det = `mês ${s.previstas_mes}`;
        if (s.saldo_anterior != 0) det += ` + ant. ${s.saldo_anterior}`;
        if (s.ajustes != 0) det += ` ${s.ajustes > 0 ? '+' : ''}${s.ajustes} aj.`;
        prevDet.textContent = det;
    }

    const saldoDet = document.getElementById('calSaldoDet');
    if (saldoDet) {
        let det = [];
        if (s.saldo_anterior != 0) det.push(`ant. ${s.saldo_anterior}`);
        if (s.ajustes != 0) det.push(`ajuste ${s.ajustes > 0 ? '+' : ''}${s.ajustes}`);
        saldoDet.textContent = det.length ? det.join(' | ') : `mês: ${s.previstas_mes - s.tiradas_mes}`;
    }

    const domDet = document.getElementById('calDomingoDet');
    if (domDet) domDet.textContent = (s.domingo_saldo_anterior ?? 0) != 0 ? `ant. ${s.domingo_saldo_anterior}` : '';

    const saldoEl = document.getElementById('calSaldo');
    saldoEl.textContent = s.saldo_acumulado;
    const card = document.getElementById('calSaldoCard');
    card.className = s.saldo_acumulado >= 0 ? 'bg-emerald-50 rounded-lg p-3 text-center' : 'bg-red-50 rounded-lg p-3 text-center';
    saldoEl.className = s.saldo_acumulado >= 0 ? 'text-xl font-bold text-emerald-700' : 'text-xl font-bold text-red-700';
}

function renderizarCalendario(dias) {
    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';
    const diasMes = new Date(currentYear, currentMonth, 0).getDate();
    const primeiroDia = new Date(currentYear, currentMonth - 1, 1).getDay();

    // Cabeçalho
    DIAS_SEG.forEach(d => {
        const el = document.createElement('div');
        el.className = 'text-center text-xs font-bold text-gray-500 py-1';
        el.textContent = d;
        grid.appendChild(el);
    });

    // Dias vazios antes do 1º
    for (let i = 0; i < primeiroDia; i++) {
        const empty = document.createElement('div');
        grid.appendChild(empty);
    }

    // Mapa de dias com registro
    const diasMap = {};
    dias.forEach(d => {
        diasMap[d.data.substring(0, 10)] = d;
    });

    for (let d = 1; d <= diasMes; d++) {
        const dataStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const diaSemana = new Date(currentYear, currentMonth - 1, d).getDay();
        const isDomingo = diaSemana === 0;
        const rec = diasMap[dataStr];

        const el = document.createElement('div');
        el.className = 'relative rounded-lg p-2 text-center text-xs cursor-pointer hover:ring-2 hover:ring-emerald-400 transition min-h-[44px] flex flex-col items-center justify-center';

        if (rec) {
            switch (rec.tipo) {
                case 'folga':
                    el.className += ' bg-green-500 text-white font-bold';
                    break;
                case 'atestado':
                    el.className += ' bg-yellow-400 text-gray-800';
                    break;
                case 'licenca':
                    el.className += ' bg-purple-500 text-white';
                    break;
                case 'trabalho':
                    el.className += ' bg-blue-100 text-blue-800 border border-blue-300';
                    break;
            }
        } else {
            el.className += ' bg-emerald-50 text-emerald-700 border border-emerald-200';
        }

        if (isDomingo) {
            el.className += ' ring-2 ring-red-400';
        }

        el.innerHTML = `<span class="font-bold">${d}</span>`;
        if (isDomingo) el.innerHTML += `<span class="text-[8px] text-red-500 font-bold">DOM</span>`;

        el.onclick = () => abrirDiaModal(dataStr, rec);
        grid.appendChild(el);
    }
}

// ── Modal Dia ──
function abrirDiaModal(dataStr, rec) {
    document.getElementById('diaUserId').value = currentUserId;
    document.getElementById('diaData').value = dataStr;
    const d = new Date(dataStr + 'T12:00:00');
    const isDomingo = d.getDay() === 0;
    document.getElementById('diaDataDisplay').value = `${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()} (${DIAS_SEG[d.getDay()]})`;

    if (rec) {
        document.getElementById('diaTipo').value = rec.tipo;
        document.getElementById('diaMotivo').value = rec.motivo_folga || '';
        document.getElementById('diaDomingoRef').value = rec.domingo_ref ? rec.domingo_ref.substring(0, 10) : '';
        document.getElementById('diaObs').value = rec.observacao || '';
        document.getElementById('diaModalTitle').textContent = 'Editar Dia';
        document.getElementById('diaForm').action = `/admin/folgas/dia/${rec.id}`;
        document.getElementById('diaForm').innerHTML += '<input type="hidden" name="_method" value="PUT">';
    } else {
        document.getElementById('diaTipo').value = 'trabalho';
        document.getElementById('diaMotivo').value = '';
        document.getElementById('diaDomingoRef').value = isDomingo ? dataStr : '';
        document.getElementById('diaObs').value = '';
        document.getElementById('diaModalTitle').textContent = 'Lançar Dia';
        document.getElementById('diaForm').action = '{{ route("admin.folgas.dia.store") }}';
        const methodInput = document.querySelector('#diaForm input[name="_method"]');
        if (methodInput) methodInput.remove();
    }

    toggleDomingoRef();
    document.getElementById('diaModal').classList.remove('hidden');
    document.getElementById('diaModal').classList.add('flex');
}

function toggleDomingoRef() {
    const tipo = document.getElementById('diaTipo').value;
    const grupo = document.getElementById('domingoRefGroup');
    if (tipo === 'folga') {
        grupo.classList.remove('hidden');
    } else {
        grupo.classList.add('hidden');
        document.getElementById('diaDomingoRef').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('diaTipo');
    if (tipoSelect) tipoSelect.addEventListener('change', toggleDomingoRef);
});

function fecharDiaModal() {
    document.getElementById('diaModal').classList.add('hidden');
    document.getElementById('diaModal').classList.remove('flex');
}

// ── Modais ──
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
    document.getElementById('importModal').classList.add('flex');
}
function fecharImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.getElementById('importModal').classList.remove('flex');
}
function openAjusteModal() {
    document.getElementById('ajusteModal').classList.remove('hidden');
    document.getElementById('ajusteModal').classList.add('flex');
}
function fecharAjusteModal() {
    document.getElementById('ajusteModal').classList.add('hidden');
    document.getElementById('ajusteModal').classList.remove('flex');
}

// ── Gráfico ──
const ctx = document.getElementById('folgasChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($motoristas->pluck('nome')->map(fn($n) => \Illuminate\Support\Str::limit($n, 15))) !!},
            datasets: [
                {
                    label: 'Prévistas',
                    data: {!! json_encode($motoristas->map(fn($m) => $dados[$m->id]['previstas'])) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 4,
                },
                {
                    label: 'Tiradas',
                    data: {!! json_encode($motoristas->map(fn($m) => $dados[$m->id]['tiradas'])) !!},
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}
</script>
@endsection
