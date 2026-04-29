@extends('layout')

@section('title', $training->titulo)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-4xl font-bold text-gray-800">{{ $training->titulo }}</h1>
            <span class="inline-block mt-3 px-3 py-1 rounded-full text-sm font-semibold
                {{ $training->tipo === 'dss' ? 'bg-red-100 text-red-900' : 'bg-blue-100 text-blue-900' }}
            ">
                {{ strtoupper($training->tipo) }}
            </span>
        </div>
        <a href="{{ route('treinamentos.edit', $training) }}" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
            <i class="fas fa-edit mr-2"></i>Editar
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm font-semibold">Carga Horária</p>
            <p class="text-3xl font-bold text-blue-900">{{ $training->carga_horaria }} min</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm font-semibold">Status</p>
            <p class="text-lg font-bold {{ $training->status === 'ativo' ? 'text-green-600' : 'text-red-600' }}">
                {{ ucfirst($training->status) }}
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm font-semibold">Tipo de Vídeo</p>
            <p class="text-lg font-bold text-purple-600">{{ ucfirst($training->tipo_video) }}</p>
        </div>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-4">Descrição</h2>
        <p class="text-gray-700 text-lg">{{ $training->descricao }}</p>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-4">Permissões</h2>
        <div class="space-y-2">
            @foreach(['motorista', 'funcionario', 'terceirizado'] as $tipo)
                <label class="flex items-center">
                    <input type="checkbox" {{ in_array($tipo, (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} disabled class="mr-2">
                    <span>{{ ucfirst($tipo) }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('treinamentos.edit', $training) }}" class="flex-1 bg-orange-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-700 transition text-center">
            <i class="fas fa-edit mr-2"></i>Editar Treinamento
        </a>
        <a href="{{ route('treinamentos.index') }}" class="flex-1 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-500 transition text-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
</div>
@endsection
