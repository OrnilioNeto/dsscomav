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
                                <option value="{{ $role->id }}">{{ $role->descricao ?: ucfirst(str_replace('_', ' ', $role->nome)) }}</option>
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

            <details class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <summary class="cursor-pointer list-none flex items-center justify-between gap-3 text-amber-900 font-semibold">
                    <span><i class="fas fa-calendar-alt mr-2"></i>Férias e usuário de teste</span>
                    <span class="text-xs bg-amber-100 px-2 py-1 rounded-full">Abrir</span>
                </summary>
                <div class="mt-4 grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Início das férias</label>
                        <input type="date" name="ferias_inicio" value="{{ old('ferias_inicio') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Fim das férias</label>
                        <input type="date" name="ferias_fim" value="{{ old('ferias_fim') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>

                <label class="flex items-center mt-4">
                    <input type="checkbox" name="usuario_teste" value="1" {{ old('usuario_teste') ? 'checked' : '' }} class="mr-2">
                    <span class="text-gray-700 font-semibold">Usuário de teste</span>
                </label>

                <p class="text-sm text-gray-600 mt-2">Contas de teste ficam fora dos relatórios e KPIs. Férias só entram em KPI quando houver atividade no período informado.</p>
            </details>

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

            <!-- Dados de Fardamento (EPIs) -->
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4">
                <h3 class="text-lg font-bold text-emerald-900 flex items-center">
                    <i class="fas fa-tshirt text-emerald-700 mr-2"></i>Dados de Fardamento
                    <span class="ml-2 text-xs font-semibold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Controle de EPIs</span>
                </h3>
                <p class="text-sm text-gray-600 mt-1 mb-4">Informe os tamanhos para auxiliar a gestão de pedidos de fardamento.</p>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tamanho da Camisa</label>
                        <select name="camisa_tamanho" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700 bg-white">
                            <option value="">Não informado</option>
                            @foreach(['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG'] as $tamanho)
                                <option value="{{ $tamanho }}" {{ old('camisa_tamanho') === $tamanho ? 'selected' : '' }}>{{ $tamanho }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tamanho da Calça</label>
                        <select name="calca_tamanho" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700 bg-white">
                            <option value="">Não informado</option>
                            @foreach(['36', '38', '40', '42', '44', '46', '48', '50', '52'] as $tamanho)
                                <option value="{{ $tamanho }}" {{ old('calca_tamanho') === $tamanho ? 'selected' : '' }}>{{ $tamanho }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Numeração da Bota</label>
                        <select name="bota_numero" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700 bg-white">
                            <option value="">Não informado</option>
                            @for($numero = 33; $numero <= 47; $numero++)
                                <option value="{{ $numero }}" {{ old('bota_numero') == $numero ? 'selected' : '' }}>{{ $numero }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>



            <div class="pt-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-3 md:space-y-0">
                    <button type="submit" class="w-full md:flex-1 bg-green-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Criar Usuário
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="w-full md:flex-1 bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('select[name="tipo_usuario"]').addEventListener('change', function() {
    document.getElementById('motorista-fields').classList.add('hidden');
    document.getElementById('funcionario-fields').classList.add('hidden');
    
    if (this.value === 'motorista') {
        document.getElementById('motorista-fields').classList.remove('hidden');
    } else if (this.value === 'funcionario') {
        document.getElementById('funcionario-fields').classList.remove('hidden');
    }
});

// Inicializa ao carregar a página
document.querySelector('select[name="tipo_usuario"]').dispatchEvent(new Event('change'));
</script>
@endsection
