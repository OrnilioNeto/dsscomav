@extends('layout')

@section('title', 'Ver Usuário')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-start justify-between mb-6">
        <h1 class="text-4xl font-bold text-gray-800">{{ $usuario->nome }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('usuarios.ficha.manage', $usuario->id) }}" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition">
                <i class="fas fa-id-card mr-2"></i>Ficha (EPI/Treinamentos)
            </a>
            <a href="{{ route('usuarios.edit', $usuario) }}" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-edit mr-2"></i>Editar
            </a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @if($usuario->isOnVacation())
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-900">Em férias</span>
        @endif
        @if($usuario->usuario_teste)
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-900">Usuário de teste</span>
        @endif
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Informações Pessoais</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">CPF</p>
                    <p class="text-lg font-mono">{{ $usuario->getCpfFormatted() }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Email</p>
                    <p class="text-lg">{{ $usuario->email }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Telefone</p>
                    <p class="text-lg">{{ $usuario->telefone ?? 'Não informado' }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Data de Nascimento</p>
                    <p class="text-lg">{{ $usuario->data_nascimento?->format('d/m/Y') ?? 'Não informado' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Status e Perfil</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Tipo de Usuário</p>
                    <p class="text-lg">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            @if($usuario->tipo_usuario === 'motorista')
                                bg-blue-100 text-blue-900
                            @elseif($usuario->tipo_usuario === 'funcionario')
                                bg-green-100 text-green-900
                            @else
                                bg-orange-100 text-orange-900
                            @endif
                        ">
                            {{ ucfirst($usuario->tipo_usuario) }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Perfil/Role</p>
                    <p class="text-lg font-bold">{{ ucfirst(str_replace('_', ' ', $usuario->role?->nome)) }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Status</p>
                    <p class="text-lg">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            {{ $usuario->status === 'ativo' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}
                        ">
                            {{ ucfirst($usuario->status) }}
                        </span>
                    </p>
                </div>
                @if($usuario->data_inativacao)
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Inativado em</p>
                        <p class="text-lg font-bold text-red-700">{{ $usuario->data_inativacao->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-gray-500">Usuário bloqueado do acesso e desconsiderado dos treinamentos a partir desta data.</p>
                    </div>
                @endif
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Cadastro em</p>
                    <p class="text-lg">{{ $usuario->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center justify-between">
            <h2 class="text-xl font-bold mb-4 text-center w-full">QR Code da Ficha</h2>
            <div class="flex flex-col items-center justify-center flex-grow">
                @if($usuario->qrcode_token)
                    <img src="{{ $usuario->ficha_qr_code_url }}" alt="QR Code da Ficha" class="w-40 h-40 border p-2 bg-gray-50 rounded mb-4">
                    <p class="text-[10px] text-center text-gray-500 mb-2 font-mono break-all max-w-[180px]">{{ $usuario->ficha_url }}</p>
                @else
                    <div class="text-center p-4 text-gray-500">
                        <i class="fas fa-qrcode text-4xl mb-2"></i>
                        <p class="text-xs">Nenhum QR Code gerado.</p>
                    </div>
                @endif
            </div>
            <div class="w-full mt-2 space-y-2">
                @if($usuario->qrcode_token)
                    <a href="{{ $usuario->ficha_url }}" target="_blank" class="block text-center text-sm font-semibold text-blue-900 hover:text-blue-800 hover:underline">
                        <i class="fas fa-external-link-alt mr-1"></i>Visualizar Ficha Pública
                    </a>
                @endif
                <a href="{{ route('usuarios.ficha.manage', $usuario->id) }}" class="block text-center text-sm font-semibold text-orange-600 hover:text-orange-700 hover:underline">
                    <i class="fas fa-cog mr-1"></i>Gerenciar Dados da Ficha
                </a>
            </div>
        </div>
    </div>

    <!-- Dados Específicos -->
    @if($usuario->tipo_usuario === 'motorista' && $usuario->cnh)
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h2 class="text-xl font-bold mb-4">Dados do Motorista</h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">CNH</p>
                    <p class="text-lg">{{ $usuario->cnh }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Categoria</p>
                    <p class="text-lg">{{ $usuario->categoria_cnh }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Validade</p>
                    <p class="text-lg">{{ $usuario->validade_cnh?->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Progresso -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">Progresso em Treinamentos</h2>
        @if($usuario->progress->count() > 0)
            <div class="space-y-4">
                @foreach($usuario->progress as $prog)
                    <div class="border-l-4 border-blue-900 p-4 bg-gray-50 rounded">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">{{ $prog->training->titulo }}</span>
                            <span class="text-sm {{ $prog->concluido ? 'text-green-600' : 'text-orange-600' }} font-bold">
                                {{ $prog->concluido ? '✓ Concluído' : 'Em progresso' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-900 h-2 rounded-full" style="width: {{ $prog->porcentagem_assistida }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">{{ $prog->porcentagem_assistida }}% assistido</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-600">Nenhum treinamento iniciado</p>
        @endif
    </div>

    <!-- Certificados -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">Certificados</h2>
        @if($usuario->certificates->count() > 0)
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($usuario->certificates as $cert)
                    @php
                        $statusVal = $cert->status_validade;
                        $borderCls = $statusVal === 'vencido' ? 'border-red-300 bg-red-50' : ($statusVal === 'vencendo' ? 'border-amber-300 bg-amber-50' : 'border-green-300 bg-green-50');
                        $badge = $statusVal === 'vencido'
                            ? ['bg-red-100 text-red-800', 'Vencido']
                            : ($statusVal === 'vencendo' ? ['bg-amber-100 text-amber-800', 'Vence em breve'] : ['bg-green-100 text-green-800', 'Vigente']);
                    @endphp
                    <div class="border p-4 rounded {{ $borderCls }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold {{ $statusVal === 'vencido' ? 'text-red-900' : 'text-green-900' }}">{{ $cert->training->titulo }}</p>
                            @if($statusVal !== 'sem_validade')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600">Emitido: {{ $cert->data_emissao->format('d/m/Y') }}</p>
                        @if($cert->data_validade)
                            <p class="text-sm text-gray-600">Validade: {{ $cert->data_validade->format('d/m/Y') }}</p>
                        @endif
                        <p class="text-xs text-gray-600 mt-2 font-mono">{{ $cert->codigo_certificado }}</p>
                        <a href="{{ route('certificados.download', $cert->id) }}" class="inline-block mt-3 text-sm font-semibold text-green-800 hover:text-green-900 hover:underline">
                            <i class="fas fa-download mr-1"></i>Baixar certificado
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-600">Nenhum certificado emitido</p>
        @endif
    </div>

    <div class="flex gap-4">
        <a href="{{ route('usuarios.edit', $usuario) }}" class="flex-1 bg-orange-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-700 transition text-center">
            <i class="fas fa-edit mr-2"></i>Editar
        </a>
        <a href="{{ route('usuarios.index') }}" class="flex-1 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-500 transition text-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
</div>
@endsection
