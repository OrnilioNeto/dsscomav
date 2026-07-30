@extends('layout')

@section('title')
Dashboard Administrador
@endsection

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

    /* Estilos da moldura de ranking */
    .profile-moldura {
        position: relative;
        padding: 4px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    }
    .tier-mythic  { background: linear-gradient(135deg, #7c3aed, #db2777); box-shadow: 0 0 18px rgba(124,58,237,0.6); }
    .tier-titan   { background: linear-gradient(135deg, #ef4444, #f97316); box-shadow: 0 0 12px rgba(239,68,68,0.5); }
    .tier-imperial{ background: linear-gradient(135deg, #f97316, #facc15); }
    .tier-elite   { background: linear-gradient(135deg, #0ea5e9, #2dd4bf); }
    .tier-silver  { background: linear-gradient(135deg, #64748b, #94a3b8); }
    .tier-bronze  { background: linear-gradient(135deg, #b45309, #d97706); }
</style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">
        <i class="fas fa-tachometer-alt text-blue-900 mr-3"></i>Dashboard Administrativo
    </h1>

    <!-- Perfil e Nível (Ranking Pessoal do Admin) -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 flex flex-col md:flex-row items-center gap-6 border border-gray-100">
        <div class="profile-moldura {{ $rankingLevel['class'] }}">
            <img src="{{ Auth::user()->getFotoPerfilUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-white">
            <div class="absolute -bottom-2 -right-2 bg-white rounded-full p-2 shadow-md">
                <i class="fas {{ $rankingLevel['icon'] }}" style="color: {{ $rankingLevel['color'] }}"></i>
            </div>
        </div>
        <div class="flex-1 text-center md:text-left">
            <h2 class="text-2xl font-black text-gray-900">Olá, {{ explode(' ', Auth::user()->nome)[0] }}!</h2>
            <p class="text-gray-500 font-medium">
                Seu nível: 
                <span class="font-bold" style="color: {{ $rankingLevel['color'] }}">{{ $rankingLevel['name'] }}</span>
                <span class="text-gray-400 font-normal">— {{ $rankingLevel['sub'] }}</span>
            </p>
            <p class="text-sm text-gray-400 mt-1 italic">"{{ $rankingLevel['msg'] }}"</p>
        </div>
        <div class="text-center">
            <div class="flex gap-4 md:gap-8 justify-center mb-1">
                <div>
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Posição</p>
                    <p class="text-4xl font-black text-blue-900">{{ $userRank > 0 ? $userRank . 'º' : '--' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-widest mb-1">Pontos</p>
                    <p class="text-4xl font-black text-blue-900">{{ number_format($totalPoints, 0) }}</p>
                </div>
            </div>
            <a href="{{ route('profile.stats') }}" class="mt-2 inline-block text-sm font-bold text-blue-600 hover:underline">
                Ver detalhamento de pontos <i class="fas fa-arrow-right ml-1 text-xs"></i>
            </a>
        </div>
    </div>

    <!-- Abas de Navegação -->
    <div class="bg-white rounded-lg shadow-lg mb-8 overflow-x-auto">
        <div class="flex border-b-2 border-gray-300">
            <a href="#dashboard" onclick="abrirAba(event, 'dashboard')" class="aba-btn aba-ativa flex-1 px-4 py-3 font-semibold text-center border-b-4 border-blue-900 text-blue-900">
                <i class="fas fa-chart-bar mr-2"></i>Dashboard
            </a>

            @if(auth()->user()->participa_treinamentos)
                <a href="#treinamentos" onclick="abrirAba(event, 'treinamentos')" class="aba-btn flex-1 px-4 py-3 font-semibold text-center text-gray-700 hover:text-blue-900 transition">
                    <i class="fas fa-video mr-2"></i>Meus Treinamentos
                </a>
            @endif

            <a href="#certificados" onclick="abrirAba(event, 'certificados')" class="aba-btn flex-1 px-4 py-3 font-semibold text-center text-gray-700 hover:text-blue-900 transition">
                <i class="fas fa-certificate mr-2"></i>Certificados
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
                        <p class="text-gray-600 text-sm">Usuários</p>
                        <p class="text-3xl font-bold text-blue-900">{{ $totalUsuarios }}</p>
                    </div>
                    <i class="fas fa-users text-5xl text-blue-100"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Ativos</p>
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

        <!-- Seções -->
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Treinamentos Recentes -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-video text-purple-900 mr-2"></i>Treinamentos Recentes
                </h2>
                <div class="space-y-3">
                    @forelse($treinamentosRecentes as $training)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $training->titulo }}</p>
                                <p class="text-sm text-gray-600">{{ $training->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="bg-{{ $training->status === 'ativo' ? 'green' : 'red' }}-100 text-{{ $training->status === 'ativo' ? 'green' : 'red' }}-900 px-3 py-1 rounded-full text-sm">
                                {{ $training->status }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-600">Nenhum treinamento recente</p>
                    @endforelse
                </div>
                <a href="{{ route('treinamentos.index') }}" class="mt-4 text-blue-900 font-semibold hover:text-blue-700">
                    Ver todos <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <!-- Usuários Recentes -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-user-plus text-green-900 mr-2"></i>Usuários Recentes
                </h2>
                <div class="space-y-3">
                    @forelse($usuariosRecentes as $usuario)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $usuario->nome }}</p>
                                <p class="text-sm text-gray-600">{{ ucfirst($usuario->tipo_usuario) }}</p>
                            </div>
                            <span class="bg-{{ $usuario->status === 'ativo' ? 'green' : 'red' }}-100 text-{{ $usuario->status === 'ativo' ? 'green' : 'red' }}-900 px-3 py-1 rounded-full text-sm">
                                {{ ucfirst($usuario->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-600">Nenhum usuário recente</p>
                    @endforelse
                </div>
                <a href="{{ route('usuarios.index') }}" class="mt-4 text-blue-900 font-semibold hover:text-blue-700">
                    Ver todos <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="mt-8 grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @if(auth()->user()->hasPermission('users', 'edit'))
                <a href="{{ route('usuarios.create') }}" class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6 rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-user-plus text-3xl mb-3"></i>
                    <h3 class="text-xl font-bold">Novo Usuário</h3>
                    <p class="text-blue-100 text-sm mt-2">Adicionar um novo usuário ao sistema</p>
                </a>
            @endif

            @if(auth()->user()->hasPermission('trainings', 'edit'))
                <a href="{{ route('treinamentos.create') }}" class="bg-gradient-to-r from-purple-900 to-purple-700 text-white p-6 rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-plus text-3xl mb-3"></i>
                    <h3 class="text-xl font-bold">Novo Treinamento</h3>
                    <p class="text-purple-100 text-sm mt-2">Criar um novo treinamento ou DSS</p>
                </a>
            @endif

            @if(auth()->user()->hasPermission('rankings', 'view'))
                <a href="{{ route('admin.ranking.index') }}" class="bg-gradient-to-r from-amber-700 via-yellow-600 to-amber-500 text-white p-6 rounded-lg hover:shadow-xl transition-all shadow-amber-200 border-2 border-amber-400/30 group">
                    <i class="fas fa-award text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-xl font-bold">Gestão de Engajamento</h3>
                    <p class="text-amber-50 text-sm mt-2 opacity-90">Análise comportamental e BI de prontidão da equipe</p>
                </a>
            @endif

            @if(auth()->user()->hasPermission('splash', 'view'))
                <a href="{{ route('admin.splash.index') }}" class="bg-gradient-to-r from-blue-600 to-blue-500 text-white p-6 rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-bullhorn text-3xl mb-3"></i>
                    <h3 class="text-xl font-bold">Gerenciar Conteúdo Splash</h3>
                    <p class="text-blue-100 text-sm mt-2">Configurar mensagens de boas-vindas e comunicados</p>
                </a>
            @endif
            @if(auth()->user()->hasPermission('epi', 'view'))
                <a href="{{ route('epi.index') }}" class="bg-gradient-to-r from-amber-600 to-amber-500 text-white p-6 rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-hard-hat text-3xl mb-3"></i>
                    <h3 class="text-xl font-bold">Gestão de EPIs</h3>
                    <p class="text-amber-100 text-sm mt-2">Catálogo, estoque, entregas e fichas de EPI</p>
                </a>
            @endif
        </div>
    </div>

    <!-- ABA: MEUS TREINAMENTOS (Só mostrada se participa_treinamentos = true) -->
    @if(auth()->user()->participa_treinamentos)
        <div id="treinamentos" class="aba-conteudo" style="display:none;">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-video text-purple-900 mr-2"></i>Meus Treinamentos
                </h2>

                <div class="grid md:grid-cols-3 gap-4">
                    @forelse($treinamentosDisponíveis ?? [] as $training)
                        <a href="{{ route('treinamentos.player', $training->id) }}" class="bg-gradient-to-br from-purple-50 to-blue-50 p-4 rounded-lg border-2 border-purple-200 hover:shadow-lg transition">
                            <h3 class="font-bold text-gray-800 text-sm mb-2">{{ $training->titulo }}</h3>
                            <p class="text-xs text-gray-600 mb-3">{{ substr($training->descricao, 0, 60) . '...' }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">⏱ {{ $training->carga_horaria }} min</span>
                                <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded">Assistir</span>
                            </div>
                        </a>
                    @empty
                        <div class="col-span-3 text-center p-6">
                            <p class="text-gray-600">Nenhum treinamento disponível no momento</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- ABA: CERTIFICADOS -->
    <div id="certificados" class="aba-conteudo" style="display:none;">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-certificate text-orange-600 mr-2"></i>Gerenciamento de Certificados
            </h2>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('certificados.gerencial') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-search mr-2"></i>Buscar Certificados
                </a>
                <a href="{{ route('certificados.exportar') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i>Exportar Dados
                </a>
            </div>

            <p class="text-gray-600 text-sm">Acesse a página de gerenciamento de certificados para filtros avançados, busca e exportação de dados para auditoria.</p>
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
