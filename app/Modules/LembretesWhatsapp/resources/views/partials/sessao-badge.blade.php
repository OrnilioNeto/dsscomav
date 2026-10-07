<span id="waSessaoBadge" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">
    <i class="fas fa-circle-notch fa-spin text-xs"></i> Verificando sessão...
</span>

<script>
    window.WaGateway = (function () {
        const badge = document.getElementById('waSessaoBadge');
        const callbacks = [];
        const state = { checked: false, enabled: false, connected: false, status: null };

        function esc(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function notify() {
            callbacks.forEach((cb) => cb(state));
        }

        function onReady(cb) {
            if (state.checked) {
                cb(state);
            } else {
                callbacks.push(cb);
            }
        }

        async function carregar() {
            try {
                const resp = await fetch(@json(route('admin.lembretes.sessao')), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();

                state.checked = true;
                state.enabled = !!data.enabled;
                state.connected = !!data.connected;
                state.status = data.status || null;

                if (!state.enabled) {
                    badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
                    badge.innerHTML = '<i class="fas fa-power-off text-xs"></i> Integração desativada';
                } else if (state.connected) {
                    badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-800';
                    badge.innerHTML = '<i class="fas fa-check-circle text-xs"></i> Sessão conectada';
                } else {
                    badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-800';
                    badge.innerHTML = '<i class="fas fa-exclamation-triangle text-xs"></i> Sessão: ' + esc(state.status || 'desconectada');
                }
            } catch (e) {
                state.checked = true;
                state.enabled = false;
                state.connected = false;
                state.status = 'inacessível';
                badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-800';
                badge.innerHTML = '<i class="fas fa-exclamation-triangle text-xs"></i> Gateway inacessível';
            }

            notify();
        }

        carregar();

        return { onReady };
    })();
</script>
