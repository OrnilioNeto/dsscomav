@extends('layout')

@section('title', 'Relatório de Usuários')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-users text-blue-900 mr-3"></i>Relatório de Usuários
    </h1>

    <!-- Estatísticas -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Total de Usuários</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Usuários Ativos</p>
            <p class="text-3xl font-bold text-green-600">{{ $usuariosAtivos }}</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">Filtros</h2>

        <form method="GET" action="{{ route('relatorios.usuarios') }}" class="space-y-4">
            <div class="flex gap-2 mb-4">
                <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-300">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-3 py-2 rounded-lg hover:bg-blue-800">Dashboard</a>
            </div>
            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                    <select name="tipo_usuario" id="tipo_usuario" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-1">Nome</label>
                    <select name="usuario_id" id="usuario_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($users as $usuario)
                            <option value="{{ $usuario->id }}" @if(request('usuario_id') == $usuario->id) selected @endif>
                                {{ $usuario->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="cpf" class="block text-sm font-semibold text-gray-700 mb-1">CPF</label>
                    <input type="text" name="cpf" id="cpf" 
                        value="{{ request('cpf') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="CPF">
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="ativo" @if(request('status') === 'ativo') selected @endif>Ativo</option>
                        <option value="inativo" @if(request('status') === 'inativo') selected @endif>Inativo</option>
                    </select>
                </div>

                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
                    <select name="tipo_usuario" id="tipo_usuario" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="motorista" @if(request('tipo_usuario') === 'motorista') selected @endif>Motorista</option>
                        <option value="funcionario" @if(request('tipo_usuario') === 'funcionario') selected @endif>Funcionário</option>
                        <option value="terceirizado" @if(request('tipo_usuario') === 'terceirizado') selected @endif>Terceirizado</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 md:flex-none bg-blue-900 text-white py-2 px-4 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
                <a href="{{ route('relatorios.usuarios') }}" class="flex-1 md:flex-none bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                    <i class="fas fa-redo mr-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                    <th class="px-4 py-3 text-left font-semibold">Email</th>
                    <th class="px-4 py-3 text-center font-semibold">Tipo</th>
                    <th class="px-4 py-3 text-center font-semibold">Status</th>
                    <th class="px-4 py-3 text-center font-semibold">Treinamentos</th>
                    <th class="px-4 py-3 text-center font-semibold">Certificados</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $usuario->nome }}</div>
                            <div class="text-sm text-gray-600">{{ $usuario->getCpfFormatted() }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $usuario->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm font-semibold">
                                {{ ucfirst(str_replace('_', ' ', $usuario->tipo_usuario)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($usuario->status === 'ativo')
                                <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Ativo</span>
                            @else
                                <span class="bg-red-100 text-red-900 px-3 py-1 rounded-full text-sm font-semibold">✗ Inativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $usuario->progress->count() }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $usuario->certificates->count() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-600">
                            <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                            <p class="mt-2">Nenhum usuário encontrado</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    @if($usuarios->hasPages())
        <div class="mt-6">
            {{ $usuarios->links() }}
        </div>
    @endif
</div>
@endsection
