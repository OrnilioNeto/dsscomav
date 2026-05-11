@extends('layout')

@section('title', 'Meu Dashboard')

@section('extra_css')
<style>
    .certificate-badge {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
        border: 1px solid rgba(249, 115, 22, 0.25);
        box-shadow: 0 12px 28px rgba(249, 115, 22, 0.18);
        color: #c2410c;
        flex-shrink: 0;
    }

    .certificate-badge i {
        filter: drop-shadow(0 1px 0 rgba(255, 255, 255, 0.75));
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Meu Dashboard
    </h1>

    <!-- Resumo -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Treinamentos Disponíveis</p>
                    <p class="text-3xl font-bold text-blue-900">{{ count($treinamentosDisponíveis) }}</p>
                </div>
                <i class="fas fa-play-circle text-5xl text-blue-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Concluídos</p>
                    <p class="text-3xl font-bold text-green-600">{{ $treinamentosCompletos }}</p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Tempo Assistido</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $tempoTotal }}</p>
                </div>
                <i class="fas fa-clock text-5xl text-purple-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificados</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $certificados }}</p>
                </div>
                <div class="certificate-badge" aria-hidden="true">
                    <i class="fas fa-certificate text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Treinamentos Disponíveis -->
    <div class="space-y-8">

        <!-- AINDA NÃO LIBERADOS -->
        @if(count($treinamentosBloqueados) > 0)
            <div class="bg-white p-8 rounded-lg shadow-lg border-l-4 border-gray-400">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-lock text-gray-500 mr-3"></i>Ainda não liberados ({{ count($treinamentosBloqueados) }})
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($treinamentosBloqueados as $training)
                        <div class="border-2 border-gray-200 rounded-lg overflow-hidden bg-gradient-to-br from-gray-50 to-white opacity-80 cursor-not-allowed locked-training-card"
                             data-release-local="{{ optional($training->data_liberacao)->format('d/m/Y, H:i') }}"
                             title="Carregando data de liberação...">
                            <div class="bg-gradient-to-r from-gray-500 to-gray-600 h-40 flex items-center justify-center text-white relative">
                                <i class="fas fa-lock text-5xl opacity-70"></i>
                                <div class="absolute top-2 right-2 bg-gray-800 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    BLOQUEADO
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800">{{ $training->titulo }}</h3>
                                    <span class="text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>

                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-gray-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span class="text-gray-500"><i class="fas fa-lock"></i> Aguardando liberação</span>
                                </div>

                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-600 locked-release-label">
                                    Libera em breve
                                </div>

                                <button type="button" disabled class="mt-3 block w-full bg-gray-400 text-white text-center py-2 rounded font-semibold cursor-not-allowed">
                                    <i class="fas fa-lock mr-2"></i>Ainda não liberado
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- PENDENTES -->
        @if(count($treinamentosPendentes) > 0)
            <div class="bg-white p-8 rounded-lg shadow-lg border-l-4 border-orange-500">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-hourglass-half text-orange-500 mr-3"></i>Pendentes ({{ count($treinamentosPendentes) }})
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($treinamentosPendentes as $training)
                        @php
                            $userProgress = $progresso->where('training_id', $training->id)->first();
                            $porcentagem = $userProgress->porcentagem_assistida ?? 0;
                        @endphp
                        
                        <div class="border-2 border-orange-200 rounded-lg overflow-hidden hover:shadow-lg transition bg-gradient-to-br from-orange-50 to-white">
                            <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-40 flex items-center justify-center text-white relative">
                                <i class="fas fa-play-circle text-5xl opacity-50"></i>
                                <div class="absolute top-2 right-2 bg-orange-700 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    {{ $porcentagem }}%
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800">{{ $training->titulo }}</h3>
                                    <span class="text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>

                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progresso</span>
                                        <span>{{ $porcentagem }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-orange-500 h-2 rounded-full transition-all" 
                                            style="width: {{ $porcentagem }}%"
                                        ></div>
                                    </div>
                                </div>

                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-orange-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span><i class="fas fa-{{ $training->obrigatorio ? 'exclamation-circle text-orange-600' : 'check text-green-600' }}"></i> {{ $training->obrigatorio ? 'Obrigatório' : 'Opcional' }}</span>
                                </div>

                                <a 
                                    href="{{ route('treinamentos.player', $training->id) }}"
                                    class="block w-full bg-orange-500 text-white text-center py-2 rounded hover:bg-orange-600 transition font-semibold"
                                >
                                    <i class="fas fa-play mr-2"></i>Continuar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- CONCLUÍDOS -->
        @if(count($treinamentosConcluidos) > 0)
            <div class="bg-white p-8 rounded-lg shadow-lg border-l-4 border-green-500">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>Concluídos ({{ count($treinamentosConcluidos) }})
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($treinamentosConcluidos as $training)
                        <div class="border-2 border-green-200 rounded-lg overflow-hidden hover:shadow-lg transition bg-gradient-to-br from-green-50 to-white">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-40 flex items-center justify-center text-white relative">
                                <i class="fas fa-check-circle text-5xl opacity-70"></i>
                                <div class="absolute top-2 right-2 bg-green-700 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    100%
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800">{{ $training->titulo }}</h3>
                                    <span class="text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>

                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-green-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span class="text-green-600"><i class="fas fa-check-circle"></i> Concluído</span>
                                </div>

                                <a 
                                    href="{{ route('treinamentos.player', $training->id) }}"
                                    class="block w-full bg-green-500 text-white text-center py-2 rounded hover:bg-green-600 transition font-semibold"
                                >
                                    <i class="fas fa-redo mr-2"></i>Reabrir
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- NÃO INICIADOS -->
        @if(count($treinamentosNaoIniciados) > 0)
            <div class="bg-white p-8 rounded-lg shadow-lg border-l-4 border-blue-500">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-play-circle text-blue-500 mr-3"></i>Não Iniciados ({{ count($treinamentosNaoIniciados) }})
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($treinamentosNaoIniciados as $training)
                        <div class="border-2 border-blue-200 rounded-lg overflow-hidden hover:shadow-lg transition bg-gradient-to-br from-blue-50 to-white">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-40 flex items-center justify-center text-white relative">
                                <i class="fas fa-play-circle text-5xl opacity-50"></i>
                                @if($training->obrigatorio)
                                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                        OBRIGATÓRIO
                                    </div>
                                @endif
                            </div>

                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800">{{ $training->titulo }}</h3>
                                    <span class="text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>

                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-blue-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span><i class="fas fa-{{ $training->obrigatorio ? 'exclamation-circle text-red-600' : 'check text-green-600' }}"></i> {{ $training->obrigatorio ? 'Obrigatório' : 'Opcional' }}</span>
                                </div>

                                <a 
                                    href="{{ route('treinamentos.player', $training->id) }}"
                                    class="block w-full bg-blue-900 text-white text-center py-2 rounded hover:bg-blue-800 transition font-semibold"
                                >
                                    <i class="fas fa-play mr-2"></i>Iniciar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(count($treinamentosDisponíveis) === 0)
            <div class="bg-gray-50 p-8 rounded text-center border-2 border-gray-200">
                <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 text-lg">Nenhum treinamento disponível para seu perfil no momento.</p>
            </div>
        @endif

    </div>

    <!-- Link para Certificados -->
    <div class="mt-8">
        <a href="{{ route('certificados.meus') }}" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-lg hover:shadow-lg transition flex items-start gap-4">
            <div class="certificate-badge bg-white/95 border-white/30 shadow-none" aria-hidden="true">
                <i class="fas fa-certificate text-3xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold">Meus Certificados</h3>
                <p class="text-orange-100 text-sm mt-2">Visualize e baixe seus certificados</p>
            </div>
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.locked-training-card').forEach(function(card) {
                const formatted = card.getAttribute('data-release-local');
                if (!formatted) return;

                const label = card.querySelector('.locked-release-label');
                if (label) {
                    label.textContent = 'Libera em ' + formatted;
                }

                card.setAttribute('title', 'Libera em ' + formatted);
            });
        });
    </script>
</div>
@endsection
