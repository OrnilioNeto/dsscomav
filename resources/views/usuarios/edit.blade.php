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

            @if(auth()->user()->isSuperAdmin())
                <div class="bg-red-50 p-4 rounded-lg border-2 border-red-200">
                    <h3 class="text-red-900 font-semibold mb-4">
                        <i class="fas fa-lock mr-2"></i>Configurações de Acesso (Super Admin)
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Alterar Senha</label>
                            <input type="password" name="password" placeholder="Deixe em branco para manter a senha atual" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600">
                            <p class="text-xs text-gray-600 mt-1">Mínimo 8 caracteres. Deixe em branco para não alterar.</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Confirmar Senha</label>
                            <input type="password" name="password_confirmation" placeholder="Confirme a nova senha" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600">
                        </div>
                    </div>
                </div>
            @endif

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
                    <input type="hidden" name="tipo_usuario" value="{{ $usuario->tipo_usuario }}">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Empresa</label>
                    <input type="text" name="empresa" value="{{ $usuario->empresa }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Cargo</label>
                    <input type="text" name="cargo" value="{{ $usuario->cargo }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <details class="rounded-lg border border-amber-200 bg-amber-50 p-4" @if($usuario->ferias_inicio || $usuario->usuario_teste) open @endif>
                <summary class="cursor-pointer list-none flex items-center justify-between gap-3 text-amber-900 font-semibold">
                    <span><i class="fas fa-calendar-alt mr-2"></i>Férias e usuário de teste</span>
                    <span class="text-xs bg-amber-100 px-2 py-1 rounded-full">Configurar</span>
                </summary>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($usuario->isOnVacation())
                        <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-xs font-semibold">Em férias</span>
                    @endif
                    @if($usuario->usuario_teste)
                        <span class="bg-purple-100 text-purple-900 px-3 py-1 rounded-full text-xs font-semibold">Usuário de teste</span>
                    @endif
                </div>
                <div class="mt-4 grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Início das férias</label>
                        <input type="date" name="ferias_inicio" value="{{ old('ferias_inicio', optional($usuario->ferias_inicio)->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Fim das férias</label>
                        <input type="date" name="ferias_fim" value="{{ old('ferias_fim', optional($usuario->ferias_fim)->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>

                <label class="flex items-center mt-4">
                    <input type="checkbox" name="usuario_teste" value="1" {{ old('usuario_teste', $usuario->usuario_teste) ? 'checked' : '' }} class="mr-2">
                    <span class="text-gray-700 font-semibold">Usuário de teste</span>
                </label>

                <p class="text-sm text-gray-600 mt-2">Usuários de teste saem dos relatórios e KPIs. Férias sem atividade no período também não entram nos números.</p>
            </details>

            @if($usuario->isAdmin())
                <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="participa_treinamentos" value="1" 
                            @if($usuario->participa_treinamentos) checked @endif
                            class="w-5 h-5 text-blue-900 rounded focus:ring-2 focus:ring-blue-900">
                        <span class="ml-3 text-gray-700 font-semibold">
                            <i class="fas fa-video text-purple-600 mr-2"></i>Este usuário também participa dos DSS (Treinamentos)
                        </span>
                    </label>
                    <p class="text-sm text-gray-600 mt-2 ml-8">Se marcado, este administrador terá acesso à abas de treinamentos e poderá visualizar certificados como participante.</p>
                </div>
            @endif

            <div class="pt-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-3 md:space-y-0">
                    <button type="submit" class="w-full md:flex-1 bg-green-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                    <a href="{{ route('usuarios.show', $usuario) }}" class="w-full md:flex-1 bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
