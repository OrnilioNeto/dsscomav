@extends('layout')

@section('title', 'Usuários Excluídos dos KPIs')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>Usuários Excluídos dos KPIs
        </h1>
        <a href="{{ route('usuarios.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    <!-- Resumo Geral -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-semibold">Total de Usuários</p>
                    <p class="text-4xl font-bold">{{ $totalUsuarios }}</p>
                </div>
                <i class="fas fa-users text-5xl opacity-20"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-semibold">Elegíveis para KPIs</p>
                    <p class="text-4xl font-bold">{{ $usuariosEligiveisKPI }}</p>
                </div>
                <i class="fas fa-check-circle text-5xl opacity-20"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-semibold">Excluídos</p>
                    <p class="text-4xl font-bold">{{ $totalExcluidosKPI }}</p>
                </div>
                <i class="fas fa-times-circle text-5xl opacity-20"></i>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex gap-4">
            <button onclick="showTab('super-admin')" class="tab-btn active px-6 py-3 font-semibold text-gray-700 border-b-2 border-orange-600">
                <i class="fas fa-crown mr-2"></i>Super Admin <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded ml-2 text-sm">{{ count($superAdmins) }}</span>
            </button>
            <button onclick="showTab('teste')" class="tab-btn px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                <i class="fas fa-flask mr-2"></i>Usuários de Teste <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded ml-2 text-sm">{{ count($usuariosTeste) }}</span>
            </button>
            <button onclick="showTab('admin-sem-participacao')" class="tab-btn px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                <i class="fas fa-user-slash mr-2"></i>Admin sem Treinamentos <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded ml-2 text-sm">{{ count($adminsSemParticipacaoTreinamentos) }}</span>
            </button>
            <button onclick="showTab('ferias')" class="tab-btn px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                <i class="fas fa-umbrella-beach mr-2"></i>Em Férias <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded ml-2 text-sm">{{ count($usuariosEmFerias) }}</span>
            </button>
            <button onclick="showTab('inativos')" class="tab-btn px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-gray-300">
                <i class="fas fa-user-slash mr-2"></i>Inativos <span class="bg-red-100 text-red-700 px-2 py-1 rounded ml-2 text-sm">{{ count($usuariosInativos) }}</span>
            </button>
        </div>
    </div>

    <!-- Super Admin Tab -->
    <div id="super-admin" class="tab-content">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-orange-50 px-6 py-4 border-b border-orange-200">
                <h2 class="text-xl font-bold text-orange-900">
                    <i class="fas fa-crown mr-2"></i>Super Administradores
                </h2>
                <p class="text-orange-700 text-sm mt-1">{{ count($superAdmins) }} usuário(s) com role de super_admin - SEMPRE excluído(s) dos KPIs</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($superAdmins as $usuario)
                            <tr class="border-b hover:bg-orange-50 transition">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $usuario->nome }}</td>
                                <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4">
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
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        {{ $usuario->status === 'ativo' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}
                                    ">
                                        {{ ucfirst($usuario->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-600">
                                    <i class="fas fa-check-circle text-3xl text-green-400 mb-2 block"></i>
                                    Nenhum super_admin encontrado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Usuários de Teste Tab -->
    <div id="teste" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-purple-50 px-6 py-4 border-b border-purple-200">
                <h2 class="text-xl font-bold text-purple-900">
                    <i class="fas fa-flask mr-2"></i>Usuários de Teste
                </h2>
                <p class="text-purple-700 text-sm mt-1">{{ count($usuariosTeste) }} usuário(s) marcado(s) como teste - SEMPRE excluído(s) dos KPIs</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosTeste as $usuario)
                            <tr class="border-b hover:bg-purple-50 transition">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $usuario->nome }}</td>
                                <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4">
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
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        {{ $usuario->status === 'ativo' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}
                                    ">
                                        {{ ucfirst($usuario->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="text-orange-600 hover:text-orange-900 text-sm">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-600">
                                    <i class="fas fa-check-circle text-3xl text-green-400 mb-2 block"></i>
                                    Nenhum usuário de teste encontrado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Admin sem participação em treinamentos Tab -->
    <div id="admin-sem-participacao" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-200">
                <h2 class="text-xl font-bold text-yellow-900">
                    <i class="fas fa-user-slash mr-2"></i>Admins sem Participação em Treinamentos
                </h2>
                <p class="text-yellow-700 text-sm mt-1">{{ count($adminsSemParticipacaoTreinamentos) }} admin(s) marcado(s) para NÃO participar de treinamentos - EXCLUÍDO(S) dos KPIs</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Perfil</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Participa Treinamentos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adminsSemParticipacaoTreinamentos as $usuario)
                            <tr class="border-b hover:bg-yellow-50 transition">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $usuario->nome }}</td>
                                <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-gray-800 font-semibold">Admin</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-900">
                                        Não
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-600">
                                    <i class="fas fa-check-circle text-3xl text-green-400 mb-2 block"></i>
                                    Nenhum admin sem participação em treinamentos
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Usuários em Férias Tab -->
    <div id="ferias" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                <h2 class="text-xl font-bold text-blue-900">
                    <i class="fas fa-umbrella-beach mr-2"></i>Usuários em Férias
                </h2>
                <p class="text-blue-700 text-sm mt-1">{{ count($usuariosEmFerias) }} usuário(s) em férias NO MOMENTO - EXCLUÍDO(S) dos KPIs enquanto em período de férias</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Início</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fim</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Dias Restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosEmFerias as $usuario)
                            <tr class="border-b hover:bg-blue-50 transition">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $usuario->nome }}</td>
                                <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-gray-800 font-semibold">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-900 rounded text-sm">
                                        {{ $usuario->ferias_inicio?->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-800 font-semibold">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-900 rounded text-sm">
                                        {{ $usuario->ferias_fim?->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($usuario->ferias_fim)
                                        @php
                                            $diasRestantes = now()->diffInDays($usuario->ferias_fim, false);
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                                            {{ $diasRestantes > 7 ? 'bg-yellow-100 text-yellow-900' : 'bg-orange-100 text-orange-900' }}
                                        ">
                                            {{ $diasRestantes > 0 ? $diasRestantes . ' dias' : 'Voltando hoje' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-600">
                                    <i class="fas fa-check-circle text-3xl text-green-400 mb-2 block"></i>
                                    Nenhum usuário em férias no momento
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    <!-- Usuários Inativos Tab -->
    <div id="inativos" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-red-50 px-6 py-4 border-b border-red-200">
                <h2 class="text-xl font-bold text-red-900">
                    <i class="fas fa-user-slash mr-2"></i>Usuários Inativos
                </h2>
                <p class="text-red-700 text-sm mt-1">{{ count($usuariosInativos) }} usuário(s) com status inativo - BLOQUEADO(S) do acesso ao sistema e desconsiderado(s) dos treinamentos a partir da data de inativação.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Data de Inativação</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuariosInativos as $usuario)
                            <tr class="border-b hover:bg-red-50 transition">
                                <td class="px-6 py-4 text-gray-800 font-semibold">{{ $usuario->nome }}</td>
                                <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4">
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
                                </td>
                                <td class="px-6 py-4">
                                    @if($usuario->data_inativacao)
                                        <span class="px-2 py-1 bg-red-100 text-red-900 rounded text-sm font-semibold">
                                            {{ $usuario->data_inativacao->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-900">Inativo</span>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="text-orange-600 hover:text-orange-900 text-sm">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </a>
                                    <a href="{{ route('usuarios.show', $usuario) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                                        <i class="fas fa-eye mr-1"></i>Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-600">
                                    <i class="fas fa-check-circle text-3xl text-green-400 mb-2 block"></i>
                                    Nenhum usuário inativo
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-btn {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .tab-btn:hover {
        color: #ff8800;
    }
    
    .tab-btn.active {
        color: #ff8800;
    }
    
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .tab-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>

<script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked button
        event.target.closest('.tab-btn').classList.add('active');
    }
    
    // Show first tab on load
    document.querySelectorAll('.tab-content')[0].classList.add('active');
</script>
@endsection
