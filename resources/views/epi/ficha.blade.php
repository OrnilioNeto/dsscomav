<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de EPI - {{ $colaborador->ss_c_tx_nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 11pt; }
            .print-border { border: 1px solid #000 !important; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-gray-100 py-8 px-4 font-sans text-gray-900">

    <!-- Botões de Ação na Tela -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('epi.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-bold text-sm shadow">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para o Módulo
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg font-bold text-sm shadow flex items-center">
            <i class="fas fa-print mr-2 text-amber-300"></i> Imprimir Ficha Individual (PDF)
        </button>
    </div>

    <!-- Documento da Ficha Individual -->
    <div class="max-w-4xl mx-auto bg-white p-8 border border-gray-300 shadow-xl rounded-xl print-border">

        <!-- Cabeçalho Oficial -->
        <div class="flex items-center justify-between border-b-2 border-emerald-900 pb-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-emerald-900 text-amber-400 rounded-lg flex items-center justify-center text-2xl font-bold">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-emerald-950 uppercase tracking-wide">Plataforma DSS - Saúde e Segurança</h1>
                    <p class="text-xs text-gray-500 font-semibold">Gestão de Equipamentos de Proteção Individual (NR-06)</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-900 border border-emerald-200 text-xs font-bold rounded">
                    FICHA INDIVIDUAL DE EPI
                </span>
                <p class="text-xs text-gray-400 mt-1">Emissão: {{ date('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Dados do Colaborador -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Identificação do Colaborador</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-xs text-gray-500 block">Nome do Funcionário:</span>
                    <strong class="text-gray-900 font-bold">{{ $colaborador->ss_c_tx_nome }}</strong>
                </div>
                <div>
                    <span class="text-xs text-gray-500 block">Matrícula:</span>
                    <strong class="text-gray-900 font-bold">{{ $colaborador->ss_c_tx_matricula ?? 'S/N' }}</strong>
                </div>
                <div>
                    <span class="text-xs text-gray-500 block">CPF:</span>
                    <strong class="text-gray-900 font-bold">{{ $colaborador->ss_c_tx_cpf ?? 'N/D' }}</strong>
                </div>
                <div>
                    <span class="text-xs text-gray-500 block">Cargo / Função:</span>
                    <strong class="text-gray-900 font-bold">{{ $colaborador->ss_c_tx_cargo ?? 'Funcionário' }}</strong>
                </div>
            </div>
        </div>

        <!-- Termo de Responsabilidade NR-06 -->
        <div class="p-3 bg-amber-50 border border-amber-200 rounded text-xs text-amber-950 mb-6 text-justify leading-relaxed">
            <strong>DECLARAÇÃO E TERMO DE RESPONSABILIDADE (NR-06 MTE):</strong> Declaramos para os devidos fins que o colaborador acima recebeu gratuitamente os Equipamentos de Proteção Individual (EPIs) relacionados abaixo, adequados ao risco e em perfeito estado de conservação e funcionamento, obrigando-se a usá-los durante a jornada de trabalho, responsabilizando-se pela sua guarda e conservação e comunicando qualquer alteração que o torne impróprio para uso.
        </div>

        <!-- Tabela de EPIs Entregues -->
        <h3 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wider flex items-center">
            <i class="fas fa-boxes text-emerald-800 mr-2"></i> Relação de EPIs Fornecidos
        </h3>

        <div class="overflow-x-auto mb-8 border border-gray-300 rounded-lg">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-300">
                        <th class="p-2.5 font-bold text-gray-700 uppercase border-r border-gray-300">Data</th>
                        <th class="p-2.5 font-bold text-gray-700 uppercase border-r border-gray-300">Item / Variação / Descrição</th>
                        <th class="p-2.5 font-bold text-gray-700 uppercase text-center border-r border-gray-300">Nº CA</th>
                        <th class="p-2.5 font-bold text-gray-700 uppercase text-center border-r border-gray-300">Qtd</th>
                        <th class="p-2.5 font-bold text-gray-700 uppercase text-center border-r border-gray-300">Validade do CA</th>
                        <th class="p-2.5 font-bold text-gray-700 uppercase text-center">Assinatura / Visto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-300">
                    @forelse($entregas as $entrega)
                        <tr class="hover:bg-gray-50">
                            <td class="p-2.5 font-semibold text-gray-800 border-r border-gray-300 whitespace-nowrap">
                                {{ date('d/m/Y', strtotime($entrega->ss_e_tx_data_entrega)) }}
                            </td>
                            <td class="p-2.5 border-r border-gray-300">
                                <div class="font-bold text-gray-900">
                                    {{ $entrega->epi->ss_e_tx_item ?? 'EPI N/D' }}
                                    @if(!empty($entrega->ss_e_tx_retroativo))
                                        <span class="ml-1 text-[9px] text-purple-800 bg-purple-100 px-1.5 py-0.5 rounded font-bold">Retroativo</span>
                                    @endif
                                    @if($entrega->ss_e_tx_status === 'devolvido')
                                        <span class="ml-1 text-[9px] text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded font-bold" title="{{ $entrega->ss_e_tx_justificativa_exclusao ?? '' }}">Devolvido</span>
                                    @endif
                                </div>
                                @if($entrega->ss_e_nb_variacao_id && $entrega->variacao)
                                    <div class="text-amber-700 font-semibold text-[10px]">{{ $entrega->variacao->ss_ev_tx_nome }}</div>
                                @endif
                                <div class="text-gray-500 text-[10px]">{{ $entrega->epi->ss_e_tx_grupo ?? '' }}</div>
                            </td>
                            <td class="p-2.5 text-center font-bold text-gray-800 border-r border-gray-300 whitespace-nowrap">
                                {{ $entrega->epi->ss_e_tx_ca ?? '-' }}
                            </td>
                            <td class="p-2.5 text-center font-bold text-gray-900 border-r border-gray-300">
                                {{ $entrega->ss_e_nb_quantidade }}
                            </td>
                            <td class="p-2.5 text-center text-gray-700 border-r border-gray-300 whitespace-nowrap">
                                @if(!empty($entrega->epi->ss_e_tx_validade_ca))
                                    {{ date('d/m/Y', strtotime($entrega->epi->ss_e_tx_validade_ca)) }}
                                @else
                                    <span class="text-gray-400">Não informada</span>
                                @endif
                            </td>
                            <td class="p-2.5 text-center">
                                @if($entrega->ss_e_tx_assinatura)
                                    <img src="{{ $entrega->ss_e_tx_assinatura }}" alt="Assinatura" class="h-8 max-w-[120px] mx-auto object-contain">
                                @elseif($entrega->ss_e_tx_foto)
                                    <span class="text-[10px] text-blue-700 font-bold border border-blue-200 bg-blue-50 px-2 py-0.5 rounded">Comprovante Físico</span>
                                @else
                                    <span class="text-gray-400 font-italic">Sem Visto</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400 italic">Nenhum registro de entrega de EPI ativo encontrado para este colaborador.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Seção de Assinaturas Finais -->
        <div class="mt-12 pt-8 border-t border-gray-300 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 mb-2 w-3/4 mx-auto"></div>
                <strong class="block text-gray-900 font-bold">{{ $colaborador->ss_c_tx_nome }}</strong>
                <span class="text-gray-500">Assinatura do Colaborador</span>
            </div>
            <div>
                <div class="border-b border-gray-400 mb-2 w-3/4 mx-auto"></div>
                <strong class="block text-gray-900 font-bold">Segurança do Trabalho / RH</strong>
                <span class="text-gray-500">Visto da Empresa</span>
            </div>
        </div>

    </div>

</body>
</html>
