@extends('layout')

@section('title', 'Ranking de Engajamento')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-trophy text-yellow-500 mr-3"></i>Ranking de Engajamento
    </h1>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
        <p class="text-sm font-semibold text-blue-900 mb-2">Visão Geral</p>
        <p class="text-sm text-blue-900/80 leading-6">
            Este painel apresenta os usuários mais engajados com a plataforma, com base em critérios como velocidade de conclusão, foco na assistência e volume de treinamentos finalizados.
            As métricas são projetadas para identificar os "campeões" de engajamento e podem ser usadas para programas de reconhecimento e premiação.
        </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
        <!-- Ranking de Pioneiros -->
        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-blue-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-rocket text-blue-500 mr-2"></i>Pioneiros
            </h2>
            <p class="text-gray-600 text-sm mb-4">Usuários que concluem treinamentos mais rápido após a publicação.</p>
            <ul class="space-y-3">
                @forelse($pioneiros as $index => $pioneiro)
                    <li class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-xl font-bold text-blue-700 mr-3">{{ $index + 1 }}º</span>
                            <div>
                                <p class="font-semibold text-gray-800">{{ optional($pioneiro->user)->nome ?? 'Usuário Removido' }}</p>
                                <p class="text-sm text-gray-600">{{ optional($pioneiro->user)->cargo ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-blue-600">{{ round($pioneiro->tempo_reacao / 3600, 1) }}h</span>
                    </li>
                @empty
                    <li class="text-gray-600 italic">Nenhum pioneiro encontrado ainda.</li>
                @endforelse
            </ul>
        </div>

        <!-- Ranking de Foco -->
        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-green-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-eye text-green-500 mr-2"></i>Focados
            </h2>
            <p class="text-gray-600 text-sm mb-4">Usuários que assistem o conteúdo de forma mais contínua e eficiente.</p>
            <ul class="space-y-3">
                @forelse($focados as $index => $focado)
                    <li class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-xl font-bold text-green-700 mr-3">{{ $index + 1 }}º</span>
                            <div>
                                <p class="font-semibold text-gray-800">{{ optional($focado->user)->nome ?? 'Usuário Removido' }}</p>
                                <p class="text-sm text-gray-600">{{ optional($focado->user)->empresa ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-green-600">{{ number_format($focado->fluidez_ratio, 2) }}x</span>
                    </li>
                @empty
                    <li class="text-gray-600 italic">Nenhum usuário focado encontrado ainda.</li>
                @endforelse
            </ul>
        </div>

        <!-- Top Geral (Score de Engajamento) -->
        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-purple-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-star text-purple-500 mr-2"></i>Top Geral
            </h2>
            <p class="text-gray-600 text-sm mb-4">Usuários com maior número de conclusões e tempo total assistido.</p>
            <ul class="space-y-3">
                @forelse($topGeral as $index => $topUser)
                    <li class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-xl font-bold text-purple-700 mr-3">{{ $index + 1 }}º</span>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $topUser->nome }}</p>
                                <p class="text-sm text-gray-600">{{ $topUser->concluidos }} treinamentos</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-purple-600">{{ gmdate('H:i', $topUser->tempo_total) }}h</span>
                    </li>
                @empty
                    <li class="text-gray-600 italic">Nenhum usuário no top geral ainda.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-8 flex justify-end space-x-4">
        <a href="{{ route('admin.ranking.settings') }}" class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition font-semibold">
            <i class="fas fa-sliders-h mr-2"></i>Configurações do Ranking
        </a>
        <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition font-semibold">
            <i class="fas fa-arrow-left mr-2"></i>Voltar ao Dashboard
        </a>
    </div>
</div>
@endsection