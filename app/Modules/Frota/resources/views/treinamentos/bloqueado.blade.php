@extends('layout')

@section('title', 'Treinamentos indisponíveis')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-8 py-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center">
                    <i class="fas fa-lock text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Treinamentos indisponíveis no momento</h1>
                    <p class="text-slate-200 text-sm mt-1">O acesso é liberado durante o seu horário de trabalho.</p>
                </div>
            </div>
        </div>

        <div class="px-8 py-8 space-y-6">
            <div id="bloqueio-motivo" class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900">
                <p class="font-semibold">{{ $acesso['motivo'] }}</p>
                @if($acesso['detalhe'])
                    <p class="text-sm mt-1 text-amber-800">{{ $acesso['detalhe'] }}</p>
                @endif
            </div>

            <div class="text-sm text-gray-600 space-y-2">
                <p>
                    A Plataforma DSS identifica o seu login no rastreador do veículo (Autotrac) para confirmar que você
                    está em jornada de trabalho. Assim que o login for identificado, os treinamentos serão liberados
                    automaticamente — você não precisa sair e entrar novamente.
                </p>
                <p>
                    Se você já está trabalhando e o acesso continua bloqueado, aguarde alguns instantes ou fale com
                    seu gestor para uma liberação manual.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="btn-verificar"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-900 text-white text-sm font-semibold hover:bg-blue-800">
                    <i class="fas fa-rotate"></i> Verificar novamente
                </button>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    <i class="fas fa-arrow-left"></i> Voltar ao dashboard
                </a>
                <span id="verificacao-info" class="text-xs text-gray-500">
                    @if($acesso['verificado_em'])
                        Última verificação: {{ $acesso['verificado_em']->format('d/m/Y H:i:s') }}
                    @else
                        Ainda não verificado.
                    @endif
                </span>
            </div>

            <div id="verificacao-resultado" class="hidden rounded-lg px-4 py-3 text-sm"></div>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script>
    (function () {
        const botao = document.getElementById('btn-verificar');
        const info = document.getElementById('verificacao-info');
        const resultado = document.getElementById('verificacao-resultado');
        let verificando = false;

        function verificar(forcar) {
            if (verificando) {
                return;
            }

            verificando = true;

            if (forcar) {
                botao.disabled = true;
                resultado.className = 'rounded-lg px-4 py-3 text-sm bg-gray-50 text-gray-600';
                resultado.textContent = 'Verificando sua jornada no rastreamento...';
                resultado.classList.remove('hidden');
            }

            fetch('{{ route('treinamentos.acesso-status') }}' + (forcar ? '?forcar=1' : ''), {
                headers: { 'Accept': 'application/json' }
            })
                .then((resposta) => resposta.json())
                .then((dados) => {
                    if (dados.liberado) {
                        resultado.className = 'rounded-lg px-4 py-3 text-sm bg-green-50 text-green-800 border border-green-200';
                        resultado.textContent = 'Acesso liberado! Redirecionando...';
                        resultado.classList.remove('hidden');
                        window.location.href = '{{ route('dashboard') }}';

                        return;
                    }

                    if (dados.verificado_em) {
                        const data = new Date(dados.verificado_em);
                        info.textContent = 'Última verificação: ' + data.toLocaleString('pt-BR');
                    }

                    if (forcar) {
                        resultado.className = 'rounded-lg px-4 py-3 text-sm bg-amber-50 text-amber-800 border border-amber-200';
                        resultado.textContent = dados.motivo || 'Ainda não identificamos sua jornada.';
                        resultado.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    if (forcar) {
                        resultado.className = 'rounded-lg px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200';
                        resultado.textContent = 'Não foi possível verificar agora. Tente novamente em instantes.';
                        resultado.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    verificando = false;
                    botao.disabled = false;
                });
        }

        botao.addEventListener('click', function () {
            verificar(true);
        });

        // Tenta liberar sozinho: o motorista pode logar no caminhão depois
        // de abrir a plataforma.
        window.setInterval(function () {
            verificar(false);
        }, 60000);
    })();
</script>
@endsection
