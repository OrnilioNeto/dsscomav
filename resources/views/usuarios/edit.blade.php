@extends('layout')

@section('title', 'Editar Usuário')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-user-edit text-blue-900 mr-3"></i>Editar Usuário
    </h1>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nome *</label>
                    <input type="text" name="nome" value="{{ $usuario->nome }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">CPF (somente leitura)</label>
                    <input type="text" value="{{ $usuario->getCpfFormatted() }}" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Email *</label>
                    <input type="email" name="email" value="{{ $usuario->email }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Telefone</label>
                    <input type="tel" name="telefone" value="{{ $usuario->telefone }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="ativo" {{ $usuario->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                        <option value="inativo" {{ $usuario->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo de Usuário *</label>
                    <input type="text" value="{{ ucfirst($usuario->tipo_usuario) }}" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                </button>
                <a href="{{ route('usuarios.show', $usuario) }}" class="flex-1 bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
