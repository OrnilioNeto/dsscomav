@extends('layout')

@section('title', 'Ver Usuário')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-start justify-between mb-6">
        <h1 class="text-4xl font-bold text-gray-800">{{ $usuario->nome }}</h1>
        <a href="{{ route('usuarios.edit', $usuario) }}" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
            <i class="fas fa-edit mr-2"></i>Editar
        </a>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-8">
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
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Cadastro em</p>
                    <p class="text-lg">{{ $usuario->created_at->format('d/m/Y H:i') }}</p>
                </div>
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
                    <div class="border border-green-300 p-4 rounded bg-green-50">
                        <p class="font-semibold text-green-900">{{ $cert->training->titulo }}</p>
                        <p class="text-sm text-gray-600">Emitido: {{ $cert->data_emissao->format('d/m/Y') }}</p>
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
