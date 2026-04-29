@extends('layout')

@section('title', 'Dashboard Super Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Dashboard Super Administrador
    </h1>

    <!-- Estatísticas -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total de Usuários</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
                </div>
                <i class="fas fa-users text-5xl text-blue-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Usuários Ativos</p>
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

    <!-- Gráficos e Relatórios -->
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Usuários por Tipo -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Usuários por Tipo</h2>
            <div class="space-y-3">
                @foreach($usuariosPorTipo as $tipo)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">
                            @if($tipo->tipo_usuario === 'motorista')
                                <i class="fas fa-truck mr-2"></i>Motorista
                            @elseif($tipo->tipo_usuario === 'funcionario')
                                <i class="fas fa-briefcase mr-2"></i>Funcionário
                            @else
                                <i class="fas fa-building mr-2"></i>Terceirizado
                            @endif
                        </span>
                        <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full font-semibold">{{ $tipo->total }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Taxa de Conclusão -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Taxa de Conclusão dos Treinamentos</h2>
            <div class="space-y-4">
                @foreach($treinamentos as $training)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $training->titulo }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ $taxaConclusao[$training->id] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-green-500 h-2 rounded-full transition-all" 
                                style="width: {{ $taxaConclusao[$training->id] ?? 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Links de Ação -->
    <div class="mt-8 grid md:grid-cols-2 gap-6">
        <a href="{{ route('usuarios.index') }}" class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-users text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Gerenciar Usuários</h3>
            <p class="text-blue-100 text-sm mt-2">Adicionar, editar e remover usuários</p>
        </a>

        <a href="{{ route('treinamentos.index') }}" class="bg-gradient-to-r from-purple-900 to-purple-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-video text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Gerenciar Treinamentos</h3>
            <p class="text-purple-100 text-sm mt-2">Criar, editar e publicar conteúdo</p>
        </a>
    </div>
</div>
@endsection
