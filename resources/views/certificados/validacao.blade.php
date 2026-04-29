@extends('layout')

@section('title', 'Validar Certificado')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="bg-white p-10 rounded-lg shadow-2xl">
        <div class="text-center mb-8">
            @if($valido)
                <i class="fas fa-check-circle text-6xl text-green-600 mb-4"></i>
                <h1 class="text-4xl font-bold text-green-600 mb-2">Certificado Válido</h1>
                <p class="text-gray-600">Este certificado é autêntico e válido</p>
            @else
                <i class="fas fa-times-circle text-6xl text-red-600 mb-4"></i>
                <h1 class="text-4xl font-bold text-red-600 mb-2">Certificado Inválido</h1>
                <p class="text-gray-600">{{ $mensagem ?? 'Este certificado não é válido' }}</p>
            @endif
        </div>

        @if($valido && $certificate)
            <div class="bg-gray-50 p-6 rounded-lg border-l-4 border-green-600 space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Nome do Beneficiário</p>
                        <p class="text-xl font-bold text-gray-800">{{ $certificate->user->nome }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">CPF</p>
                        <p class="text-xl font-bold text-gray-800 font-mono">{{ $certificate->user->getCpfFormatted() }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-gray-600 text-sm font-semibold">Treinamento</p>
                    <p class="text-xl font-bold text-gray-800">{{ $certificate->training->titulo }}</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Carga Horária</p>
                        <p class="text-xl font-bold text-gray-800">{{ $certificate->training->carga_horaria }} minutos</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Data de Emissão</p>
                        <p class="text-xl font-bold text-gray-800">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-gray-600 text-sm font-semibold">Código do Certificado</p>
                    <p class="text-lg font-mono tracking-widest bg-white p-3 rounded border border-green-300 text-green-700">
                        {{ $certificate->codigo_certificado }}
                    </p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600 mb-4">Consultado em {{ now()->format('d/m/Y H:i') }}</p>
                <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-2"></i>Imprimir
                </button>
            </div>
        @endif
    </div>
</div>
@endsection
