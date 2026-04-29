@extends('layout')

@section('title', 'Bem-vindo - Plataforma DSS')

@section('content')
<div class="bg-gradient-to-br from-blue-900 to-blue-700 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <i class="fas fa-graduation-cap text-6xl mb-4"></i>
        <h1 class="text-5xl font-bold mb-4">Plataforma DSS</h1>
        <p class="text-xl mb-8">Sistema de Treinamento Corporativo - Diálogo Semanal de Segurança</p>
        
        @if(Auth::check())
            <a href="{{ route('dashboard') }}" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg inline-block transition">
                <i class="fas fa-arrow-right mr-2"></i> Ir para Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg inline-block transition">
                <i class="fas fa-sign-in-alt mr-2"></i> Fazer Login
            </a>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-16">
    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-video text-4xl text-blue-900 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Conteúdo em Vídeo</h3>
            <p class="text-gray-600">Assista a treinamentos e DSS quando quiser</p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-chart-line text-4xl text-blue-900 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Acompanhamento</h3>
            <p class="text-gray-600">Monitore seu progresso em tempo real</p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-certificate text-4xl text-blue-900 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Certificados</h3>
            <p class="text-gray-600">Receba certificados automáticos</p>
        </div>
    </div>
</div>
@endsection
