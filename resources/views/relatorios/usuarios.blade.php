@extends('layout')

@section('title', 'Relatório de Usuários')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col gap-2 mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-users text-blue-900 mr-3"></i>Relatório Gerencial de Usuários
        </h1>
        <p class="text-gray-600 max-w-3xl">
            Visão executiva da base de usuários, com volume, status, participação em treinamentos, emissão de certificados e tempo acumulado de assistência.
        </p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
        <p class="text-sm font-semibold text-blue-900 mb-2">Legenda rápida dos filtros</p>
        <p class="text-sm text-blue-900/80 leading-6">
            <strong>Nome</strong> faz busca parcial; <strong>Tipo de usuário</strong> filtra o perfil; <strong>Usuário específico</strong> seleciona um registro exato; <strong>Status</strong> separa ativos e inativos; <strong>CPF</strong> aceita texto formatado ou só números.
            Os números de <strong>Treinamentos</strong> e <strong>Tempo assistido exato</strong> representam histórico registrado, não uma contagem única de pessoas. O tempo segue o mesmo formato do certificado: <strong>HH:MM:SS</strong>.
        </p>
    </div>

    <!-- KPIs -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Total de Usuários</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
            <p class="text-xs text-gray-500 mt-1">Base considerada no filtro atual</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Usuários Ativos</p>
            <p class="text-3xl font-bold text-green-600">{{ $usuariosAtivos }}</p>
            <p class="text-xs text-gray-500 mt-1">Operacionais no sistema</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Com Treinamentos</p>
            <p class="text-3xl font-bold text-purple-900">{{ $usuariosComTreinamentos }}</p>
            <p class="text-xs text-gray-500 mt-1">Usuários com histórico de progresso</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100">
            <p class="text-gray-600 text-sm">Com Certificados</p>
            <p class="text-3xl font-bold text-orange-600">{{ $usuariosComCertificados }}</p>
            <p class="text-xs text-gray-500 mt-1">Usuários que concluíram conteúdos</p>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 mb-8 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Justificativa operacional</p>
                <h2 class="mt-1 text-xl font-black text-amber-950">Usuários em férias agora</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-amber-900/80">
                    Esse bloco mostra quem está oficialmente em férias neste momento. Ele ajuda a justificar a diferença entre o total da base e os KPIs, sem remover esses usuários do cadastro ou do acesso ao sistema.
                </p>
            </div>
            <div class="rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-amber-100 text-center min-w-[180px]">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Em férias</p>
                <p class="mt-2 text-4xl font-black text-amber-900">{{ $usuariosEmFerias }}</p>
            </div>
        </div>

        @if(($usuariosEmFeriasLista ?? collect())->count())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($usuariosEmFeriasLista as $usuarioFerias)
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-200">
                        {{ $usuarioFerias->nome }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-sm text-amber-900/70">Nenhum usuário está em férias no momento.</p>
        @endif
    </div>

    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 md:col-span-2">
            <p class="text-gray-600 text-sm">Tempo assistido exato</p>
            <p class="text-3xl font-bold text-blue-900">{{ $tempoTotalFormatado }}</p>
            <p class="text-xs text-gray-500 mt-1">Somatório de todos os registros de progresso</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 md:col-span-2">
            <p class="text-gray-600 text-sm">Cobertura Operacional</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $totalUsuarios > 0 ? number_format(($usuariosComTreinamentos / $totalUsuarios) * 100, 1, ',', '.') : '0,0' }}%</p>
            <p class="text-xs text-gray-500 mt-1">Percentual de usuários com participação em treinamentos</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8 border border-gray-100">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Filtros</h2>
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="bg-gray-200 text-gray-800 px-3 py-2 rounded-lg hover:bg-gray-300">← Voltar</a>
                <a href="{{ route('dashboard') }}" class="bg-blue-900 text-white px-3 py-2 rounded-lg hover:bg-blue-800">Dashboard</a>
            </div>
        </div>

        <form method="GET" action="{{ route('relatorios.usuarios') }}" class="space-y-4">
            <div class="grid md:grid-cols-4 gap-4">
                <div>
                    <label for="nome" class="block text-sm font-semibold text-gray-700 mb-1">Nome</label>
                    <input type="text" name="nome" id="nome" value="{{ request('nome') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Buscar por nome">
                </div>

                <div>
                    <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Tipo de usuário</label>
                    <select name="tipo_usuario" id="tipo_usuario" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($userTypes as $type)
                            <option value="{{ $type }}" @if(request('tipo_usuario') === $type) selected @endif>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-1">Usuário específico</label>
                    <select name="usuario_id" id="usuario_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($users as $usuario)
                            <option value="{{ $usuario->id }}" @if(request('usuario_id') == $usuario->id) selected @endif>{{ $usuario->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="ativo" @if(request('status') === 'ativo') selected @endif>Ativo</option>
                        <option value="inativo" @if(request('status') === 'inativo') selected @endif>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="cpf" class="block text-sm font-semibold text-gray-700 mb-1">CPF</label>
                    <input type="text" name="cpf" id="cpf" value="{{ request('cpf') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Somente números ou formatado">
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" name="somente_ferias" value="1" {{ request('somente_ferias') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-500">
                        Mostrar somente usuários em férias
                    </label>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-900 text-white py-2 px-4 rounded-lg hover:bg-blue-800 transition">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="{{ route('relatorios.usuarios') }}" class="flex-1 bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                        <i class="fas fa-redo mr-2"></i>Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                        <th class="px-4 py-3 text-center font-semibold">Perfil</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Treinamentos</th>
                        <th class="px-4 py-3 text-center font-semibold">Certificados</th>
                        <th class="px-4 py-3 text-center font-semibold">Tempo Assistido</th>
                        <th class="px-4 py-3 text-center font-semibold">Última Atividade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $usuario->nome }}</div>
                                <div class="text-sm text-gray-600">{{ $usuario->getCpfFormatted() }}</div>
                                <div class="text-xs text-gray-500">{{ $usuario->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $usuario->tipo_usuario ?? 'sem_tipo')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($usuario->status === 'ativo')
                                    <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Ativo</span>
                                @else
                                    <span class="bg-red-100 text-red-900 px-3 py-1 rounded-full text-sm font-semibold">✗ Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $usuario->progress_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $usuario->certificates_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ gmdate('H:i:s', (int) ($usuario->tempo_total_assistido ?? 0)) }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $usuario->ultima_atividade_em ? \Carbon\Carbon::parse($usuario->ultima_atividade_em)->format('d/m/Y H:i') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-600">
                                <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                                <p class="mt-2">Nenhum usuário encontrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginação -->
    @if($usuarios->hasPages())
        <div class="mt-6">
            {{ $usuarios->links() }}
        </div>
    @endif
</div>
@endsection
