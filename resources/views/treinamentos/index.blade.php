@extends('layout')

@section('title', 'Gerenciar Treinamentos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-video text-blue-900 mr-3"></i>Treinamentos
        </h1>
        <a href="{{ route('treinamentos.create') }}" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-plus mr-2"></i>Novo Treinamento
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-lg shadow mb-6 flex gap-4">
        <input type="text" placeholder="Pesquisar..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" id="search">
        <select class="px-4 py-2 border border-gray-300 rounded-lg" id="filter-tipo">
            <option value="">Todos os tipos</option>
            <option value="dss">DSS</option>
            <option value="treinamento">Treinamento</option>
        </select>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Título</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Carga Horária</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Publicado</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($treinamentos as $training)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-800 font-medium">{{ $training->titulo }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                {{ $training->tipo === 'dss' ? 'bg-red-100 text-red-900' : 'bg-blue-100 text-blue-900' }}
                            ">
                                {{ strtoupper($training->tipo) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $training->carga_horaria }} min</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                {{ $training->status === 'ativo' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}
                            ">
                                {{ ucfirst($training->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $training->data_publicacao?->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('treinamentos.show', $training) }}" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-eye mr-1"></i>Ver
                            </a>
                            <a href="{{ route('treinamentos.edit', $training) }}" class="text-orange-600 hover:text-orange-900">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </a>
                            <form action="{{ route('treinamentos.destroy', $training) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?')">
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
                            <i class="fas fa-video text-4xl text-gray-300 mb-4 block"></i>
                            Nenhum treinamento encontrado
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="mt-6">
        {{ $treinamentos->links() }}
    </div>
</div>
@endsection
