@extends('layout')

@section('title', 'Meus Certificados')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-certificate text-orange-600 mr-3"></i>Meus Certificados
    </h1>

    @if($certificates->count() > 0)
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($certificates as $cert)
                <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition border-t-4 border-orange-600">
                    <div class="text-center mb-4">
                        <i class="fas fa-certificate text-5xl text-orange-600 mb-3"></i>
                        <h3 class="text-lg font-bold text-gray-800">{{ $cert->training->titulo }}</h3>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <p><strong>Tipo:</strong> {{ ucfirst($cert->training->tipo) }}</p>
                        <p><strong>Carga Horária:</strong> {{ $cert->training->carga_horaria }} minutos</p>
                        <p><strong>Emitido em:</strong> <span class="horaEmissao">{{ $cert->data_emissao->format('d/m/Y H:i') }}</span></p>
                        <p><strong>Início do Treinamento:</strong> <span class="horaTreinamento">{{ optional($cert->data_inicio_assistencia)->format('d/m/Y H:i') }}</span></p>
                        <p><strong>Fim do Treinamento:</strong> <span class="horaFinalizacao">{{ optional($cert->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</span></p>
                        <p><strong>Tempo assistido:</strong> {{ gmdate('H:i:s', max(0, (int) $cert->tempo_assistido_segundos)) }}</p>
                        <p><strong>Código:</strong> <code class="bg-gray-100 px-2 py-1 rounded">{{ $cert->codigo_certificado }}</code></p>
                    </div>

                    <div class="flex gap-2">
                        <a 
                            href="{{ route('certificados.download', $cert) }}" 
                            class="flex-1 bg-blue-600 text-white py-2 px-4 rounded text-center hover:bg-blue-700 transition font-semibold text-sm"
                        >
                            <i class="fas fa-download mr-2"></i>Baixar
                        </a>
                        <a 
                            href="{{ route('validar.certificado', $cert->codigo_certificado) }}" 
                            target="_blank"
                            class="flex-1 bg-green-600 text-white py-2 px-4 rounded text-center hover:bg-green-700 transition font-semibold text-sm"
                        >
                            <i class="fas fa-check mr-2"></i>Validar
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginação -->
        <div class="mt-8">
            {{ $certificates->links() }}
        </div>
    @else
        <div class="bg-white p-12 rounded-lg shadow-lg text-center">
            <i class="fas fa-certificate text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Nenhum Certificado</h2>
            <p class="text-gray-600 mb-6">Você ainda não possui certificados. Complete alguns treinamentos para receber certificados.</p>
            <a href="{{ route('dashboard') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Voltar ao Dashboard
            </a>
        </div>
    @endif
</div>
@endsection
