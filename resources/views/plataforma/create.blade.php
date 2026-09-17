@extends('layout')

@section('title', 'Plataforma — Novo Cliente')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-user-plus text-indigo-600 mr-3"></i>Novo Cliente
        </h1>
        <a href="{{ route('plataforma.index') }}" class="text-gray-600 hover:text-gray-800 font-semibold">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 text-red-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('plataforma.store') }}" class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome do cliente *</label>
                <input type="text" name="nome" required value="{{ old('nome') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Slug (subdomínio)</label>
                <input type="text" name="slug" value="{{ old('slug') }}" placeholder="ex.: transportes-a" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Em branco = gerado automaticamente do nome.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Domínio próprio (opcional)</label>
                <input type="text" name="dominio" value="{{ old('dominio') }}" placeholder="ex.: treinamentos.empresa.com.br" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="ativo" {{ old('status', 'ativo') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                    <option value="trial" {{ old('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="suspenso" {{ old('status') === 'suspenso' ? 'selected' : '' }}>Suspenso</option>
                    <option value="cancelado" {{ old('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Plano</label>
                <input type="text" name="plano" value="{{ old('plano') }}" placeholder="ex.: Pro" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome de exibição (white-label)</label>
                <input type="text" name="nome_exibicao" value="{{ old('nome_exibicao') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail remetente</label>
                <input type="email" name="email_remetente" value="{{ old('email_remetente') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>

        <h2 class="text-lg font-bold text-gray-800 mb-3">Módulos liberados</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
            @foreach($modules as $slug => $info)
                <label class="flex items-start gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 cursor-pointer">
                    <input type="checkbox" name="modules[]" value="{{ $slug }}" checked class="mt-1">
                    <span>
                        <span class="block font-semibold text-gray-800 text-sm">{{ $info['label'] }}</span>
                        <span class="block text-xs text-gray-500">{{ $info['description'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('plataforma.index') }}" class="px-6 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-check mr-2"></i>Criar Cliente
            </button>
        </div>
    </form>
</div>
@endsection