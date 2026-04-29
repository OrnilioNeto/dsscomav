@extends('layout')

@section('title', 'Meu Dashboard')

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
                <i class="fas fa-certificate text-5xl text-orange-100"></i>
            </div>
        </div>
    </div>

    <!-- Treinamentos Disponíveis -->
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-video text-blue-900 mr-3"></i>Treinamentos Disponíveis
        </h2>

        @if($treinamentosDisponíveis->count() > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($treinamentosDisponíveis as $training)
                    @php
                        $userProgress = $progresso->where('training_id', $training->id)->first();
                        $isCompleted = $userProgress && $userProgress->concluido;
                        $porcentagem = $userProgress->porcentagem_assistida ?? 0;
                    @endphp
                    
                    <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                        <div class="bg-gradient-to-r from-blue-900 to-blue-700 h-40 flex items-center justify-center text-white relative">
                            <i class="fas fa-play-circle text-5xl opacity-50"></i>
                            @if($isCompleted)
                                <div class="absolute top-2 right-2 bg-green-500 text-white px-3 py-1 rounded-full text-sm">
                                    ✓ Concluído
                                </div>
                            @endif
                        </div>

                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-bold text-gray-800 text-lg">{{ $training->titulo }}</h3>
                                <span class="text-xs bg-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-100 text-{{ $training->tipo === 'dss' ? 'red' : 'blue' }}-900 px-2 py-1 rounded">
                                    {{ strtoupper($training->tipo) }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 mb-3">{{ Str::limit($training->descricao, 100) }}</p>

                            <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                                <span>
                                    <i class="fas fa-clock mr-1"></i>{{ $training->carga_horaria }} min
                                </span>
                                <span>
                                    <i class="fas fa-{{ $training->obrigatorio ? 'exclamation-circle text-orange-600' : 'check text-green-600' }} mr-1"></i>
                                    {{ $training->obrigatorio ? 'Obrigatório' : 'Opcional' }}
                                </span>
                            </div>

                            @if($userProgress)
                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progresso</span>
                                        <span>{{ $porcentagem }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-blue-900 h-2 rounded-full transition-all" 
                                            style="width: {{ $porcentagem }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endif

                            <a 
                                href="{{ route('treinamentos.player', $training->id) }}"
                                class="block w-full bg-blue-900 text-white text-center py-2 rounded hover:bg-blue-800 transition font-semibold"
                            >
                                <i class="fas fa-play mr-2"></i>
                                {{ $isCompleted ? 'Reabrir' : 'Assistir' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 p-8 rounded text-center">
                <i class="fas fa-video text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Nenhum treinamento disponível para seu perfil no momento.</p>
            </div>
        @endif
    </div>

    <!-- Link para Certificados -->
    <div class="mt-8">
        <a href="{{ route('certificados.meus') }}" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-certificate text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Meus Certificados</h3>
            <p class="text-orange-100 text-sm mt-2">Visualize e baixe seus certificados</p>
        </a>
    </div>
</div>
@endsection
