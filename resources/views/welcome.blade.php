@extends('layout')

@section('title', 'Bem-vindo - Plataforma DSS')

@section('content')
<div class="text-white py-20" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-700) 100%);">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <x-logo alt="Logo COMAV" class="mx-auto mb-6" height="72px" />
        <h1 class="text-5xl font-bold mb-4">Previa Segurança</h1>
        <p class="text-xl mb-8" style="color: rgba(255,255,255,0.9);">Sistema de Treinamento Corporativo - Diálogo Semanal de Segurança</p>
        
        @if(Auth::check())
            <a href="{{ route('dashboard') }}" class="text-white font-bold py-3 px-8 rounded-lg inline-block transition" style="background: var(--accent);">
                <i class="fas fa-arrow-right mr-2"></i> Ir para Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="text-white font-bold py-3 px-8 rounded-lg inline-block transition" style="background: var(--accent);">
                <i class="fas fa-sign-in-alt mr-2"></i> Fazer Login
            </a>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-16">
    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-video text-4xl mb-4" style="color: var(--primary);"></i>
            <h3 class="text-xl font-bold mb-2">Conteúdo em Vídeo</h3>
            <p class="text-gray-600">Assista a treinamentos e DSS quando quiser</p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-chart-line text-4xl mb-4" style="color: var(--primary);"></i>
            <h3 class="text-xl font-bold mb-2">Acompanhamento</h3>
            <p class="text-gray-600">Monitore seu progresso em tempo real</p>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg text-center hover:shadow-xl transition">
            <i class="fas fa-certificate text-4xl mb-4" style="color: var(--primary);"></i>
            <h3 class="text-xl font-bold mb-2">Certificados</h3>
            <p class="text-gray-600">Receba certificados automáticos</p>
        </div>
    </div>
</div>
@endsection
