@extends('layout')

@section('title', 'Gestão de Assinaturas - EPI')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
        <h1 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-file-signature text-emerald-700 mr-2"></i> Gestão de Assinaturas de EPI
            <a href="{{ route('epi.index') }}" class="ml-auto text-sm text-emerald-700 hover:text-emerald-900 font-bold">
                <i class="fas fa-arrow-left mr-1"></i> Voltar ao Módulo
            </a>
        </h1>

        <!-- Pendentes -->
        <div class="mb-8">
            <h2 class="text-sm font-bold text-amber-800 uppercase mb-3 flex items-center">
                <i class="fas fa-clock mr-2"></i> Pendentes ({{ $pendentes->count() }})
            </h2>
            @if($pendentes->isEmpty())
                <p class="text-sm text-gray-400">Nenhuma assinatura pendente.</p>
            @else
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-xs divide-y">
                        <thead class="bg-amber-50"><tr>
                            <th class="px-3 py-2 text-left font-bold text-amber-800">Data</th>
                            <th class="px-3 py-2 text-left font-bold text-amber-800">Colaborador</th>
                            <th class="px-3 py-2 text-left font-bold text-amber-800">EPI</th>
                            <th class="px-3 py-2 text-center font-bold text-amber-800">Qtd</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            @foreach($pendentes as $ent)
                                <tr><td class="px-3 py-2">{{ date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $ent->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                    <td class="px-3 py-2">{{ $ent->epi->ss_e_tx_item ?? 'N/D' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $ent->ss_e_nb_quantidade }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Assinadas -->
        <div class="mb-8">
            <h2 class="text-sm font-bold text-emerald-800 uppercase mb-3 flex items-center">
                <i class="fas fa-check-circle mr-2"></i> Assinadas ({{ $assinadas->count() }})
            </h2>
            @if($assinadas->isEmpty())
                <p class="text-sm text-gray-400">Nenhuma assinatura realizada.</p>
            @else
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-xs divide-y">
                        <thead class="bg-emerald-50"><tr>
                            <th class="px-3 py-2 text-left font-bold text-emerald-800">Data</th>
                            <th class="px-3 py-2 text-left font-bold text-emerald-800">Colaborador</th>
                            <th class="px-3 py-2 text-left font-bold text-emerald-800">EPI</th>
                            <th class="px-3 py-2 text-center font-bold text-emerald-800">Qtd</th>
                            <th class="px-3 py-2 text-center font-bold text-emerald-800">Assinatura</th>
                            <th class="px-3 py-2 text-center font-bold text-emerald-800">Data Ass.</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            @foreach($assinadas as $ent)
                                <tr><td class="px-3 py-2">{{ date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $ent->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                    <td class="px-3 py-2">{{ $ent->epi->ss_e_tx_item ?? 'N/D' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $ent->ss_e_nb_quantidade }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($ent->ss_e_tx_assinatura)
                                            <button onclick="verAssinatura('{{ $ent->ss_e_tx_assinatura }}')" class="text-emerald-600 font-bold hover:underline"><i class="fas fa-signature"></i> Ver</button>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $ent->ss_e_tx_data_assinatura ? date('d/m/Y H:i', strtotime($ent->ss_e_tx_data_assinatura)) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Negadas -->
        <div>
            <h2 class="text-sm font-bold text-rose-800 uppercase mb-3 flex items-center">
                <i class="fas fa-times-circle mr-2"></i> Recusadas / Negadas ({{ $negadas->count() }})
            </h2>
            @if($negadas->isEmpty())
                <p class="text-sm text-gray-400">Nenhuma assinatura recusada.</p>
            @else
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-xs divide-y">
                        <thead class="bg-rose-50"><tr>
                            <th class="px-3 py-2 text-left font-bold text-rose-800">Data</th>
                            <th class="px-3 py-2 text-left font-bold text-rose-800">Colaborador</th>
                            <th class="px-3 py-2 text-left font-bold text-rose-800">EPI</th>
                            <th class="px-3 py-2 text-center font-bold text-rose-800">Qtd</th>
                            <th class="px-3 py-2 text-left font-bold text-rose-800">Justificativa</th>
                            <th class="px-3 py-2 text-center font-bold text-rose-800">Ações</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            @foreach($negadas as $ent)
                                <tr><td class="px-3 py-2">{{ date('d/m/Y', strtotime($ent->ss_e_tx_data_entrega)) }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $ent->colaborador->ss_c_tx_nome ?? 'N/D' }}</td>
                                    <td class="px-3 py-2">{{ $ent->epi->ss_e_tx_item ?? 'N/D' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $ent->ss_e_nb_quantidade }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-600 max-w-xs">{{ $ent->ss_e_tx_justificativa_negacao ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <form method="POST" action="{{ route('epi.gestao-assinaturas.alterar', $ent->ss_e_nb_id) }}" class="inline" onsubmit="return confirm('Reenviar esta entrega para assinatura do colaborador?')">
                                            @csrf
                                            <input type="hidden" name="action" value="reativar">
                                            <button class="text-emerald-600 hover:text-emerald-800 font-bold text-xs mr-2" title="Reenviar para assinatura"><i class="fas fa-redo"></i> Reenviar</button>
                                        </form>
                                        <form method="POST" action="{{ route('epi.gestao-assinaturas.alterar', $ent->ss_e_nb_id) }}" class="inline" onsubmit="var j=prompt('Justificativa (opcional):');if(j!=null){this.querySelector('input[name=justificativa]').value=j;return true}return false;">
                                            @csrf
                                            <input type="hidden" name="action" value="cancelar">
                                            <input type="hidden" name="ss_e_tx_justificativa_exclusao" value="">
                                            <button class="text-rose-600 hover:text-rose-800 font-bold text-xs" title="Cancelar entrega"><i class="fas fa-ban"></i> Cancelar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function verAssinatura(base64) {
        Swal.fire({ title: 'Assinatura Digital', imageUrl: base64, imageAlt: 'Assinatura', confirmButtonText: 'Fechar' });
    }
</script>
@endsection
