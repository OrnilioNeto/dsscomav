@extends('layout')

@section('title', 'Gerenciar Usuários')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-users text-blue-900 mr-3"></i>Usuários
        </h1>
        <div class="flex gap-3">
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('usuarios.exclus-kpi') }}" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-exclamation-circle mr-2"></i>Excluídos dos KPIs
                </a>
            @endif
        <a href="{{ route('usuarios.create') }}" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-user-plus mr-2"></i>Novo Usuário
        </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('usuarios.index') }}" class="space-y-4">
            <div>
                <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Pesquisar por nome</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="{{ $nome ?? '' }}"
                    placeholder="Digite o nome do usuário"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <p class="block text-sm font-semibold text-gray-700 mb-2">Filtrar por tipo (selecione 1 ou mais)</p>
                <div class="flex flex-wrap gap-4">
                    @php
                        $tipos = [
                            'motorista' => 'Motorista',
                            'funcionario' => 'Funcionário',
                            'terceirizado' => 'Terceirizado',
                        ];
                    @endphp

                    @foreach($tipos as $valor => $label)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                name="tipos[]"
                                value="{{ $valor }}"
                                {{ in_array($valor, $tiposSelecionados ?? [], true) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-blue-900 text-white px-5 py-2 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-filter mr-2"></i>Aplicar filtros
                </button>
                <a href="{{ route('usuarios.index') }}" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nome</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">CPF</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-800">{{ $usuario->nome }}</td>
                        <td class="px-6 py-4 text-gray-600 font-mono">{{ $usuario->getCpfFormatted() }}</td>
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
                        <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                {{ $usuario->status === 'ativo' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}
                            ">
                                {{ ucfirst($usuario->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('usuarios.show', $usuario) }}" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-eye mr-1"></i>Ver
                            </a>
                            <a href="{{ route('certificados.gerencial', ['cpf' => $usuario->cpf]) }}" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-certificate mr-1"></i>Certificados
                            </a>
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="text-orange-600 hover:text-orange-900">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </a>
                            <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash mr-1"></i>Deletar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-600">
                            <i class="fas fa-users text-4xl text-gray-300 mb-4 block"></i>
                            Nenhum usuário encontrado
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Paginação -->
    <div class="mt-6">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection
