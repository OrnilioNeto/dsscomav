@extends('layout')

@section('title', 'Liberar Conteúdo para Reassistir')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-redo text-orange-600 mr-3"></i>Liberar Conteúdo para Reassistir
            </h1>
            <p class="text-gray-600 mt-1">
                Libere treinamentos para que o usuário reassista novamente (comportamento de velocidade indevida).
                <strong>{{ $usuario->nome }}</strong>
                ({{ $usuario->getCpfFormatted() }})
            </p>
        </div>
        <a href="{{ route('usuarios.show', $usuario) }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-orange-900">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Como funciona:</strong> Ao liberar um conteúdo para reassistir, o progresso anterior do usuário será zerado
            e o certificado válido anterior será invalidado. O deverá assistir o conteúdo novamente e passar na avaliação
            para obter um novo certificado. Um registro completo fica salvo para fins de auditoria.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Tipo</th>
                        <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Certificado</th>
                        <th class="px-4 py-3 text-center font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lista as $item)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $item->training->titulo }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                {{ $item->training->tipo ? ucfirst($item->training->tipo) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_progresso)
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-1 max-w-[80px] mx-auto">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->porcentagem }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ $item->porcentagem }}%</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->status === 'concluido')
                                    <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">✓ Concluído</span>
                                @elseif($item->status === 'pendente')
                                    <span class="bg-yellow-100 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold">⧖ Pendente</span>
                                @else
                                    <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm font-semibold">○ Não iniciado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_rewatch && $item->rewatch)
                                    <span class="bg-orange-100 text-orange-900 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas fa-redo mr-1"></i>Reassistir
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Solicitado em {{ $item->rewatch->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 italic">{{ Str::limit($item->rewatch->justificativa, 50) }}</div>
                                @elseif($item->tem_certificado)
                                    <span class="bg-green-100 text-green-900 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas fa-certificate mr-1"></i>Válido
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Emissão: {{ $item->certificate->data_emissao->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_rewatch && $item->rewatch)
                                    <form action="{{ route('usuarios.treinamentos_reassistir.destroy', [$usuario, $item->rewatch]) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta solicitação de reassistir?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-700 px-3 py-1 rounded-lg hover:bg-red-200 transition text-sm font-semibold">
                                            <i class="fas fa-trash mr-1"></i>Remover
                                        </button>
                                    </form>
                                @else
                                    <button onclick="abrirModalReassistir({{ $item->training->id }}, '{{ addslashes($item->training->titulo) }}', {{ $item->tem_certificado ? 'true' : 'false' }})" class="bg-orange-600 text-white px-3 py-1 rounded-lg hover:bg-orange-700 transition text-sm font-semibold">
                                        <i class="fas fa-redo mr-1"></i>Liberar p/ Reassistir
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                <i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i>
                                <p>Nenhum treinamento encontrado para este usuário</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Liberar para Reassistir -->
<div id="modalReassistir" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-redo text-orange-600 mr-2"></i>Liberar para Reassistir
                </h3>
                <button onclick="fecharModalReassistir()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form action="{{ route('usuarios.treinamentos_reassistir.store', $usuario) }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <input type="hidden" name="training_id" id="modal_rewatch_training_id">

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Treinamento:</p>
                    <p class="text-gray-800 font-semibold" id="modal_rewatch_training_nome"></p>
                </div>

                <div id="modal_rewatch_alerta_certificado" class="hidden bg-orange-50 border border-orange-200 rounded-lg p-3">
                    <p class="text-sm text-orange-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atenção:</strong> O certificado válido atual será invalidado e o progresso do usuário será zerado.
                    </p>
                </div>

                <div>
                    <label for="justificativa" class="block text-sm font-semibold text-gray-700 mb-1">Justificativa *</label>
                    <textarea name="justificativa" id="justificativa" rows="4" required maxlength="1000"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="Ex: Usuário assistiu o vídeo em velocidade superior ao permitido (2x). Necessário reassistir para validação."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Campo obrigatório. Esta justificativa ficará registrada para fins de auditoria.</p>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="fecharModalReassistir()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-check mr-2"></i>Confirmar Liberação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalReassistir(trainingId, trainingNome, temCertificado) {
    document.getElementById('modal_rewatch_training_id').value = trainingId;
    document.getElementById('modal_rewatch_training_nome').textContent = trainingNome;
    
    var alertaCertificado = document.getElementById('modal_rewatch_alerta_certificado');
    if (temCertificado) {
        alertaCertificado.classList.remove('hidden');
    } else {
        alertaCertificado.classList.add('hidden');
    }
    
    document.getElementById('modalReassistir').classList.remove('hidden');
}

function fecharModalReassistir() {
    document.getElementById('modalReassistir').classList.add('hidden');
}

document.getElementById('modalReassistir').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalReassistir();
    }
});
</script>
@endsection
