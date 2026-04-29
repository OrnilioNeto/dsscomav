@extends('layout')

@section('title', 'Editar Treinamento')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-edit text-blue-900 mr-3"></i>Editar Treinamento
    </h1>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="{{ route('treinamentos.update', $training) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Título *</label>
                <input type="text" name="titulo" value="{{ $training->titulo }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Descrição</label>
                <textarea name="descricao" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ $training->descricao }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo *</label>
                    <select name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="treinamento" {{ $training->tipo === 'treinamento' ? 'selected' : '' }}>Treinamento</option>
                        <option value="dss" {{ $training->tipo === 'dss' ? 'selected' : '' }}>DSS</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Carga Horária (minutos) *</label>
                    <input type="number" name="carga_horaria" value="{{ $training->carga_horaria }}" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Permitido para *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="motorista" {{ in_array('motorista', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Motoristas</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="funcionario" {{ in_array('funcionario', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Funcionários</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="terceirizado" {{ in_array('terceirizado', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Terceirizados</span>
                    </label>
                </div>
            </div>

            <div class="border-t pt-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-800">Avaliação do Treinamento</h2>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pergunta da avaliação *</label>
                    <textarea name="avaliacao_pergunta" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ $training->avaliacao_pergunta }}</textarea>
                </div>

                @php
                    $assessmentOptions = $training->avaliacao_opcoes ?? [];
                @endphp

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 1 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[0] ?? '' }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 2 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[1] ?? '' }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 3</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[2] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 4</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[3] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Resposta correta *</label>
                    <select name="avaliacao_resposta_correta" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="0" {{ (int) $training->avaliacao_resposta_correta === 0 ? 'selected' : '' }}>Opção 1</option>
                        <option value="1" {{ (int) $training->avaliacao_resposta_correta === 1 ? 'selected' : '' }}>Opção 2</option>
                        <option value="2" {{ (int) $training->avaliacao_resposta_correta === 2 ? 'selected' : '' }}>Opção 3</option>
                        <option value="3" {{ (int) $training->avaliacao_resposta_correta === 3 ? 'selected' : '' }}>Opção 4</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    <option value="ativo" {{ $training->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                    <option value="inativo" {{ $training->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Salvar Alterações
                </button>
                <a href="{{ route('treinamentos.show', $training) }}" class="flex-1 bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
