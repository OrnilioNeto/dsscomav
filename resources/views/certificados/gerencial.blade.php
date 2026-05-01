@extends('layout')

@section('title', 'Gerenciamento de Certificados')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-certificate text-orange-600 mr-3"></i>Gerenciamento de Certificados
    </h1>

    <!-- Estatísticas Rápidas -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Total de Certificados</p>
            <p class="text-3xl font-bold text-orange-600">{{ $totalCertificados }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <p class="text-gray-600 text-sm">Certificados Válidos</p>
            <p class="text-3xl font-bold text-green-600">{{ $certificadosValidos }}</p>
        </div>
    </div>

    <!-- Filtros Avançados -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-filter text-gray-700 mr-2"></i>Filtros Avançados
        </h2>

        <form method="GET" action="{{ route('certificados.gerencial') }}" class="space-y-4">
            <div class="grid md:grid-cols-4 gap-4">
                <!-- Tipo de usuário e Nome dinâmico -->
                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                    <select name="tipo_usuario" id="tipo_usuario" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-1">Usuário (nome)</label>
                    <select name="usuario_id" id="usuario_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        @foreach($users as $usuario)
                            <option value="{{ $usuario->id }}" @if(request('usuario_id') == $usuario->id) selected @endif>
                                {{ $usuario->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- CPF -->
                <div>
                    <label for="cpf" class="block text-sm font-semibold text-gray-700 mb-1">CPF</label>
                    <input type="text" name="cpf" id="cpf" 
                        value="{{ request('cpf') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="CPF">
                </div>

                <!-- Treinamento -->
                <div>
                    <label for="training_id" class="block text-sm font-semibold text-gray-700 mb-1">Treinamento</label>
                    <select name="training_id" id="training_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        @foreach($treinamentos as $training)
                            <option value="{{ $training->id }}" 
                                @if(request('training_id') == $training->id) selected @endif>
                                {{ $training->titulo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tipo do Treinamento (DSS / Treinamento) -->
                <div>
                    <label for="training_tipo" class="block text-sm font-semibold text-gray-700 mb-1">Tipo Treinamento</label>
                    <select name="training_tipo" id="training_tipo"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        <option value="dss" @if(request('training_tipo') === 'dss') selected @endif>DSS</option>
                        <option value="treinamento" @if(request('training_tipo') === 'treinamento') selected @endif>Treinamento</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="valido" class="block text-sm font-semibold text-gray-700 mb-1">Validade</label>
                    <select name="valido" id="valido" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        <option value="1" @if(request('valido') === '1') selected @endif>Válido</option>
                        <option value="0" @if(request('valido') === '0') selected @endif>Inválido</option>
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-4 gap-4">
                <!-- Data de Emissão (início) -->
                <div>
                    <label for="data_emissao_inicio" class="block text-sm font-semibold text-gray-700 mb-1">Data Emissão (De)</label>
                    <input type="date" name="data_emissao_inicio" id="data_emissao_inicio" 
                        value="{{ request('data_emissao_inicio') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <!-- Data de Emissão (fim) -->
                <div>
                    <label for="data_emissao_fim" class="block text-sm font-semibold text-gray-700 mb-1">Data Emissão (Até)</label>
                    <input type="date" name="data_emissao_fim" id="data_emissao_fim" 
                        value="{{ request('data_emissao_fim') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <!-- Data de Conclusão (início) -->
                <div>
                    <label for="data_conclusao_inicio" class="block text-sm font-semibold text-gray-700 mb-1">Data Conclusão (De)</label>
                    <input type="date" name="data_conclusao_inicio" id="data_conclusao_inicio" 
                        value="{{ request('data_conclusao_inicio') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <!-- Data de Conclusão (fim) -->
                <div>
                    <label for="data_conclusao_fim" class="block text-sm font-semibold text-gray-700 mb-1">Data Conclusão (Até)</label>
                    <input type="date" name="data_conclusao_fim" id="data_conclusao_fim" 
                        value="{{ request('data_conclusao_fim') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <!-- Ordenação -->
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="ordenar" class="block text-sm font-semibold text-gray-700 mb-1">Ordenar Por</label>
                    <select name="ordenar" id="ordenar" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="recente" @if(request('ordenar') === 'recente' || !request('ordenar')) selected @endif>Mais Recentes</option>
                        <option value="antigo" @if(request('ordenar') === 'antigo') selected @endif>Mais Antigos</option>
                        <option value="nome_asc" @if(request('ordenar') === 'nome_asc') selected @endif>Nome A-Z</option>
                    </select>
                </div>

                <!-- Botões -->
                <div class="flex gap-2 items-end">
                    <button type="submit" class="flex-1 bg-orange-600 text-white py-2 px-4 rounded-lg hover:bg-orange-700 transition">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="{{ route('certificados.gerencial') }}" class="flex-1 bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                        <i class="fas fa-redo mr-2"></i>Limpar
                    </a>
                    <a href="{{ route('certificados.exportar', request()->query()) }}" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition text-center">
                        <i class="fas fa-download mr-2"></i>Exportar CSV
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($filtrosAtivos)
        <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg mb-4">
            <p class="text-blue-900 text-sm"><i class="fas fa-info-circle mr-2"></i>Filtros ativos: {{ count(array_filter(request()->query())) }} critério(s)</p>
        </div>
    @endif

    <!-- Tabela de Certificados -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-800">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-800">Usuário</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-800">Treinamento</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-800">Data Emissão</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-800">Tempo</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-800">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-800">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificados as $cert)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-sm text-gray-700">{{ $cert->codigo_certificado }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $cert->user->nome }}</div>
                            <div class="text-sm text-gray-600">{{ $cert->user->getCpfFormatted() }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $cert->training->titulo }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $cert->data_emissao->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ gmdate('H:i:s', $cert->tempo_assistido ?? 0) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($cert->valido)
                                <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Válido</span>
                            @else
                                <span class="bg-red-100 text-red-900 px-3 py-1 rounded-full text-sm font-semibold">✗ Inválido</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('certificados.download', $cert->id) }}" 
                                class="text-orange-600 hover:text-orange-900 font-semibold hover:underline">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-600">
                            <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                            <p class="mt-2">Nenhum certificado encontrado</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    @if($certificados->hasPages())
        <div class="mt-6">
            {{ $certificados->links() }}
        </div>
    @endif
</div>
@endsection
