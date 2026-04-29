@extends('layout')

@section('title', 'Novo Usuário')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-user-plus text-blue-900 mr-3"></i>Criar Novo Usuário
    </h1>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="{{ route('usuarios.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nome *</label>
                    <input type="text" name="nome" value="{{ old('nome') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">CPF (apenas números) *</label>
                    <input type="text" name="cpf" value="{{ old('cpf') }}" placeholder="00000000000" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Telefone</label>
                    <input type="tel" name="telefone" value="{{ old('telefone') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo de Usuário *</label>
                    <select name="tipo_usuario" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="motorista">Motorista</option>
                        <option value="funcionario">Funcionário</option>
                        <option value="terceirizado">Terceirizado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Role (Perfil) *</label>
                    <select name="role_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        @foreach($roles as $role)
                            @if($role->nome !== 'super_admin')
                                <option value="{{ $role->id }}">{{ ucfirst(str_replace('_', ' ', $role->nome)) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Senha *</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <!-- Campos específicos para Motorista -->
            <div id="motorista-fields" class="space-y-4 hidden border-t pt-4">
                <h3 class="text-lg font-bold text-gray-800">Dados do Motorista</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">CNH</label>
                        <input type="text" name="cnh" value="{{ old('cnh') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Categoria CNH</label>
                        <input type="text" name="categoria_cnh" value="{{ old('categoria_cnh') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Validade CNH</label>
                    <input type="date" name="validade_cnh" value="{{ old('validade_cnh') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <!-- Campos específicos para Funcionário -->
            <div id="funcionario-fields" class="space-y-4 hidden border-t pt-4">
                <h3 class="text-lg font-bold text-gray-800">Dados do Funcionário</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Setor</label>
                        <input type="text" name="setor" value="{{ old('setor') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Cargo</label>
                        <input type="text" name="cargo" value="{{ old('cargo') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>
            </div>

            <!-- Campos específicos para Terceirizado -->
            <div id="terceirizado-fields" class="space-y-4 hidden border-t pt-4">
                <h3 class="text-lg font-bold text-gray-800">Dados do Terceirizado</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Empresa</label>
                        <input type="text" name="empresa" value="{{ old('empresa') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Responsável</label>
                        <input type="text" name="responsavel" value="{{ old('responsavel') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Criar Usuário
                </button>
                <a href="{{ route('usuarios.index') }}" class="flex-1 bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('select[name="tipo_usuario"]').addEventListener('change', function() {
    document.getElementById('motorista-fields').classList.add('hidden');
    document.getElementById('funcionario-fields').classList.add('hidden');
    document.getElementById('terceirizado-fields').classList.add('hidden');
    
    if (this.value === 'motorista') {
        document.getElementById('motorista-fields').classList.remove('hidden');
    } else if (this.value === 'funcionario') {
        document.getElementById('funcionario-fields').classList.remove('hidden');
    } else if (this.value === 'terceirizado') {
        document.getElementById('terceirizado-fields').classList.remove('hidden');
    }
});

// Inicializa ao carregar a página
document.querySelector('select[name="tipo_usuario"]').dispatchEvent(new Event('change'));
</script>
@endsection
