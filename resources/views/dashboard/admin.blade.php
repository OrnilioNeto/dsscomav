@extends('layout')

@section('title', 'Dashboard Administrador')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Dashboard Administrativo
    </h1>

    <!-- Estatísticas -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Usuários</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
                </div>
                <i class="fas fa-users text-5xl text-blue-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Ativos</p>
                    <p class="text-3xl font-bold text-green-600">{{ $usuariosAtivos }}</p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Treinamentos</p>
                    <p class="text-3xl font-bold text-purple-900">{{ $totalTreinamentos }}</p>
                </div>
                <i class="fas fa-video text-5xl text-purple-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificados</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $certificadosEmitidos }}</p>
                </div>
                <i class="fas fa-certificate text-5xl text-orange-100"></i>
            </div>
        </div>
    </div>

    <!-- Seções -->
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Treinamentos Recentes -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-video text-purple-900 mr-2"></i>Treinamentos Recentes
            </h2>
            <div class="space-y-3">
                @forelse($treinamentosRecentes as $training)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $training->titulo }}</p>
                            <p class="text-sm text-gray-600">{{ $training->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="bg-{{ $training->status === 'ativo' ? 'green' : 'red' }}-100 text-{{ $training->status === 'ativo' ? 'green' : 'red' }}-900 px-3 py-1 rounded-full text-sm">
                            {{ $training->status }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-600">Nenhum treinamento recente</p>
                @endforelse
            </div>
            <a href="{{ route('treinamentos.index') }}" class="mt-4 text-blue-900 font-semibold hover:text-blue-700">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>

        <!-- Usuários Recentes -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-user-plus text-green-900 mr-2"></i>Usuários Recentes
            </h2>
            <div class="space-y-3">
                @forelse($usuariosRecentes as $usuario)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $usuario->nome }}</p>
                            <p class="text-sm text-gray-600">{{ ucfirst($usuario->tipo_usuario) }}</p>
                        </div>
                        <span class="bg-{{ $usuario->status === 'ativo' ? 'green' : 'red' }}-100 text-{{ $usuario->status === 'ativo' ? 'green' : 'red' }}-900 px-3 py-1 rounded-full text-sm">
                            {{ ucfirst($usuario->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-600">Nenhum usuário recente</p>
                @endforelse
            </div>
            <a href="{{ route('usuarios.index') }}" class="mt-4 text-blue-900 font-semibold hover:text-blue-700">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="mt-8 grid md:grid-cols-2 gap-6">
        <a href="{{ route('usuarios.create') }}" class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-user-plus text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Novo Usuário</h3>
            <p class="text-blue-100 text-sm mt-2">Adicionar um novo usuário ao sistema</p>
        </a>

        <a href="{{ route('treinamentos.create') }}" class="bg-gradient-to-r from-purple-900 to-purple-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-plus text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Novo Treinamento</h3>
            <p class="text-purple-100 text-sm mt-2">Criar um novo treinamento ou DSS</p>
        </a>
    </div>
</div>
@endsection
