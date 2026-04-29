@extends('layout')

@section('title', 'Teste de Vídeo')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">Teste Simples de Vídeo YouTube</h1>

    <div class="bg-white p-4 rounded-lg shadow-lg mb-8">
        <p class="mb-4 text-gray-700">Vídeo simples sem proteção nenhuma:</p>
        <iframe 
            width="100%" 
            height="500" 
            src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-lg">
        <p class="text-gray-700"><strong>Se o vídeo toca normalmente acima:</strong> O problema é no nosso código.</p>
        <p class="text-gray-700"><strong>Se o vídeo para no 1 segundo aqui também:</strong> O problema é no navegador ou servidor.</p>
    </div>

    <a href="{{ route('dashboard') }}" class="mt-8 inline-block bg-blue-900 text-white px-6 py-2 rounded">Voltar</a>
</div>
@endsection
