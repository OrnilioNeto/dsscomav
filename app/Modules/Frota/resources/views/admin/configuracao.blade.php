@extends('layout')

@section('title', 'Configuração da Integração Autotrac')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-plug text-blue-900 mr-2"></i>Integração Autotrac
            </h1>
            <p class="text-gray-500 mt-1">Credenciais da ATIC API e regras de presença/jornada.</p>
        </div>
        <a href="{{ route('admin.frota.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
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
            <strong>Verificação de jornada desativada globalmente.</strong>
            O checkbox “Integração ativa” abaixo <strong>não ativa a regra</strong> enquanto
            <code>AUTOTRAC_GATE_ENABLED=false</code>. Você pode salvar e testar as credenciais agora;
            quando os dados chegarem, basta definir <code>AUTOTRAC_GATE_ENABLED=true</code> no <code>.env</code>
            e reiniciar a aplicação.
        </div>
    @endif

    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <i class="fas fa-circle-info mr-1"></i>
        Os valores salvos aqui têm prioridade sobre o <code>.env</code> (AUTOTRAC_*). A senha e a chave são gravadas
        criptografadas e nunca são exibidas por inteiro. O IP de saída deste servidor precisa estar liberado no
        Home Office da Autotrac, senão a API responde <strong>403</strong>.
    </div>

    <form method="POST" action="{{ route('admin.frota.configuracao.salvar') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">Conexão</h2>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="ativo" value="1" @checked($config->ativo) class="rounded border-gray-300">
                    Integração ativa
                </label>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Base URL</label>
                    <input type="url" name="base_url" required value="{{ old('base_url', $config->base_url) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @error('base_url')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Código da conta (accountCode)</label>
                    <input type="text" name="account_code" value="{{ old('account_code', $config->account_code) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: 1234">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Ocp-Apim-Subscription-Key</label>
                    <input type="password" name="subscription_key" autocomplete="new-password"
                           value="{{ old('subscription_key') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                           placeholder="{{ $config->chaveMascarada() }}">
                    <p class="text-[11px] text-gray-500 mt-1">Deixe em branco para manter a chave atual.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Usuário (Basic)</label>
                    <input type="text" name="usuario" value="{{ old('usuario', $config->usuario) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                           placeholder="usuario@companhia">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Senha (Basic)</label>
                    <input type="password" name="senha" autocomplete="new-password" value="{{ old('senha') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                           placeholder="••••••••">
                    <p class="text-[11px] text-gray-500 mt-1">Deixe em branco para manter a senha atual.</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" id="btn-testar"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">
                    <i class="fas fa-satellite-dish"></i> Testar conexão
                </button>
                <span class="text-[11px] text-gray-500 self-center">Salve antes de testar as credenciais novas.</span>
            </div>

            <div id="resultado-teste" class="hidden mt-3 rounded-lg px-4 py-3 text-sm"></div>
        </div>

        <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Regras de acesso</h2>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cache do status (segundos)</label>
                    <input type="number" name="ttl_segundos" min="10" max="3600" required
                           value="{{ old('ttl_segundos', $config->ttl_segundos) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tolerância de indisponibilidade (min)</label>
                    <input type="number" name="grace_minutos" min="0" max="1440" required
                           value="{{ old('grace_minutos', $config->grace_minutos) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Jornada máxima (horas)</label>
                    <input type="number" name="jornada_maxima_horas" min="1" max="24" required
                           value="{{ old('jornada_maxima_horas', $config->jornada_maxima_horas) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mt-4 grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Se a Autotrac ficar indisponível</label>
                    <select name="fail_mode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="grace" @selected(old('fail_mode', $config->fail_mode) === 'grace')>Manter último status por tolerância (recomendado)</option>
                        <option value="closed" @selected(old('fail_mode', $config->fail_mode) === 'closed')>Bloquear imediatamente</option>
                        <option value="open" @selected(old('fail_mode', $config->fail_mode) === 'open')>Liberar e apenas registrar</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Tipos de usuário afetados</label>
                    @php($tiposSelecionados = (array) old('tipos_motorista', $config->tiposMotorista()))
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mr-4">
                        <input type="checkbox" name="tipos_motorista[]" value="motorista"
                               @checked(in_array('motorista', $tiposSelecionados, true)) class="rounded border-gray-300">
                        Motorista
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="tipos_motorista[]" value="motorista_monitor"
                               @checked(in_array('motorista_monitor', $tiposSelecionados, true)) class="rounded border-gray-300">
                        Motorista Monitor
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800">
                <i class="fas fa-floppy-disk mr-1"></i> Salvar configuração
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.frota.sincronizar') }}" class="mt-4">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700">
            <i class="fas fa-rotate mr-1"></i> Sincronizar agora (motoristas, veículos e status)
        </button>
    </form>
</div>
@endsection

@section('extra_js')
<script>
    (function () {
        const botao = document.getElementById('btn-testar');
        const resultado = document.getElementById('resultado-teste');

        if (!botao) {
            return;
        }

        botao.addEventListener('click', function () {
            botao.disabled = true;
            resultado.className = 'mt-3 rounded-lg px-4 py-3 text-sm bg-gray-50 text-gray-600';
            resultado.textContent = 'Testando conexão...';
            resultado.classList.remove('hidden');

            fetch('{{ route('admin.frota.configuracao.testar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
                .then(async (resposta) => {
                    const dados = await resposta.json().catch(() => ({}));
                    return { ok: resposta.ok, dados: dados };
                })
                .then(({ ok, dados }) => {
                    if (ok && dados.ok) {
                        const contas = (dados.contas || [])
                            .map((conta) => '#' + conta.code + ' — ' + conta.name)
                            .join(' | ');
                        resultado.className = 'mt-3 rounded-lg px-4 py-3 text-sm bg-green-50 text-green-800 border border-green-200';
                        resultado.textContent = dados.message + (contas ? ' Contas: ' + contas : '');
                    } else {
                        resultado.className = 'mt-3 rounded-lg px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200';
                        resultado.textContent = (dados && dados.message) || 'Falha ao consultar a Autotrac.';
                    }
                })
                .catch(() => {
                    resultado.className = 'mt-3 rounded-lg px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200';
                    resultado.textContent = 'Falha de rede ao testar a conexão.';
                })
                .finally(() => {
                    botao.disabled = false;
                });
        });
    })();
</script>
@endsection
