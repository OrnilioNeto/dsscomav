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
    <style>
        .profile-moldura {
            position: relative;
            padding: 4px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
        }
        .tier-mythic  { background: linear-gradient(135deg, #7c3aed, #db2777); box-shadow: 0 0 18px rgba(124,58,237,0.6); }
        .tier-titan   { background: linear-gradient(135deg, #ef4444, #f97316); box-shadow: 0 0 12px rgba(239,68,68,0.5); }
        .tier-imperial{ background: linear-gradient(135deg, #f97316, #facc15); }
        .tier-elite   { background: linear-gradient(135deg, #0ea5e9, #2dd4bf); }
        .tier-silver  { background: linear-gradient(135deg, #64748b, #94a3b8); }
        .tier-bronze  { background: linear-gradient(135deg, #b45309, #d97706); }
    </style>

    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Meu Dashboard
    </h1>

    <!-- Perfil e Nível -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 flex flex-col md:flex-row items-center gap-6 border border-gray-100">
        <div class="profile-moldura {{ $rankingLevel['class'] }}">
            <img src="{{ Auth::user()->getFotoPerfilUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-white">
            <div class="absolute -bottom-2 -right-2 bg-white rounded-full p-2 shadow-md">
                <i class="fas {{ $rankingLevel['icon'] }}" style="color: {{ $rankingLevel['color'] }}"></i>
            </div>
        </div>
        <div class="flex-1 text-center md:text-left">
            <h2 class="text-2xl font-black text-gray-900">Olá, {{ explode(' ', Auth::user()->nome)[0] }}!</h2>
            <p class="text-gray-500 font-medium">
                Seu nível: 
                <span class="font-bold" style="color: {{ $rankingLevel['color'] }}">{{ $rankingLevel['name'] }}</span>
                <span class="text-gray-400 font-normal">— {{ $rankingLevel['sub'] }}</span>
            </p>
            <p class="text-sm text-gray-400 mt-1 italic">"{{ $rankingLevel['msg'] }}"</p>
        </div>
        <div class="text-center">
            <div class="flex gap-4 md:gap-8 justify-center mb-1">
                <div>
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Posição</p>
                    <p class="text-4xl font-black text-blue-900">{{ $userRank > 0 ? $userRank . 'º' : '--' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Pontos</p>
                    <p class="text-4xl font-black text-blue-900">{{ number_format($totalPoints, 0) }}</p>
                </div>
            </div>
            <a href="{{ route('profile.stats') }}" class="mt-2 inline-block text-sm font-bold text-blue-600 hover:underline">
                Como ganhei esses pontos? <i class="fas fa-arrow-right ml-1 text-xs"></i>
            </a>
        </div>
    </div>

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
    <div class="space-y-4" id="secoes-treinamentos">

        @php
            // Define qual seção fica no topo (expandida por padrão)
            // Prioridade: pendentes > não iniciados > concluídos > bloqueados
            $secaoPrincipal = null;
            if (count($treinamentosPendentes) > 0) $secaoPrincipal = 'pendentes';
            elseif (count($treinamentosNaoIniciados) > 0) $secaoPrincipal = 'nao_iniciados';
            elseif (count($treinamentosConcluidos) > 0) $secaoPrincipal = 'concluidos';
            elseif (count($treinamentosBloqueados) > 0) $secaoPrincipal = 'bloqueados';
        @endphp

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- PENDENTES                                                   --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if(count($treinamentosPendentes) > 0)
        @php $isPrincipal = $secaoPrincipal === 'pendentes'; @endphp
        <div class="bg-white rounded-xl shadow-md border-l-4 border-orange-500 overflow-hidden secao-treinamento"
             id="secao-pendentes">

            {{-- Cabeçalho clicável --}}
            <button type="button"
                    onclick="toggleSecao('pendentes')"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-orange-50 transition-colors focus:outline-none group">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100">
                        <i class="fas fa-hourglass-half text-orange-500 text-lg"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            Em Andamento
                            <span class="text-sm font-bold bg-orange-500 text-white px-2 py-0.5 rounded-full">{{ count($treinamentosPendentes) }}</span>
                            @if($isPrincipal)
                                <span class="text-xs font-semibold bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Continue de onde parou</span>
                            @endif
                        </h2>
                        <p class="text-xs text-gray-500">Você já iniciou {{ count($treinamentosPendentes) === 1 ? 'este conteúdo' : 'estes conteúdos' }} — finalize para ganhar pontos</p>
                    </div>
                </div>
                <i id="icon-pendentes" class="fas fa-chevron-{{ $isPrincipal ? 'up' : 'down' }} text-gray-400 group-hover:text-orange-500 transition-transform text-lg"></i>
            </button>

            {{-- Conteúdo da seção --}}
            <div id="body-pendentes" class="{{ $isPrincipal ? '' : 'hidden' }} px-6 pb-6">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
                    @foreach($treinamentosPendentes as $training)
                        @php
                            $userProgress = $progresso->where('training_id', $training->id)->first();
                            $porcentagem = $userProgress->porcentagem_assistida ?? 0;
                        @endphp
                        <div class="border-2 border-orange-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow bg-gradient-to-br from-orange-50 to-white">
                            <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-36 flex items-center justify-center text-white relative">
                                <i class="fas fa-play-circle text-5xl opacity-50"></i>
                                <div class="absolute top-2 right-2 bg-orange-700 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    {{ $porcentagem }}%
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800 leading-snug">{{ $training->titulo }}</h3>
                                    <span class="ml-2 shrink-0 text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>
                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progresso</span><span>{{ $porcentagem }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $porcentagem }}%"></div>
                                    </div>
                                </div>
                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-orange-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span><i class="fas fa-{{ $training->obrigatorio ? 'exclamation-circle text-orange-600' : 'check text-green-600' }}"></i> {{ $training->obrigatorio ? 'Obrigatório' : 'Opcional' }}</span>
                                </div>
                                <a href="{{ route('treinamentos.player', $training->id) }}"
                                   class="block w-full bg-orange-500 text-white text-center py-2 rounded hover:bg-orange-600 transition font-semibold">
                                    <i class="fas fa-play mr-2"></i>Continuar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- NÃO INICIADOS                                               --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if(count($treinamentosNaoIniciados) > 0)
        @php $isPrincipal = $secaoPrincipal === 'nao_iniciados'; @endphp
        <div class="bg-white rounded-xl shadow-md border-l-4 border-blue-500 overflow-hidden secao-treinamento"
             id="secao-nao_iniciados">

            <button type="button"
                    onclick="toggleSecao('nao_iniciados')"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-blue-50 transition-colors focus:outline-none group">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100">
                        <i class="fas fa-play-circle text-blue-500 text-lg"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            Não Iniciados
                            <span class="text-sm font-bold bg-blue-500 text-white px-2 py-0.5 rounded-full">{{ count($treinamentosNaoIniciados) }}</span>
                            @if($isPrincipal)
                                <span class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Novo para você</span>
                            @endif
                        </h2>
                        <p class="text-xs text-gray-500">Conteúdos disponíveis aguardando seu início</p>
                    </div>
                </div>
                <i id="icon-nao_iniciados" class="fas fa-chevron-{{ $isPrincipal ? 'up' : 'down' }} text-gray-400 group-hover:text-blue-500 transition-transform text-lg"></i>
            </button>

            <div id="body-nao_iniciados" class="{{ $isPrincipal ? '' : 'hidden' }} px-6 pb-6">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
                    @foreach($treinamentosNaoIniciados as $training)
                        <div class="border-2 border-blue-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow bg-gradient-to-br from-blue-50 to-white">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-36 flex items-center justify-center text-white relative">
                                <i class="fas fa-play-circle text-5xl opacity-50"></i>
                                @if($training->obrigatorio)
                                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                        OBRIGATÓRIO
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800 leading-snug">{{ $training->titulo }}</h3>
                                    <span class="ml-2 shrink-0 text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>
                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-blue-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span><i class="fas fa-{{ $training->obrigatorio ? 'exclamation-circle text-red-600' : 'check text-green-600' }}"></i> {{ $training->obrigatorio ? 'Obrigatório' : 'Opcional' }}</span>
                                </div>
                                <a href="{{ route('treinamentos.player', $training->id) }}"
                                   class="block w-full bg-blue-900 text-white text-center py-2 rounded hover:bg-blue-800 transition font-semibold">
                                    <i class="fas fa-play mr-2"></i>Iniciar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- CONCLUÍDOS                                                  --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if(count($treinamentosConcluidos) > 0)
        @php $isPrincipal = $secaoPrincipal === 'concluidos'; @endphp
        <div class="bg-white rounded-xl shadow-md border-l-4 border-green-500 overflow-hidden secao-treinamento"
             id="secao-concluidos">

            <button type="button"
                    onclick="toggleSecao('concluidos')"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-green-50 transition-colors focus:outline-none group">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            Concluídos
                            <span class="text-sm font-bold bg-green-500 text-white px-2 py-0.5 rounded-full">{{ count($treinamentosConcluidos) }}</span>
                        </h2>
                        <p class="text-xs text-gray-500">Parabéns! Conteúdos que você já finalizou</p>
                    </div>
                </div>
                <i id="icon-concluidos" class="fas fa-chevron-{{ $isPrincipal ? 'up' : 'down' }} text-gray-400 group-hover:text-green-500 transition-transform text-lg"></i>
            </button>

            <div id="body-concluidos" class="{{ $isPrincipal ? '' : 'hidden' }} px-6 pb-6">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
                    @foreach($treinamentosConcluidos as $training)
                        <div class="border-2 border-green-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow bg-gradient-to-br from-green-50 to-white">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-36 flex items-center justify-center text-white relative">
                                <i class="fas fa-check-circle text-5xl opacity-70"></i>
                                <div class="absolute top-2 right-2 bg-green-700 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    100%
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800 leading-snug">{{ $training->titulo }}</h3>
                                    <span class="ml-2 shrink-0 text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>
                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-green-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span class="text-green-600"><i class="fas fa-check-circle"></i> Concluído</span>
                                </div>
                                <a href="{{ route('treinamentos.player', $training->id) }}"
                                   class="block w-full bg-green-500 text-white text-center py-2 rounded hover:bg-green-600 transition font-semibold">
                                    <i class="fas fa-redo mr-2"></i>Reabrir
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- BLOQUEADOS                                                  --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if(count($treinamentosBloqueados) > 0)
        @php $isPrincipal = $secaoPrincipal === 'bloqueados'; @endphp
        <div class="bg-white rounded-xl shadow-md border-l-4 border-gray-400 overflow-hidden secao-treinamento"
             id="secao-bloqueados">

            <button type="button"
                    onclick="toggleSecao('bloqueados')"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition-colors focus:outline-none group">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100">
                        <i class="fas fa-lock text-gray-500 text-lg"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            Ainda não liberados
                            <span class="text-sm font-bold bg-gray-400 text-white px-2 py-0.5 rounded-full">{{ count($treinamentosBloqueados) }}</span>
                        </h2>
                        <p class="text-xs text-gray-500">Conteúdos com data de liberação futura</p>
                    </div>
                </div>
                <i id="icon-bloqueados" class="fas fa-chevron-{{ $isPrincipal ? 'up' : 'down' }} text-gray-400 group-hover:text-gray-600 transition-transform text-lg"></i>
            </button>

            <div id="body-bloqueados" class="{{ $isPrincipal ? '' : 'hidden' }} px-6 pb-6">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
                    @foreach($treinamentosBloqueados as $training)
                        <div class="border-2 border-gray-200 rounded-lg overflow-hidden bg-gradient-to-br from-gray-50 to-white opacity-80 cursor-not-allowed locked-training-card"
                             data-release-local="{{ optional($training->data_liberacao)->format('d/m/Y, H:i') }}"
                             title="Carregando data de liberação...">
                            <div class="bg-gradient-to-r from-gray-500 to-gray-600 h-36 flex items-center justify-center text-white relative">
                                <i class="fas fa-lock text-5xl opacity-70"></i>
                                <div class="absolute top-2 right-2 bg-gray-800 text-white px-2 py-1 rounded-full text-xs font-bold">
                                    BLOQUEADO
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-bold text-gray-800 leading-snug">{{ $training->titulo }}</h3>
                                    <span class="ml-2 shrink-0 text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                        {{ strtoupper($training->tipo) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 80) }}</p>
                                <div class="flex gap-2 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-clock text-gray-500"></i> {{ $training->carga_horaria }} min</span>
                                    <span class="text-gray-500"><i class="fas fa-lock"></i> Aguardando liberação</span>
                                </div>
                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-600 locked-release-label mb-3">
                                    Libera em breve
                                </div>
                                <button type="button" disabled
                                        class="block w-full bg-gray-400 text-white text-center py-2 rounded font-semibold cursor-not-allowed">
                                    <i class="fas fa-lock mr-2"></i>Ainda não liberado
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if(count($treinamentosDisponíveis) === 0 && count($treinamentosBloqueados) === 0)
            <div class="bg-gray-50 p-8 rounded-xl text-center border-2 border-gray-200">
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

    <div class="mt-6 overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <div class="mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-800">
                    <i class="fab fa-whatsapp"></i>
                    Suporte
                </div>
                <p class="text-sm font-semibold text-gray-900">Dúvidas sobre o sistema ou conteúdo?</p>
                <p class="text-xs text-gray-600">Fale no WhatsApp com a equipe de apoio.</p>
            </div>

            <a href="https://wa.me/5584994017097" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-emerald-700">
                <i class="fab fa-whatsapp text-lg"></i>
                <span class="whitespace-nowrap">84 99401-7097</span>
            </a>
        </div>
    </div>

    <script>
        // ── Exibir/ocultar seções ─────────────────────────────────────
        function toggleSecao(id) {
            const body = document.getElementById('body-' + id);
            const icon = document.getElementById('icon-' + id);
            if (!body || !icon) return;

            const aberto = !body.classList.contains('hidden');
            body.classList.toggle('hidden', aberto);
            icon.classList.toggle('fa-chevron-up', !aberto);
            icon.classList.toggle('fa-chevron-down', aberto);
        }

        // ── Data de liberação nos cards bloqueados ────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.locked-training-card').forEach(function (card) {
                const formatted = card.getAttribute('data-release-local');
                if (!formatted) return;
                const label = card.querySelector('.locked-release-label');
                if (label) label.textContent = 'Libera em ' + formatted;
                card.setAttribute('title', 'Libera em ' + formatted);
            });
        });
    </script>
</div>
@endsection
