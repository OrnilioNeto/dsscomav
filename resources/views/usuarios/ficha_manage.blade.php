@extends('layout')

@section('title', 'Gerenciar Ficha do Funcionário')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 border-2 border-blue-900">
                <img src="{{ $usuario->getFotoPerfilUrl() }}" alt="Foto de {{ $usuario->nome }}" class="w-full h-full object-cover">
            </div>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $usuario->nome }}</h1>
                <p class="text-gray-600 text-sm">
                    CPF: <span class="font-mono">{{ $usuario->getCpfFormatted() }}</span> | 
                    Cargo: <strong>{{ $usuario->cargo ?? 'Não informado' }}</strong>
                </p>
            </div>
        </div>
        <div class="mt-4 md:mt-0 flex gap-2">
            <a href="{{ route('usuarios.show', $usuario) }}" class="bg-gray-500 text-white px-5 py-2 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Voltar ao Perfil
            </a>
            <a href="{{ $usuario->ficha_url }}" target="_blank" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-external-link-alt mr-2"></i>Ver Ficha Pública
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
            <p class="font-bold">Sucesso!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Coluna Esquerda: QR Code -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-blue-900 text-center">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Código QR da Ficha</h2>
                
                @if($usuario->qrcode_token)
                    <div class="flex justify-center mb-6">
                        <div class="p-3 bg-gray-50 border rounded-lg shadow-inner">
                            <img src="{{ $usuario->ficha_qr_code_url }}" alt="QR Code" class="w-48 h-48">
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded border text-left mb-6">
                        <p class="text-xs text-gray-500 font-semibold mb-1">URL Pública da Ficha:</p>
                        <p class="text-xs font-mono text-blue-900 break-all">{{ $usuario->ficha_url }}</p>
                    </div>

                    <div class="space-y-3">
                        <button onclick="printQrCode('{{ $usuario->ficha_qr_code_url }}', '{{ $usuario->nome }}')" class="w-full bg-blue-900 text-white font-bold py-2.5 px-4 rounded-lg hover:bg-blue-800 transition flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i>Imprimir QR Code
                        </button>
                        
                        <form action="{{ route('usuarios.ficha.regenerateToken', $usuario->id) }}" method="POST" onsubmit="return confirm('ATENÇÃO: Isso invalidará o QR Code atual. Deseja realmente gerar um novo QR Code?')">
                            @csrf
                            <button type="submit" class="w-full bg-red-100 text-red-700 font-bold py-2.5 px-4 rounded-lg hover:bg-red-200 transition flex items-center justify-center">
                                <i class="fas fa-sync-alt mr-2"></i>Gerar Novo QR Code
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-8 text-gray-500">
                        <i class="fas fa-qrcode text-5xl mb-3"></i>
                        <p>Nenhum token QR Code gerado para este usuário.</p>
                        <form action="{{ route('usuarios.ficha.regenerateToken', $usuario->id) }}" method="POST" class="mt-4">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">
                                Gerar QR Code
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            
            <div class="bg-gray-50 rounded-lg p-5 border border-gray-200 text-sm text-gray-600">
                <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-info-circle text-blue-900 mr-2"></i>Como Funciona?
                </h3>
                <p class="mb-2">O QR Code ao lado direciona para uma página pública segura e sem necessidade de login.</p>
                <p class="mb-2">Ao ser lido (ex: por fiscais de obra, clientes ou transportadoras), ele exibirá:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Histórico de <strong>Treinamentos</strong> e validades</li>
                    <li>Registro de <strong>EPIs</strong> entregues</li>
                    <li>Lista de <strong>DSS</strong> assistidos e concluídos</li>
                </ul>
            </div>
        </div>

        <!-- Coluna Direita: Gerenciamento de Treinamentos e EPIs -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Seção 1: Treinamentos Externos (NRs) -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-graduation-cap text-blue-900 mr-3"></i>Treinamentos e NRs (Externos)
                </h2>

                <!-- Formulário de cadastro de Treinamento -->
                <form action="{{ route('usuarios.ficha.storeTraining', $usuario->id) }}" method="POST" class="bg-gray-50 p-4 rounded-lg border mb-6">
                    @csrf
                    <h3 class="font-bold text-gray-700 mb-3 text-sm">Registrar Novo Treinamento Externo</h3>
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="t_nome" class="block text-xs font-semibold text-gray-600 mb-1">Nome do Treinamento *</label>
                            <input type="text" id="t_nome" name="nome" placeholder="Ex: NR-20, NR-35, Integração" required class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="t_data_treinamento" class="block text-xs font-semibold text-gray-600 mb-1">Data do Treinamento *</label>
                            <input type="date" id="t_data_treinamento" name="data_treinamento" required class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="t_data_validade" class="block text-xs font-semibold text-gray-600 mb-1">Data de Validade (Opcional)</label>
                            <input type="date" id="t_data_validade" name="data_validade" class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="t_observacoes" class="block text-xs font-semibold text-gray-600 mb-1">Observações (Opcional)</label>
                            <input type="text" id="t_observacoes" name="observacoes" placeholder="Ex: Certificado emitido por SENAI" class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded text-sm hover:bg-green-700 transition">
                            <i class="fas fa-plus mr-1"></i>Registrar Treinamento
                        </button>
                    </div>
                </form>

                <!-- Listagem de Treinamentos Cadastrados -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="p-3 text-gray-700 font-semibold">Treinamento</th>
                                <th class="p-3 text-gray-700 font-semibold">Data Treinamento</th>
                                <th class="p-3 text-gray-700 font-semibold">Validade</th>
                                <th class="p-3 text-gray-700 font-semibold">Status</th>
                                <th class="p-3 text-gray-700 font-semibold text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usuario->employeeTrainings as $training)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-gray-800">
                                        {{ $training->nome }}
                                        @if($training->observacoes)
                                            <span class="block text-xs text-gray-500 font-normal">{{ $training->observacoes }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-gray-600">{{ $training->data_treinamento->format('d/m/Y') }}</td>
                                    <td class="p-3 text-gray-600">{{ $training->data_validade ? $training->data_validade->format('d/m/Y') : 'Não expira' }}</td>
                                    <td class="p-3">
                                        @if($training->isExpired())
                                            <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 font-bold">Vencido</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 font-bold">Vigente</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <form action="{{ route('usuarios.ficha.destroyTraining', $training->id) }}" method="POST" class="inline" onsubmit="return confirm('Deseja realmente remover este registro?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-gray-500 italic">
                                        Nenhum treinamento registrado manualmente.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seção 2: Entregas de EPI -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-hard-hat text-blue-900 mr-3"></i>Entregas de EPIs
                </h2>

                <p class="text-sm text-gray-600 mb-4">
                    As entregas de EPI são registradas exclusivamente pelo Módulo de Saúde e Segurança (NR-06).
                    <a href="{{ route('epi.index') }}" target="_blank" class="text-blue-700 font-semibold hover:underline">Acessar o Módulo de EPI</a>
                </p>
            </div>

        </div>
    </div>
</div>

<script>
    function printQrCode(url, name) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Imprimir QR Code - ${name}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        margin-top: 50px;
                    }
                    .container {
                        border: 2px solid #153B2E;
                        padding: 30px;
                        display: inline-block;
                        border-radius: 10px;
                        background: #fff;
                    }
                    img {
                        width: 250px;
                        height: 250px;
                    }
                    h2 {
                        color: #153B2E;
                        margin-bottom: 5px;
                    }
                    p {
                        color: #666;
                        font-size: 14px;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #999;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>COMAV Transportes</h2>
                    <p>Ficha do Funcionário: <strong>${name}</strong></p>
                    <img src="${url}" alt="QR Code">
                    <p class="footer">Escaneie para visualizar a ficha de conformidade do colaborador</p>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
@endsection
