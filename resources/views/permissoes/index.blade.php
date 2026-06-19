@extends('layout')

@section('title', 'Gerenciar Perfis e Permissões')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-user-shield text-blue-900 mr-3"></i>Controle de Acesso (Perfis & Permissões)
        </h1>
        <a href="{{ route('dashboard') }}" class="bg-gray-500 text-white px-5 py-2 rounded-lg hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <p class="font-bold">Sucesso!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
            <p class="font-bold">Erro!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-4 gap-8">
        
        <!-- Formulário para Criar Novo Perfil (Role) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 border-t-4 border-blue-900">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-plus-circle text-blue-900 mr-2"></i>Novo Perfil
                </h2>
                
                <form action="{{ route('admin.permissoes.storeRole') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="display_name" class="block text-sm font-semibold text-gray-700 mb-1">Nome do Perfil *</label>
                        <input 
                            type="text" 
                            id="display_name" 
                            name="display_name" 
                            required 
                            placeholder="Ex: Auditor, Coordenador" 
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                    </div>

                    <div>
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-1">Descrição (Opcional)</label>
                        <textarea 
                            id="descricao" 
                            name="descricao" 
                            rows="3" 
                            placeholder="Descreva as funções deste perfil..." 
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                        ></textarea>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>Criar Perfil
                    </button>
                </form>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mt-6 text-sm text-gray-600 space-y-2">
                <h3 class="font-bold text-gray-800 flex items-center">
                    <i class="fas fa-info-circle text-blue-900 mr-2"></i>Regras de Segurança
                </h3>
                <p>1. O perfil <strong>Super Admin</strong> possui acesso irrestrito garantido pelo sistema e não é listado aqui por segurança.</p>
                <p>2. Os perfis <strong>Admin</strong> e <strong>Usuário</strong> são padrões de sistema e não podem ser excluídos.</p>
                <p>3. Um perfil só pode ser excluído se não houver **nenhum usuário** vinculado a ele.</p>
            </div>
        </div>

        <!-- Matriz de Permissões -->
        <div class="lg:col-span-3">
            <form action="{{ route('admin.permissoes.update') }}" method="POST">
                @csrf
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-6">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Matriz de Acessos</h2>
                            <p class="text-xs text-gray-500 mt-1">Configure o nível de acesso (Visualizar / Editar) de cada perfil por módulo.</p>
                        </div>
                        <button type="submit" class="bg-blue-900 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-800 transition flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i>Salvar Alterações
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 text-sm font-bold text-gray-700 w-1/4">Perfil</th>
                                    <th class="p-4 text-sm font-bold text-gray-700 w-1/2">Módulos & Permissões</th>
                                    <th class="p-4 text-sm font-bold text-gray-700 text-center w-1/4">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($roles as $role)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <!-- Nome do Perfil -->
                                        <td class="p-4 align-top">
                                            <div class="font-bold text-gray-800 text-base">{{ $role->descricao }}</div>
                                            <span class="text-xs font-mono text-gray-400">slug: {{ $role->nome }}</span>
                                            
                                            <!-- Contador de Usuários Vinculados -->
                                            <div class="mt-2 text-xs text-gray-500">
                                                <i class="fas fa-users mr-1"></i>{{ $role->users()->count() }} usuários vinculados
                                            </div>
                                        </td>
                                        
                                        <!-- Módulos de Acesso -->
                                        <td class="p-4">
                                            <div class="space-y-4">
                                                @foreach($modules as $slug => $label)
                                                    @php
                                                        $perm = $role->permissions->firstWhere('module', $slug);
                                                        $canView = $perm ? $perm->can_view : false;
                                                        $canEdit = $perm ? $perm->can_edit : false;
                                                    @endphp
                                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-2 last:border-0 last:pb-0">
                                                        <span class="text-sm font-semibold text-gray-700 mb-1 sm:mb-0">{{ $label }}</span>
                                                        <div class="flex items-center space-x-6">
                                                            <!-- Checkbox Visualizar -->
                                                            <label class="inline-flex items-center space-x-2 text-xs text-gray-600 cursor-pointer">
                                                                <input 
                                                                    type="checkbox" 
                                                                    name="permissions[{{ $role->id }}][{{ $slug }}][view]" 
                                                                    value="1" 
                                                                    {{ $canView ? 'checked' : '' }}
                                                                    id="view_{{ $role->id }}_{{ $slug }}"
                                                                    class="rounded border-gray-300 text-blue-900 focus:ring-blue-500"
                                                                >
                                                                <span>Visualizar</span>
                                                            </label>

                                                            <!-- Checkbox Editar -->
                                                            <label class="inline-flex items-center space-x-2 text-xs text-gray-600 cursor-pointer">
                                                                <input 
                                                                    type="checkbox" 
                                                                    name="permissions[{{ $role->id }}][{{ $slug }}][edit]" 
                                                                    value="1" 
                                                                    {{ $canEdit ? 'checked' : '' }}
                                                                    id="edit_{{ $role->id }}_{{ $slug }}"
                                                                    onchange="toggleViewCheckbox('{{ $role->id }}', '{{ $slug }}')"
                                                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                                                >
                                                                <span>Editar / Gravar</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        
                                        <!-- Ações de Exclusão -->
                                        <td class="p-4 align-top text-center">
                                            @if(in_array($role->nome, ['admin', 'usuario']))
                                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500" title="Perfil protegido pelo sistema">
                                                    <i class="fas fa-lock mr-1"></i>Protegido
                                                </span>
                                            @else
                                                <button 
                                                    type="button" 
                                                    onclick="confirmDeleteRole('{{ route('admin.permissoes.destroyRole', $role->id) }}', '{{ $role->descricao }}', {{ $role->users()->count() }})" 
                                                    class="text-red-600 hover:text-red-900 p-2 transition"
                                                >
                                                    <i class="fas fa-trash-alt mr-1"></i>Excluir
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-900 text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-800 transition flex items-center shadow-lg">
                        <i class="fas fa-sync-alt mr-2"></i>Salvar Todas as Permissões
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal / Form invisível para Exclusão de Perfil -->
<form id="delete-role-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    function toggleViewCheckbox(roleId, slug) {
        const editCheckbox = document.getElementById(`edit_${roleId}_${slug}`);
        const viewCheckbox = document.getElementById(`view_${roleId}_${slug}`);
        
        // Se selecionar "Editar", obrigatoriamente deve habilitar "Visualizar"
        if (editCheckbox && editCheckbox.checked && viewCheckbox) {
            viewCheckbox.checked = true;
        }
    }

    function confirmDeleteRole(url, name, userCount) {
        if (userCount > 0) {
            alert(`Não é possível excluir o perfil "${name}" porque existem ${userCount} usuários vinculados a ele. Altere o perfil desses usuários antes de prosseguir.`);
            return;
        }

        if (confirm(`Tem certeza de que deseja excluir o perfil "${name}"? Todas as suas permissões associadas serão apagadas.`)) {
            const form = document.getElementById('delete-role-form');
            form.action = url;
            form.submit();
        }
    }
</script>
@endsection
