@extends('layout')

@section('title', 'Validar Certificado')

@section('extra_css')
<style>
    @media print {
        nav,
        footer,
        .print-hidden,
        .no-print {
            display: none !important;
        }

        body {
            background: #ffffff !important;
        }

        .min-h-screen {
            min-height: auto !important;
        }
    }
</style>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">
    <div class="bg-white p-8 md:p-10 rounded-3xl shadow-2xl">
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
            <div class="grid lg:grid-cols-[1.3fr_0.7fr] gap-6">
                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 space-y-5">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Nome do Beneficiário</p>
                            <p class="text-xl font-bold text-gray-800">{{ $certificate->user->nome }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">CPF</p>
                            <p class="text-xl font-bold text-gray-800 font-mono">{{ $certificate->user->getCpfFormatted() }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">E-mail</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $certificate->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Telefone</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $certificate->user->telefone ?? 'Não informado' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Empresa</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $certificate->user->empresa ?? 'Não informada' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Tipo de usuário</p>
                            <p class="text-lg font-semibold text-gray-800">{{ ucfirst($certificate->user->tipo_usuario ?? 'Não informado') }}</p>
                        </div>
                    </div>

                    <div class="border-t pt-5">
                        <p class="text-gray-600 text-sm font-semibold">Treinamento</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $certificate->training->titulo }}</p>
                        <p class="text-gray-600 mt-2">{{ $certificate->training->descricao }}</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 border-t pt-5">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Carga Horária</p>
                            <p class="text-lg font-bold text-gray-800">{{ $certificate->training->carga_horaria }} minutos</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Tipo do Conteúdo</p>
                            <p class="text-lg font-bold text-gray-800">{{ strtoupper($certificate->training->tipo) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Início da Assitência</p>
                            <p class="text-lg font-bold text-gray-800">{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Finalização</p>
                            <p class="text-lg font-bold text-gray-800">{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Tempo assistido</p>
                            <p class="text-lg font-bold text-gray-800">{{ gmdate('H:i:s', max(0, (int) $certificate->tempo_assistido_segundos)) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Data de emissão</p>
                            <p class="text-lg font-bold text-gray-800">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="border-t pt-5">
                        <p class="text-gray-600 text-sm font-semibold">Código do Certificado</p>
                        <p class="text-lg font-mono tracking-widest bg-white p-3 rounded border border-green-300 text-green-700 break-all">
                            {{ $certificate->codigo_certificado }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2 break-all">{{ $certificate->validation_url }}</p>
                    </div>
                </div>

                <div class="bg-green-50 p-6 rounded-2xl border border-green-200 flex flex-col items-center justify-between gap-5">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-green-700 uppercase tracking-wide">Validação por QR Code</p>
                        <img src="{{ $qrCodeUrl ?? $certificate->qr_code_url }}" alt="QR Code do certificado" class="mx-auto mt-4 h-56 w-56 rounded-xl border bg-white p-3 shadow-sm">
                    </div>

                    <div class="w-full space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between gap-4">
                            <span class="font-semibold">Emitido em</span>
                            <span>{{ now()->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="font-semibold">Válido</span>
                            <span class="text-green-700">Sim</span>
                        </div>
                    </div>

                    <div class="flex w-full gap-3 print:hidden">
                        <button onclick="window.print()" class="flex-1 rounded-lg bg-blue-900 px-4 py-3 font-semibold text-white hover:bg-blue-800 transition">
                            <i class="fas fa-print mr-2"></i>Salvar em PDF
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex-1 rounded-lg bg-gray-200 px-4 py-3 text-center font-semibold text-gray-800 hover:bg-gray-300 transition">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@if($valido && $certificate)
<script>
    window.addEventListener('load', function () {
        setTimeout(() => window.print(), 500);
    });
</script>
@endif
@endsection
