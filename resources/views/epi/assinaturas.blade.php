@extends('layout')

@section('title', 'Assinaturas Pendentes - EPI')

@section('extra_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .signature-container {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background-color: #f8fafc;
        position: relative;
        touch-action: none;
    }
    #signature-canvas {
        width: 100%;
        height: 160px;
        cursor: crosshair;
        background: #ffffff;
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-file-signature text-emerald-700 mr-2"></i> Assinaturas Pendentes
                </h1>
                @if($colaborador)
                    <p class="text-sm text-gray-500 mt-1">{{ $colaborador->ss_c_tx_nome }} - {{ $colaborador->ss_c_tx_cargo ?? 'Funcionário' }}</p>
                @endif
            </div>
            <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-bold">{{ $count }} pendente(s)</span>
        </div>

        @if($grupos->isEmpty())
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-check-circle text-5xl mb-4 text-emerald-300"></i>
                <p class="text-lg font-medium text-gray-500">Nenhuma assinatura pendente no momento.</p>
                <p class="text-sm">Todas as suas entregas de EPI estão em dia!</p>
            </div>
        @else
            @foreach($grupos as $grupoKey => $itens)
                @php $primeiro = $itens->first(); $loopIdx = $loop->index; @endphp
                <div class="border border-gray-200 rounded-xl p-5 mb-4 hover:border-emerald-200 transition">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm">Entrega - {{ date('d/m/Y', strtotime($primeiro->ss_e_tx_data_entrega)) }}</h3>
                            <p class="text-xs text-gray-500">{{ $itens->count() }} item(ns) para assinar</p>
                        </div>
                    </div>

                    <!-- Tabela de itens do grupo -->
                    <div class="overflow-x-auto border border-gray-200 rounded-lg mb-4">
                        <table class="min-w-full text-xs divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-bold text-gray-500">EPI</th>
                                    <th class="px-3 py-2 text-center font-bold text-gray-500">Variação</th>
                                    <th class="px-3 py-2 text-center font-bold text-gray-500">Qtd</th>
                                    <th class="px-3 py-2 text-center font-bold text-gray-500">Vencimento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($itens as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-semibold">{{ $item->epi->ss_e_tx_item ?? 'EPI' }}</td>
                                        <td class="px-3 py-2 text-center">{{ $item->variacao->ss_ev_tx_nome ?? '-' }}</td>
                                        <td class="px-3 py-2 text-center">{{ $item->ss_e_nb_quantidade }}</td>
                                        <td class="px-3 py-2 text-center">{{ $item->ss_e_tx_vencimento ? date('d/m/Y', strtotime($item->ss_e_tx_vencimento)) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="assinatura-grupo-{{ $loopIdx }}">
                        <div class="mb-3">
                            <label class="text-sm font-bold text-gray-700 block mb-1">Sua Assinatura (válida para todos os itens acima):</label>
                            <div class="signature-container bg-white border border-gray-300 rounded-lg shadow-inner">
                                <canvas id="sig-canvas-{{ $loopIdx }}" height="100" class="w-full h-24 rounded-lg cursor-crosshair"></canvas>
                            </div>
                            <div class="flex space-x-2 mt-2">
                                <button type="button" onclick="limparAssinatura({{ $loopIdx }})" class="text-xs text-amber-600 hover:text-amber-800 font-bold">
                                    <i class="fas fa-eraser mr-1"></i> Limpar
                                </button>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button type="button" onclick="assinar({{ $loopIdx }}, {{ $primeiro->ss_e_nb_id }})" class="flex-1 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-lg shadow">
                                <i class="fas fa-check-circle mr-1"></i> Assinar e Confirmar Todos
                            </button>
                            <button type="button" onclick="negar({{ $primeiro->ss_e_nb_id }})" class="px-4 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold text-sm rounded-lg border border-rose-200">
                                <i class="fas fa-times-circle mr-1"></i> Recusar Todos
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Modal Recusar Assinatura -->
<div id="modal-negar" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-rose-700 mb-2">Recusar Assinatura</h3>
        <p class="text-xs text-gray-600 mb-4">Ao recusar, todos os itens desta entrega serão marcados como recusados. Informe o motivo (opcional):</p>
        <textarea id="justificativa-negacao" rows="3" class="w-full text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Justificativa..."></textarea>
        <input type="hidden" id="negar-entrega-id">
        <div class="flex justify-end space-x-3 mt-4">
            <button type="button" onclick="fecharModalNegar()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg">Voltar</button>
            <button type="button" onclick="confirmarNegar()" class="px-5 py-2 bg-rose-700 text-white text-sm font-bold rounded-lg shadow">Confirmar Recusa</button>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let sigCanvases = {};
    let negarEntregaId = null;

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="sig-canvas-"]').forEach(function(cvs) {
            const id = cvs.id.replace('sig-canvas-', '');
            iniciarCanvas(parseInt(id));
        });
    });

    function iniciarCanvas(id) {
        const cvs = document.getElementById('sig-canvas-' + id);
        if (!cvs) return;
        const rect = cvs.getBoundingClientRect();
        cvs.width = rect.width || 400;
        cvs.height = rect.height || 100;
        const ctx = cvs.getContext('2d');
        ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#000';
        sigCanvases[id] = { canvas: cvs, ctx, isDrawing: false };

        function getPos(e) {
            const r = cvs.getBoundingClientRect();
            let cx = e.clientX, cy = e.clientY;
            if (e.touches && e.touches.length > 0) { cx = e.touches[0].clientX; cy = e.touches[0].clientY; }
            return { x: cx - r.left, y: cy - r.top };
        }
        function start(e) { sigCanvases[id].isDrawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function draw(e) { if (!sigCanvases[id].isDrawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function stop() { sigCanvases[id].isDrawing = false; }
        cvs.addEventListener('mousedown', start); cvs.addEventListener('mousemove', draw); cvs.addEventListener('mouseup', stop);
        cvs.addEventListener('touchstart', start); cvs.addEventListener('touchmove', draw); cvs.addEventListener('touchend', stop);
    }

    function limparAssinatura(id) {
        if (sigCanvases[id]) {
            const { canvas, ctx } = sigCanvases[id];
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function canvasVazio(id) {
        if (!sigCanvases[id]) return true;
        const { canvas, ctx } = sigCanvases[id];
        const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        for (let i = 3; i < data.length; i += 4) { if (data[i] !== 0) return false; }
        return true;
    }

    function assinar(grupoIdx, entregaId) {
        if (canvasVazio(grupoIdx)) {
            Swal.fire('Atenção', 'Desenhe sua assinatura no campo acima antes de confirmar!', 'warning');
            return;
        }
        const sig = sigCanvases[grupoIdx].canvas.toDataURL('image/png');
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('ss_e_tx_assinatura', sig);

        fetch('{{ url("/epi/assinaturas") }}/' + entregaId + '/assinar', {
            method: 'POST', headers: { 'Accept': 'application/json' }, body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Assinatura registrada com sucesso!', timer: 2000, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire('Erro', data.message || 'Falha ao registrar assinatura.', 'error');
            }
        })
        .catch(function() { Swal.fire('Erro', 'Falha na requisição.', 'error'); });
    }

    function negar(id) {
        negarEntregaId = id;
        document.getElementById('negar-entrega-id').value = id;
        document.getElementById('justificativa-negacao').value = '';
        document.getElementById('modal-negar').classList.remove('hidden');
        document.getElementById('modal-negar').style.display = 'flex';
    }

    function fecharModalNegar() {
        document.getElementById('modal-negar').classList.add('hidden');
        document.getElementById('modal-negar').style.display = 'none';
    }

    function confirmarNegar() {
        const id = negarEntregaId;
        const justificativa = document.getElementById('justificativa-negacao').value;
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (justificativa) formData.append('ss_e_tx_justificativa_negacao', justificativa);

        fetch('{{ url("/epi/assinaturas") }}/' + id + '/negar', {
            method: 'POST', headers: { 'Accept': 'application/json' }, body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            fecharModalNegar();
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Assinatura recusada!', text: 'A gestão será notificada.', timer: 2500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire('Erro', data.message || 'Falha ao recusar.', 'error');
            }
        })
        .catch(function() { Swal.fire('Erro', 'Falha na requisição.', 'error'); });
    }
</script>
@endsection
