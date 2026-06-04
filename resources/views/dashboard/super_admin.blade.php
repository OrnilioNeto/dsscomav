@extends('layout')

@section('title', 'Dashboard Super Admin')

@section('extra_css')
<style>
    .aba-btn {
        cursor: pointer;
    }

    .aba-btn:hover {
        background-color: #f3f4f6;
    }

    .aba-ativa {
        border-bottom: 4px solid #1e3a8a;
        color: #1e3a8a;
    }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Dashboard Super Administrador
    </h1>

    <!-- Abas de Navegação -->
    <div class="bg-white rounded-lg shadow-lg mb-8 overflow-x-auto">
        <div class="flex border-b-2 border-gray-300">
            <a href="#dashboard" onclick="abrirAba(event, 'dashboard')" class="aba-btn aba-ativa flex-1 px-4 py-3 font-semibold text-center border-b-4 border-blue-900 text-blue-900">
                <i class="fas fa-chart-bar mr-2"></i>Dashboard
            </a>

            <a href="#relatorios" onclick="abrirAba(event, 'relatorios')" class="aba-btn flex-1 px-4 py-3 font-semibold text-center text-gray-700 hover:text-blue-900 transition">
                <i class="fas fa-chart-pie mr-2"></i>Relatórios
            </a>
        </div>
    </div>

    <!-- ABA: DASHBOARD -->
    <div id="dashboard" class="aba-conteudo">
        <!-- Estatísticas -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total de Usuários</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
                </div>
                <i class="fas fa-users text-5xl text-blue-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Usuários Ativos</p>
                    <p class="text-3xl font-bold text-green-600">{{ $usuariosAtivos }}</p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Treinamentos</p>
                    <p class="text-3xl font-bold text-purple-900">{{ $totalTreinamentos }}</p>
                </div>
                <i class="fas fa-video text-5xl text-purple-100"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificados</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $certificadosEmitidos }}</p>
                </div>
                <i class="fas fa-certificate text-5xl text-orange-100"></i>
            </div>
        </div>
    </div>

    <!-- Gráficos e Relatórios -->
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Usuários por Tipo -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Usuários por Tipo</h2>
            <div class="space-y-3">
                @foreach($usuariosPorTipo as $tipo)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">
                            @if($tipo->tipo_usuario === 'motorista')
                                <i class="fas fa-truck mr-2"></i>Motorista
                            @elseif($tipo->tipo_usuario === 'funcionario')
                                <i class="fas fa-briefcase mr-2"></i>Funcionário
                            @else
                                <i class="fas fa-building mr-2"></i>Terceirizado
                            @endif
                        </span>
                        <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full font-semibold">{{ $tipo->total }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Taxa de Conclusão -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Taxa de Conclusão dos Treinamentos</h2>
            <div class="space-y-4">
                @foreach($treinamentos as $training)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $training->titulo }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ $taxaConclusao[$training->id] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-green-500 h-2 rounded-full transition-all" 
                                style="width: {{ $taxaConclusao[$training->id] ?? 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Links de Ação -->
    <div class="mt-8 grid md:grid-cols-3 gap-6">
        <a href="{{ route('usuarios.index') }}" class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-users text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Gerenciar Usuários</h3>
            <p class="text-blue-100 text-sm mt-2">Adicionar, editar e remover usuários</p>
        </a>

        <a href="{{ route('treinamentos.index') }}" class="bg-gradient-to-r from-purple-900 to-purple-700 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-video text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Gerenciar Treinamentos</h3>
            <p class="text-purple-100 text-sm mt-2">Criar, editar e publicar conteúdo</p>
        </a>

        <a href="{{ route('certificados.gerencial') }}" class="bg-gradient-to-r from-orange-700 to-orange-500 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-certificate text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Consulta de Certificados</h3>
            <p class="text-orange-100 text-sm mt-2">Visualizar certificados e relatórios gerenciais</p>
        </a>
        
        <a href="{{ route('admin.ranking.index') }}" class="bg-gradient-to-r from-amber-700 via-yellow-600 to-amber-500 text-white p-6 rounded-lg hover:shadow-xl transition-all shadow-amber-200 border-2 border-amber-400/30 group">
            <i class="fas fa-award text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
            <h3 class="text-xl font-bold">Gestão de Engajamento (Elite)</h3>
            <p class="text-amber-50 text-sm mt-2 opacity-90">Análise comportamental e BI de prontidão da equipe</p>
        </a>

        <a href="{{ route('relatorios.ia') }}" class="bg-gradient-to-r from-cyan-600 to-blue-500 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-robot text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Análise com IA</h3>
            <p class="text-cyan-100 text-sm mt-2">Resumos executivos e insights preditivos</p>
        </a>

        <a href="{{ route('admin.ranking.settings') }}" class="bg-gradient-to-r from-slate-700 to-slate-500 text-white p-6 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-cog text-3xl mb-3"></i>
            <h3 class="text-xl font-bold">Configurações</h3>
            <p class="text-slate-100 text-sm mt-2">Ajustar pesos e critérios de pontuação</p>
        </a>
    </div>
    </div>

    <!-- ABA: RELATÓRIOS -->
    <div id="relatorios" class="aba-conteudo" style="display:none;">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-chart-pie text-blue-900 mr-2"></i>Relatórios Gerenciais
            </h2>

            <div class="grid md:grid-cols-3 gap-4">
                <a href="{{ route('relatorios.treinamentos') }}" class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border-2 border-blue-300 hover:shadow-lg transition">
                    <i class="fas fa-chart-line text-3xl text-blue-900 mb-3"></i>
                    <h3 class="font-bold text-gray-800 mb-2">Treinamentos</h3>
                    <p class="text-sm text-gray-600">Relatório detalhado sobre assistência e conclusão de treinamentos</p>
                </a>

                <a href="{{ route('relatorios.usuarios') }}" class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border-2 border-green-300 hover:shadow-lg transition">
                    <i class="fas fa-users text-3xl text-green-900 mb-3"></i>
                    <h3 class="font-bold text-gray-800 mb-2">Usuários</h3>
                    <p class="text-sm text-gray-600">Análise de usuários, histórico e participação</p>
                </a>

                <a href="{{ route('relatorios.auditoria') }}" class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border-2 border-purple-300 hover:shadow-lg transition">
                    <i class="fas fa-audit text-3xl text-purple-900 mb-3"></i>
                    <h3 class="font-bold text-gray-800 mb-2">Auditoria</h3>
                    <p class="text-sm text-gray-600">Relatório completo para auditoria e compliance</p>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script>
    function abrirAba(evt, tabName) {
        evt.preventDefault();

        // Esconder todos os conteúdos
        const abaConteudos = document.querySelectorAll('.aba-conteudo');
        abaConteudos.forEach(function(element) {
            element.style.display = 'none';
            element.classList.remove('aba-ativa');
        });

        // Remover classe ativa de todos os botões
        const abaBotoes = document.querySelectorAll('.aba-btn');
        abaBotoes.forEach(function(btn) {
            btn.classList.remove('aba-ativa');
            btn.style.borderBottom = 'none';
            btn.style.color = '#374151';
        });

        // Mostrar a aba clicada
        document.getElementById(tabName).style.display = 'block';

        // Marcar botão como ativo
        evt.currentTarget.classList.add('aba-ativa');
        evt.currentTarget.style.color = '#1e3a8a';
        evt.currentTarget.style.borderBottom = '4px solid #1e3a8a';
    }
</script>
@endsection
