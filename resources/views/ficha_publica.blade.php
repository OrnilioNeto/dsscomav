<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Conformidade - {{ $usuario->nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #153B2E;
            --primary-dark: #0F2B22;
            --accent: #F28C2B;
        }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .border-primary { border-color: var(--primary); }
        .bg-accent { background-color: var(--accent); }
        .text-accent { color: var(--accent); }
        .border-accent { border-color: var(--accent); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-sans">

    <!-- Top Bar -->
    <header class="bg-primary text-white shadow-lg py-4 px-6">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🚛</span>
                <span class="font-bold text-lg tracking-wider">COMAV TRANSPORTES</span>
            </div>
            <div class="text-xs bg-white/20 px-3 py-1 rounded-full uppercase tracking-wider font-semibold">
                Painel de Conformidade
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-4xl w-full mx-auto px-4 py-8">
        
        <!-- Cartão Principal do Colaborador -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 mb-8">
            <div class="bg-primary p-6 text-white relative">
                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-white/10 border-4 border-white/30 shadow-md">
                        <img src="{{ $usuario->getFotoPerfilUrl() }}" alt="Foto de {{ $usuario->nome }}" class="w-full h-full object-cover">
                    </div>
                    <div class="text-center sm:text-left flex-grow">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $usuario->nome }}</h2>
                            <span class="mt-2 sm:mt-0 px-4 py-1.5 rounded-full text-xs font-black tracking-wider uppercase shadow-sm
                                {{ $usuario->status === 'ativo' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                                <i class="fas {{ $usuario->status === 'ativo' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $usuario->status === 'ativo' ? 'Cadastro Regular' : 'Cadastro Suspenso' }}
                            </span>
                        </div>
                        <p class="text-white/80 text-sm mt-1">
                            Cargo: <strong class="text-white">{{ $usuario->cargo ?? 'Não informado' }}</strong> | 
                            Setor: <strong class="text-white">{{ $usuario->setor ?? 'Não informado' }}</strong>
                        </p>
                        <p class="text-white/85 text-xs mt-2 font-mono">
                            CPF: {{ substr($usuario->cpf, 0, 3) }}.***.***-{{ substr($usuario->cpf, -2) }} | 
                            Empresa: {{ $usuario->empresa ?? 'COMAV Transportes' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- CNH Info (para motoristas) -->
            @if($usuario->tipo_usuario === 'motorista' && $usuario->cnh)
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex flex-wrap gap-6 text-sm text-gray-700">
                    <div>
                        <span class="text-gray-500 font-medium block text-xs">CNH</span>
                        <strong class="font-mono text-gray-900">{{ $usuario->cnh }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium block text-xs">Categoria</span>
                        <strong class="text-gray-900">{{ $usuario->categoria_cnh }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium block text-xs">Validade CNH</span>
                        @if($usuario->validade_cnh && $usuario->validade_cnh->isPast())
                            <strong class="text-red-600 flex items-center">
                                {{ $usuario->validade_cnh->format('d/m/Y') }}
                                <span class="ml-1 text-[10px] uppercase font-bold bg-red-100 px-1.5 py-0.5 rounded">Vencida</span>
                            </strong>
                        @else
                            <strong class="text-emerald-700">{{ $usuario->validade_cnh ? $usuario->validade_cnh->format('d/m/Y') : 'Não cadastrada' }}</strong>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Abas -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
            <!-- Abas Headers -->
            <div class="flex border-b border-gray-200">
                <button data-tab="treinamentos" onclick="switchTab('treinamentos')" class="tab-btn flex-1 py-4 px-2 text-center text-sm font-bold border-b-2 border-accent text-accent focus:outline-none transition">
                    <i class="fas fa-graduation-cap mr-1.5"></i>Treinamentos ({{ $usuario->employeeTrainings->count() + $plataformaTrainings->count() }})
                </button>
                <button data-tab="epis" onclick="switchTab('epis')" class="tab-btn flex-1 py-4 px-2 text-center text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none transition">
                    <i class="fas fa-hard-hat mr-1.5"></i>EPIs ({{ $epiModuloEntregas->count() }})
                </button>
                <button data-tab="dss" onclick="switchTab('dss')" class="tab-btn flex-1 py-4 px-2 text-center text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none transition">
                    <i class="fas fa-video mr-1.5"></i>DSSs ({{ $dssCertificates->count() }})
                </button>
            </div>

            <!-- Abas Contents -->
            <div class="p-6">
                
                <!-- Conteúdo 1: Treinamentos -->
                <div id="tab-treinamentos" class="tab-content space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Capacitações e Normas Regulamentadoras</h3>
                    
                    @if($usuario->employeeTrainings->count() > 0 || $plataformaTrainings->count() > 0)
                        <div class="space-y-3">
                            <!-- Externos -->
                            @foreach($usuario->employeeTrainings as $training)
                                <div class="flex items-center justify-between p-4 rounded-xl border {{ $training->isExpired() ? 'bg-red-50/50 border-red-200' : 'bg-emerald-50/30 border-emerald-100' }}">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-bold text-gray-800 text-sm sm:text-base truncate">{{ $training->nome }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Realizado em: {{ $training->data_treinamento->format('d/m/Y') }} 
                                            @if($training->observacoes)
                                                | {{ $training->observacoes }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <span class="text-xs block text-gray-500">Validade</span>
                                        <strong class="text-xs sm:text-sm text-gray-700">{{ $training->data_validade ? $training->data_validade->format('d/m/Y') : 'Vigente/Vitalício' }}</strong>
                                        <div class="mt-1">
                                            @if($training->isExpired())
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-800 font-bold uppercase">Vencido</span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold uppercase">Válido</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Da plataforma -->
                            @foreach($plataformaTrainings as $cert)
                                <div class="flex items-center justify-between p-4 rounded-xl border bg-emerald-50/30 border-emerald-100">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="text-xs bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded uppercase">Plataforma</span>
                                            <h4 class="font-bold text-gray-800 text-sm sm:text-base truncate">{{ $cert->training->titulo }}</h4>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Código do Certificado: <span class="font-mono">{{ $cert->codigo_certificado }}</span>
                                        </p>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <span class="text-xs block text-gray-500">Concluído</span>
                                        <strong class="text-xs sm:text-sm text-emerald-700">{{ $cert->data_emissao->format('d/m/Y') }}</strong>
                                        <div class="mt-1">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold uppercase">Concluído</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed">
                            <i class="fas fa-graduation-cap text-4xl mb-3"></i>
                            <p>Nenhum treinamento registrado para este funcionário.</p>
                        </div>
                    @endif
                </div>

                <!-- Conteúdo 2: EPIs -->
                <div id="tab-epis" class="tab-content space-y-4 hidden">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Equipamentos de Proteção Individual (EPIs) Entregues</h3>
                    
                    @if($epiModuloEntregas->count() > 0)
                        <div>
                            <p class="text-xs uppercase tracking-wide font-bold text-gray-500 mb-2">Entregas registradas no Módulo de EPI (NR-06)</p>
                            <div class="space-y-3">
                                @foreach($epiModuloEntregas as $entrega)
                                    <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 flex-grow">
                                            <h4 class="font-bold text-gray-800 text-sm sm:text-base">
                                                {{ $entrega->epi->ss_e_tx_item ?? 'EPI' }}
                                                @if($entrega->ss_e_nb_variacao_id && $entrega->variacao)
                                                    <span class="text-xs font-semibold text-amber-700">/ {{ $entrega->variacao->ss_ev_tx_nome }}</span>
                                                @endif
                                            </h4>
                                            @if($entrega->epi->ss_e_tx_grupo)
                                                <p class="text-xs text-gray-500 mt-0.5">{{ $entrega->epi->ss_e_tx_grupo }}</p>
                                            @endif
                                            <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-600 font-medium">
                                                <span>Quant: <strong class="text-gray-900">{{ $entrega->ss_e_nb_quantidade }}</strong></span>
                                                @if(!empty($entrega->epi->ss_e_tx_ca))
                                                    <span>C.A: <strong class="text-gray-900 font-mono">{{ $entrega->epi->ss_e_tx_ca }}</strong></span>
                                                @endif
                                                @if(!empty($entrega->epi->ss_e_tx_validade_ca))
                                                    <span>Validade do CA: <strong class="text-gray-900">{{ date('d/m/Y', strtotime($entrega->epi->ss_e_tx_validade_ca)) }}</strong></span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-3 sm:mt-0 sm:ml-4 text-left sm:text-right">
                                            <span class="text-xs block text-gray-500">Data de Entrega</span>
                                            <strong class="text-xs sm:text-sm text-gray-900">{{ date('d/m/Y', strtotime($entrega->ss_e_tx_data_entrega)) }}</strong>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed">
                            <i class="fas fa-hard-hat text-4xl mb-3"></i>
                            <p>Nenhum registro de entrega de EPI para este funcionário.</p>
                        </div>
                    @endif
                </div>

                <!-- Conteúdo 3: DSS -->
                <div id="tab-dss" class="tab-content space-y-4 hidden">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Diálogos Semanais de Segurança (DSS) Concluídos</h3>
                    
                    @if($dssCertificates->count() > 0)
                        <div class="space-y-3">
                            @foreach($dssCertificates as $cert)
                                <div class="flex items-center justify-between p-4 rounded-xl border border-gray-150 bg-gray-50/30">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-bold text-gray-800 text-sm sm:text-base truncate">{{ $cert->training->titulo }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Certificado: <span class="font-mono text-gray-700">{{ $cert->codigo_certificado }}</span>
                                        </p>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <span class="text-xs block text-gray-500">Assitido em</span>
                                        <strong class="text-xs sm:text-sm text-emerald-700">{{ $cert->data_emissao->format('d/m/Y') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed">
                            <i class="fas fa-video text-4xl mb-3"></i>
                            <p>Nenhum DSS concluído na plataforma por este funcionário.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-200 border-t py-6 px-4 text-center text-xs text-gray-500 mt-auto">
        <div class="max-w-4xl mx-auto space-y-1">
            <p>&copy; 2026 COMAV Transportes. Todos os direitos reservados.</p>
            <p>Ficha de conformidade gerada e validada digitalmente.</p>
        </div>
    </footer>

    <!-- Tab Switching Script -->
    <script>
        function switchTab(tabId) {
            // Esconder todas as abas
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Resetar estilos de todos os botões
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-accent', 'text-accent');
                btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700');
            });
            
            // Mostrar a aba selecionada
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            
            // Ativar estilo do botão clicado
            const activeBtn = document.querySelector(`[data-tab="${tabId}"]`);
            if (activeBtn) {
                activeBtn.classList.add('border-accent', 'text-accent');
                activeBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700');
            }
        }
    </script>
</body>
</html>
