@extends('layout')

@section('title', 'Lembretes WhatsApp — Disparo')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-start md:justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fab fa-whatsapp text-green-600 mr-2"></i>Lembretes WhatsApp
            </h1>
            <p class="text-gray-500 mt-1">
                Selecione um treinamento para ver quem ainda não concluiu e enfileirar os lembretes.
            </p>
        </div>
        @include('lembretes_whatsapp::partials.sessao-badge')
    </div>

    @include('lembretes_whatsapp::partials.tabs')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            <a href="{{ route('admin.lembretes.index') }}" class="underline font-semibold ml-1">Ver histórico</a>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fas fa-exclamation-triangle mr-1"></i>{{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold mb-1">Não foi possível enfileirar:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(! $gatewayEnabled)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-power-off mr-1"></i>
            Integração WhatsApp desativada no servidor (<code>WA_REMINDERS_ENABLED=false</code>): os lembretes
            enfileirados aguardarão a ativação para serem enviados.
        </div>
    @endif

    @if(! $training)
        {{-- ============================ PANORAMA ============================ --}}
        <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
            <div class="p-5 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Panorama por treinamento</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Todos os treinamentos cadastrados, com a base elegível e quantos ainda não concluíram.
                        Contagens em cache por 10 minutos.
                    </p>
                </div>
                <a href="{{ route('admin.lembretes.disparo', ['refresh' => 1]) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition text-sm">
                    <i class="fas fa-sync-alt mr-1"></i>Atualizar contagens
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-sm">Treinamento</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Liberação</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Elegíveis</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Concluíram</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Pendentes</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Situação</th>
                            <th class="px-4 py-3 text-center font-semibold text-sm">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($panorama as $item)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800">{{ $item->training->titulo }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->training->tipo ? ucfirst($item->training->tipo) : 'Sem tipo' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ $item->training->releaseDate()?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $item->elegiveis ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-green-700">{{ $item->concluidos ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->pendentes === null)
                                        <span class="text-gray-400">—</span>
                                    @elseif($item->pendentes > 0)
                                        <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-bold">{{ $item->pendentes }}</span>
                                    @else
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->ativo)
                                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs font-semibold">Ativo</span>
                                    @else
                                        <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs font-semibold">Inativo</span>
                                    @endif
                                    @if(! $item->liberado)
                                        <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full text-xs font-semibold">Não liberado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->ativo && $item->liberado)
                                        <a href="{{ route('admin.lembretes.disparo', ['training_id' => $item->training->id]) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-700 text-white text-sm font-semibold hover:bg-emerald-600 transition">
                                            <i class="fas fa-paper-plane mr-1"></i>Lembrar
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">Indisponível</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">Nenhum treinamento cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- ======================== PENDENTES DO TREINAMENTO ======================== --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <a href="{{ route('admin.lembretes.disparo') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i>Voltar ao panorama
                </a>
                <h2 class="text-2xl font-bold text-gray-800 mt-1">{{ $training->titulo }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $training->tipo ? ucfirst($training->tipo) : 'Sem tipo' }} · Liberação:
                    {{ $training->releaseDate()?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
            <div class="text-sm text-gray-600 md:text-right">
                <div>Lote máximo por disparo: <strong>{{ $batchLimit }}</strong></div>
                <div>Próximo envio previsto: <strong>{{ $proximoEnvio }}</strong></div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                <p class="text-sm text-gray-500">Elegíveis</p>
                <p class="text-2xl font-bold text-gray-800">{{ $contagens['elegiveis'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border border-green-100">
                <p class="text-sm text-gray-500">Já concluíram</p>
                <p class="text-2xl font-bold text-green-600">{{ $contagens['concluidos'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border border-yellow-100">
                <p class="text-sm text-gray-500">Pendentes</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $contagens['pendentes'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border border-red-100">
                <p class="text-sm text-gray-500">Sem telefone válido</p>
                <p class="text-2xl font-bold text-red-600">{{ $contagens['sem_telefone'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.lembretes.disparo') }}"
              class="bg-white rounded-2xl shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="hidden" name="training_id" value="{{ $training->id }}">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tipo de usuário</label>
                <select name="tipo_usuario" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach($tiposUsuario as $tipo)
                        <option value="{{ $tipo }}" @selected($filtros['tipo_usuario'] === $tipo)>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Busca (nome ou CPF)</label>
                <input type="text" name="busca" value="{{ $filtros['busca'] }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: João ou 12345678900">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 transition">
                    <i class="fas fa-filter mr-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.lembretes.disparo', ['training_id' => $training->id]) }}"
                   class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition">
                    <i class="fas fa-redo mr-1"></i>Limpar
                </a>
            </div>
        </form>

        @if($pendentes->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 border border-gray-100">
                <i class="fas fa-info-circle text-2xl text-gray-400 mb-2"></i>
                @if($contagens['pendentes'] === 0)
                    <p>Nenhum colaborador pendente: todos os {{ $contagens['elegiveis'] }} elegíveis já concluíram este treinamento.</p>
                @else
                    <p>Nenhum pendente encontrado para os filtros atuais.</p>
                    <p class="text-sm text-gray-400 mt-1">Existem {{ $contagens['pendentes'] }} pendente(s) na base — limpe os filtros para vê-los.</p>
                @endif
            </div>
        @else
            <form method="POST" action="{{ route('admin.lembretes.store') }}" id="waDisparoForm"
                  onsubmit="return WaDisparo.validar();">
                @csrf
                <input type="hidden" name="training_id" value="{{ $training->id }}">

                <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100 mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-3 text-center w-10">
                                        <input type="checkbox" id="waTodos" title="Marcar/desmarcar todos"
                                               class="rounded text-green-600 focus:ring-green-500">
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-sm">Colaborador</th>
                                    <th class="px-4 py-3 text-left font-semibold text-sm">CPF</th>
                                    <th class="px-4 py-3 text-left font-semibold text-sm">Tipo</th>
                                    <th class="px-4 py-3 text-left font-semibold text-sm">Telefone</th>
                                    <th class="px-4 py-3 text-center font-semibold text-sm">Situação</th>
                                    <th class="px-4 py-3 text-center font-semibold text-sm">Último lembrete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendentes as $item)
                                    <tr class="border-b hover:bg-gray-50 transition">
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox" name="user_ids[]" value="{{ $item->user->id }}"
                                                   class="wa-user-checkbox rounded text-green-600 focus:ring-green-500"
                                                   {{ $item->telefone_valido ? 'checked' : 'disabled' }}>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-800">{{ $item->user->nome }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->user->getCpfFormatted() }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $item->user->tipo_usuario ? ucfirst(str_replace('_', ' ', $item->user->tipo_usuario)) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm {{ $item->telefone_valido ? 'text-gray-700' : 'text-red-600 font-semibold' }}">
                                            {{ $item->telefone }}{{ $item->telefone_valido ? '' : ' (sem WhatsApp)' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($item->situacao === 'pendente')
                                                <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-xs font-semibold">Pendente</span>
                                            @else
                                                <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-xs font-semibold">Não iniciado</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-xs text-gray-500">
                                            @if($item->enviado_hoje)
                                                <span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded-full font-semibold">enviado hoje às {{ $item->enviado_hoje_hora }}</span>
                                            @elseif($item->ultimo_em)
                                                {{ $item->ultimo_status }} em {{ $item->ultimo_em }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-5 border border-gray-100">
                    <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-1">Mensagem</label>
                    <textarea id="mensagem" name="mensagem" rows="4" maxlength="2000" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('mensagem', $mensagemPadrao) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Os trechos <strong>{nome}</strong> e <strong>{treinamento}</strong> são substituídos por colaborador.
                    </p>

                    <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Envio um a um, escalonado (~1 mensagem a cada 5 minutos), teto diário e janela comercial.
                            Os números passam por validação no WhatsApp; sem telefone válido (ou número não encontrado)
                            o colaborador não entra na fila.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="submit" id="waDisparoEnviar" name="enviar_agora" value="0"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-500 transition">
                                <i class="fab fa-whatsapp mr-2"></i>Enfileirar <span id="waDisparoContador">(0)</span>
                            </button>
                            <button type="submit" id="waDisparoAgora" name="enviar_agora" value="1"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-500 transition"
                                    title="Envia imediatamente, ignorando agendamento e janela de horário. Use para testes e casos urgentes.">
                                <i class="fas fa-bolt mr-2"></i>Enviar agora
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-amber-700 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Enviar agora</strong> ignora o agendamento e a janela de horário (uso em teste/urgência).
                        O teto diário continua valendo.
                    </p>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection

@section('extra_js')
<script>
    window.WaDisparo = (function () {
        const limite = {{ (int) $batchLimit }};
        let enviarAgora = false;
        let gatewayOk = true;

        function checkboxes() {
            return Array.from(document.querySelectorAll('.wa-user-checkbox')).filter((c) => !c.disabled);
        }

        function selecionados() {
            return checkboxes().filter((c) => c.checked);
        }

        function atualizar() {
            const contador = document.getElementById('waDisparoContador');
            const botaoEnfileirar = document.getElementById('waDisparoEnviar');
            const botaoAgora = document.getElementById('waDisparoAgora');

            if (!contador || !botaoEnfileirar || !botaoAgora) {
                return;
            }

            const total = selecionados().length;
            contador.textContent = '(' + total + ')';
            botaoEnfileirar.disabled = total === 0;
            botaoAgora.disabled = total === 0 || !gatewayOk;
        }

        function validar() {
            const total = selecionados().length;

            if (total === 0) {
                alert('Selecione ao menos um colaborador com telefone válido.');
                return false;
            }

            if (total > limite) {
                alert('Selecione no máximo ' + limite + ' colaboradores por disparo (selecionados: ' + total + ').');
                return false;
            }

            const pergunta = enviarAgora
                ? 'Enviar ' + total + ' mensagem(ns) AGORA? Ignora o agendamento e a janela de horário (uso em teste/urgência).'
                : 'Enfileirar ' + total + ' lembrete(s)? O envio é escalonado e pode levar alguns minutos.';

            if (!confirm(pergunta)) {
                enviarAgora = false;

                return false;
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const todos = document.getElementById('waTodos');
            if (todos) {
                todos.addEventListener('change', function () {
                    checkboxes().forEach((c) => { c.checked = todos.checked; });
                    atualizar();
                });
            }

            document.querySelectorAll('.wa-user-checkbox').forEach((c) => c.addEventListener('change', atualizar));

            const botaoAgora = document.getElementById('waDisparoAgora');
            if (botaoAgora) {
                botaoAgora.addEventListener('click', function () {
                    enviarAgora = true;
                });
            }

            if (window.WaGateway) {
                window.WaGateway.onReady(function (state) {
                    gatewayOk = state.enabled && state.connected;

                    if (botaoAgora) {
                        botaoAgora.title = gatewayOk
                            ? 'Envia imediatamente, ignorando agendamento e janela de horário. Use para testes e casos urgentes.'
                            : 'Indisponível: integração desativada ou sessão desconectada.';
                    }

                    atualizar();
                });
            }

            atualizar();
        });

        return { validar };
    })();
</script>
@endsection
