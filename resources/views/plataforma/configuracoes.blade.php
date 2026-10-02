@extends('layout')

@section('title', 'Plataforma — Certificado padrão')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-award text-indigo-600 mr-3"></i>Certificado padrão da plataforma
        </h1>
        <a href="{{ route('plataforma.index') }}" class="text-gray-600 hover:text-gray-800 font-semibold">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('plataforma.settings.update') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Imagem de fundo (base do certificado)</label>
            <input type="file" name="fundo_certificado" accept="image/png,image/jpeg,image/webp" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">
                Imagem A4 paisagem (proporção 297:210 — ex.: 2970x2100 px, PNG/JPG, até 5 MB).
                Ela fica ao fundo, atrás dos dados do certificado, para a <strong>empresa raiz</strong> (sem tenant).
                Clientes com fundo próprio no painel de cada cliente continuam usando o fundo deles.
            </p>
        </div>

        @if($setting->fundoCertificadoUrl())
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-600 mb-2">Fundo atual:</p>
                <img src="{{ $setting->fundoCertificadoUrl() }}" alt="Fundo padrão atual do certificado" class="w-full max-w-xl object-contain border border-gray-200 rounded p-1 bg-white">
            </div>
        @else
            <p class="text-sm text-gray-500 italic mb-6">Nenhum fundo padrão configurado. Sem ele, o sistema usa a imagem padrão do sistema (ou uma moldura simples).</p>
        @endif

        <div class="flex justify-end gap-3">
            <a href="{{ route('plataforma.index') }}" class="px-6 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
        </div>
    </form>
</div>
@endsection
