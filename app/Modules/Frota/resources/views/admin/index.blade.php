@extends('layout')

@section('title', 'Motoristas Online')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-truck text-blue-900 mr-2"></i>Motoristas Online
            </h1>
            <p class="text-gray-500 mt-1">
                Jornada identificada no rastreamento (Autotrac) e situação do acesso aos treinamentos.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.frota.liberacoes.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                <i class="fas fa-unlock-keyhole"></i> Liberações
            </a>
            @if(auth()->user()->hasPermission('integracao_autotrac', 'edit'))
                <a href="{{ route('admin.frota.configuracao') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    <i class="fas fa-gear"></i> Configuração
                </a>
                <form method="POST" action="{{ route('admin.frota.sincronizar') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800">
                        <i class="fas fa-rotate"></i> Sincronizar agora
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if(! $gateEnabled)
        <div class="mb-4 rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
            <i class="fas fa-toggle-off mr-1"></i>
            <strong>Verificação de jornada DESATIVADA</strong> (kill switch global). Nenhum motorista está sendo
            bloqueado e o sync agendado não está rodando.
            Para reativar: defina <code>AUTOTRAC_GATE_ENABLED=true</code> no <code>.env</code> e reinicie a aplicação.
            Para testar a integração enquanto isso: <code>php artisan frota:sync --force</code>.
        </div>
    @elseif(! $config->ativo)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            A integração está <strong>desativada</strong> para este tenant. Nenhum motorista está sendo bloqueado.
            @if(auth()->user()->hasPermission('integracao_autotrac', 'edit'))
                <a href="{{ route('admin.frota.configuracao') }}" class="underline font-semibold">Configurar agora</a>.
            @endif
        </div>
    @elseif(! $configurada)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fas fa-plug-circle-xmark mr-1"></i>
            Integração ativa, mas com credenciais incompletas. O acesso aos treinamentos dos motoristas ficará bloqueado
            (ou em tolerância, conforme a regra).
        </div>
    @endif

    @if($ultimoSync)
        <div class="mb-4 text-xs text-gray-500">
            Última sincronização: {{ \Carbon\Carbon::parse($ultimoSync['em'])->format('d/m/Y H:i') }}
            — {{ $ultimoSync['resultado']['motoristas'] ?? 0 }} motorista(s),
            {{ $ultimoSync['resultado']['veiculos'] ?? 0 }} veículo(s),
            {{ $ultimoSync['resultado']['status'] ?? 0 }} status.
        </div>

        @if(! empty($ultimoSync['resultado']['erros']))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                Falhas na última sincronização: {{ implode(' | ', $ultimoSync['resultado']['erros']) }}
            </div>
        @endif
    @endif

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Motoristas</p>
            <p class="text-2xl font-bold text-gray-800">{{ $resumo['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-green-100">
            <p class="text-sm text-gray-500">Online agora</p>
            <p class="text-2xl font-bold text-green-600">{{ $resumo['online'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-slate-100">
            <p class="text-sm text-gray-500">Offline</p>
            <p class="text-2xl font-bold text-slate-600">{{ $resumo['offline'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-blue-100">
            <p class="text-sm text-gray-500">Liberados manualmente</p>
            <p class="text-2xl font-bold text-blue-600">{{ $resumo['liberados_manual'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-amber-100">
            <p class="text-sm text-gray-500">Sem vínculo com usuário</p>
            <p class="text-2xl font-bold text-amber-600">{{ $resumo['sem_vinculo'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <form method="GET" action="{{ route('admin.frota.index') }}" class="flex flex-1 flex-col md:flex-row gap-2">
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Nome ou CPF do motorista"
                       class="w-full md:max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos os status</option>
                    <option value="online" @selected(request('status') === 'online')>Online</option>
                    <option value="offline" @selected(request('status') === 'offline')>Offline</option>
                    <option value="desconhecido" @selected(request('status') === 'desconhecido')>Não verificado</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">Filtrar</button>
                @if(request()->filled('busca') || request()->filled('status'))
                    <a href="{{ route('admin.frota.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600">Limpar</a>
                @endif
            </form>
            <div class="text-xs text-gray-500">
                Limite de sessão: <strong>{{ $config->jornada_maxima_horas }}h</strong>
                • Tolerância de integração: <strong>{{ $config->grace_minutos }}min</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Motorista</th>
                        <th class="px-4 py-3 text-left">CPF</th>
                        <th class="px-4 py-3 text-left">Rastreamento</th>
                        <th class="px-4 py-3 text-left">Veículo</th>
                        <th class="px-4 py-3 text-left">Sessão</th>
                        <th class="px-4 py-3 text-left">Treinamentos</th>
                        <th class="px-4 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($motoristas as $motorista)
                        @php
                            $liberacao = $liberacoes->get($motorista->user_id);
                            $horas = $motorista->horasSessao();
                            $limite = (int) $config->jornada_maxima_horas;

                            if ($liberacao) {
                                $acessoLabel = 'Liberado manualmente';
                                $acessoClasses = 'bg-blue-100 text-blue-700';
                            } elseif (! $motorista->user_id) {
                                $acessoLabel = 'Sem vínculo';
                                $acessoClasses = 'bg-gray-100 text-gray-600';
                            } elseif ($motorista->status === 'online' && ($horas === null || $horas < $limite)) {
                                $acessoLabel = 'Liberado';
                                $acessoClasses = 'bg-green-100 text-green-700';
                            } elseif ($motorista->status === 'online') {
                                $acessoLabel = 'Jornada excedida';
                                $acessoClasses = 'bg-amber-100 text-amber-700';
                            } elseif ($motorista->status === 'offline') {
                                $acessoLabel = 'Bloqueado';
                                $acessoClasses = 'bg-red-100 text-red-700';
                            } else {
                                $acessoLabel = 'Não verificado';
                                $acessoClasses = 'bg-gray-100 text-gray-600';
                            }

                            $statusClasses = match ($motorista->status) {
                                'online' => 'bg-green-100 text-green-700',
                                'offline' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $motorista->name ?? '—' }}</div>
                                @if($motorista->user)
                                    <div class="text-xs text-gray-500">{{ $motorista->user->nome }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $motorista->cpf ? mask_cpf($motorista->cpf) : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                    {{ $motorista->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $motorista->veiculoAtual?->identificacao ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($motorista->status === 'online' && $motorista->ultimo_login_em)
                                    desde {{ $motorista->ultimo_login_em->format('d/m H:i') }}
                                    <span class="block text-xs text-gray-400">{{ number_format((float) ($horas ?? 0), 1, ',', '.') }}h</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $acessoClasses }}">
                                    {{ $acessoLabel }}
                                </span>
                                @if($liberacao)
                                    <span class="block text-[11px] text-gray-500 mt-1">
                                        até {{ $liberacao->fim?->format('d/m/Y H:i') }}
                                        @if($liberacao->motivo)
                                            — {{ \Illuminate\Support\Str::limit($liberacao->motivo, 40) }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($motorista->user_id && auth()->user()->hasPermission('frota', 'edit'))
                                    <button type="button"
                                            class="px-3 py-1.5 rounded-lg bg-blue-900 text-white text-xs font-semibold hover:bg-blue-800"
                                            data-liberar-id="{{ $motorista->user_id }}"
                                            data-liberar-nome="{{ $motorista->user->nome ?? $motorista->name }}">
                                        <i class="fas fa-unlock mr-1"></i> Liberar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Nenhum motorista encontrado. Use <strong>Sincronizar agora</strong> para importar da Autotrac.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100">
            {{ $motoristas->links() }}
        </div>
    </div>
</div>

@if(auth()->user()->hasPermission('frota', 'edit'))
    <div id="modal-liberar" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
            <form method="POST" action="{{ route('admin.frota.liberacoes.store') }}">
                @csrf
                <input type="hidden" name="user_ids[]" id="liberar-user-id" value="">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Liberar acesso aos treinamentos</h2>
                        <p class="text-sm text-gray-500" id="liberar-nome">—</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="fecharLiberar()">
                        <i class="fas fa-xmark text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Validade</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <button type="button" class="preset-validade px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold" data-preset="fim_do_dia">Até o fim do dia</button>
                            <button type="button" class="preset-validade px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold" data-preset="24h">24 horas</button>
                            <button type="button" class="preset-validade px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold" data-preset="7d">7 dias</button>
                        </div>
                        <input type="datetime-local" name="fim" id="liberar-fim" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-[11px] text-gray-500 mt-1">A liberação vale para todos os treinamentos do motorista no período.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Motivo (opcional)</label>
                        <textarea name="motivo" rows="3" maxlength="1000"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                  placeholder="Ex.: treinamento obrigatório fora da jornada, falha no rastreador..."></textarea>
                        <p class="text-[11px] text-gray-500 mt-1">O motivo fica registrado na auditoria e visível para o motorista.</p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700" onclick="fecharLiberar()">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800">
                        <i class="fas fa-unlock mr-1"></i> Liberar acesso
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@section('extra_js')
<script>
    (function () {
        const modal = document.getElementById('modal-liberar');

        if (!modal) {
            return;
        }

        const campoId = document.getElementById('liberar-user-id');
        const campoNome = document.getElementById('liberar-nome');
        const campoFim = document.getElementById('liberar-fim');

        function formatarInput(data) {
            const pad = (n) => String(n).padStart(2, '0');
            return data.getFullYear() + '-' + pad(data.getMonth() + 1) + '-' + pad(data.getDate())
                + 'T' + pad(data.getHours()) + ':' + pad(data.getMinutes());
        }

        window.abrirLiberar = function (id, nome) {
            campoId.value = id;
            campoNome.textContent = nome || 'Motorista';
            aplicarPreset('fim_do_dia');
            modal.classList.remove('hidden');
        };

        window.fecharLiberar = function () {
            modal.classList.add('hidden');
        };

        function aplicarPreset(preset) {
            const agora = new Date();
            let alvo = new Date(agora);

            if (preset === 'fim_do_dia') {
                alvo.setHours(23, 59, 0, 0);
            } else if (preset === '24h') {
                alvo.setHours(alvo.getHours() + 24);
            } else if (preset === '7d') {
                alvo.setDate(alvo.getDate() + 7);
            }

            campoFim.value = formatarInput(alvo);
        }

        document.querySelectorAll('[data-liberar-id]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                window.abrirLiberar(botao.dataset.liberarId, botao.dataset.liberarNome);
            });
        });

        document.querySelectorAll('.preset-validade').forEach(function (botao) {
            botao.addEventListener('click', function () {
                aplicarPreset(botao.dataset.preset);
            });
        });

        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) {
                window.fecharLiberar();
            }
        });
    })();
</script>
@endsection
