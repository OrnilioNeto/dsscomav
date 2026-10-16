@extends('layout')

@section('title', 'Prazos de Treinamentos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-hourglass-half text-red-600 mr-3"></i>Prazos de Treinamentos
            </h1>
            <p class="text-gray-600 mt-1">
                Libere um novo prazo para usuários concluírem treinamentos vencidos ou que vencem em até 7 dias.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
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

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-900">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Como funciona:</strong> cada treinamento tem como prazo padrão as <strong>23:00 do domingo
            subsequente à liberação</strong>. Após esse prazo o usuário perde o acesso. Ao liberar um novo prazo,
            o usuário volta a poder assistir o vídeo e responder a avaliação (o progresso já feito é mantido),
            e o treinamento retorna para a seção <strong>Em Andamento</strong> do dashboard dele.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-4 mb-6 border border-gray-100">
        <form method="GET" action="{{ route('admin.prazos.index') }}" class="grid gap-4 md:grid-cols-4 items-end">
            <div>
                <label for="training_id" class="block text-sm font-semibold text-gray-700 mb-1">Treinamento</label>
                <select name="training_id" id="training_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    <option value="">Todos</option>
                    @foreach($treinamentos as $training)
                        <option value="{{ $training->id }}" @selected($trainingId === $training->id)>{{ $training->titulo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="situacao" class="block text-sm font-semibold text-gray-700 mb-1">Situação</label>
                <select name="situacao" id="situacao" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    <option value="expirado" @selected($situacao === 'expirado')>Prazo expirado</option>
                    <option value="vencendo" @selected($situacao === 'vencendo')>Vencendo em até 7 dias</option>
                </select>
            </div>

            <div>
                <label for="usuario_nome" class="block text-sm font-semibold text-gray-700 mb-1">Usuário</label>
                <input type="text" name="usuario_nome" id="usuario_nome" value="{{ $usuarioNome }}"
                    placeholder="Buscar por nome"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-900 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition font-semibold">
                    <i class="fas fa-filter mr-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.prazos.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Usuário</th>
                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                        <th class="px-4 py-3 text-center font-semibold">Progresso</th>
                        <th class="px-4 py-3 text-center font-semibold">Liberação</th>
                        <th class="px-4 py-3 text-center font-semibold">Prazo</th>
                        <th class="px-4 py-3 text-center font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginados as $item)
                        @php
                            $horasRestantes = now()->diffInHours($item->prazo, false);
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $item->user->nome }}</div>
                                <div class="text-xs text-gray-500">{{ $item->user->getCpfFormatted() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $item->training->titulo }}</div>
                                <div class="text-xs text-gray-500">{{ strtoupper($item->training->tipo) }} • {{ $item->training->carga_horaria_formatada }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->tem_progresso)
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-1 max-w-[80px] mx-auto">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->porcentagem }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ $item->porcentagem }}%</span>
                                @else
                                    <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-full text-xs font-semibold">Não iniciado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                {{ optional($item->training->data_liberacao_exibicao)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($horasRestantes < 0)
                                    <span class="bg-red-100 text-red-900 px-3 py-1 rounded-full text-xs font-bold">
                                        <i class="fas fa-hourglass-end mr-1"></i>Expirado
                                    </span>
                                    <div class="text-xs text-red-700 mt-1">
                                        em {{ $item->prazo->format('d/m/Y') }} às {{ $item->prazo->format('H:i') }}
                                    </div>
                                @else
                                    <span class="bg-amber-100 text-amber-900 px-3 py-1 rounded-full text-xs font-bold">
                                        <i class="fas fa-hourglass-half mr-1"></i>{{ $horasRestantes < 24 ? 'Vence hoje' : 'Vence em '.ceil($horasRestantes / 24).' dia(s)' }}
                                    </span>
                                    <div class="text-xs text-amber-700 mt-1">
                                        até {{ $item->prazo->format('d/m/Y') }} às {{ $item->prazo->format('H:i') }}
                                    </div>
                                @endif

                                @if($item->prazo_estendido)
                                    <div class="text-xs text-blue-700 mt-1">
                                        <i class="fas fa-calendar-plus mr-1"></i>Prorrogado
                                        @if($item->prazo_estendido_em)
                                            em {{ $item->prazo_estendido_em->format('d/m/Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                    onclick="abrirModalPrazo(
                                        {{ $item->user->id }},
                                        '{{ addslashes($item->user->nome) }}',
                                        {{ $item->training->id }},
                                        '{{ addslashes($item->training->titulo) }}'
                                    )"
                                    class="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                                    <i class="fas fa-calendar-plus mr-1"></i>Liberar novo prazo
                                </button>

                                @if($item->prazo_estendido && $item->progress)
                                    <form action="{{ route('admin.prazos.destroy', $item->progress) }}" method="POST" class="inline mt-1"
                                          onsubmit="return confirm('Remover a prorrogação de prazo deste usuário?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-gray-500 hover:text-red-700 underline mt-1">
                                            Remover prorrogação
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-600">
                                <i class="fas fa-check-circle text-3xl text-green-400 mb-2"></i>
                                <p>
                                    @if($situacao === 'vencendo')
                                        Nenhum treinamento vencendo nos próximos 7 dias.
                                    @else
                                        Nenhum treinamento com prazo expirado. Tudo em dia!
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginados->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $paginados->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal de Liberar Novo Prazo -->
<div id="modalPrazo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-calendar-plus text-red-600 mr-2"></i>Liberar Novo Prazo
                </h3>
                <button onclick="fecharModalPrazo()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form action="{{ route('admin.prazos.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <input type="hidden" name="user_id" id="modal_prazo_user_id">
                <input type="hidden" name="training_id" id="modal_prazo_training_id">

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Usuário:</p>
                    <p class="text-gray-800 font-semibold" id="modal_prazo_usuario"></p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Treinamento:</p>
                    <p class="text-gray-800 font-semibold" id="modal_prazo_treinamento"></p>
                </div>

                <div>
                    <label for="modal_prazo_data" class="block text-sm font-semibold text-gray-700 mb-1">Novo prazo (data e hora) *</label>
                    <input type="datetime-local" name="prazo" id="modal_prazo_data" required
                        value="{{ $prazoSugerido }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Sugestão: 23:00 do próximo domingo. O usuário poderá assistir e responder a avaliação até este momento.
                    </p>
                </div>

                <div>
                    <label for="modal_prazo_justificativa" class="block text-sm font-semibold text-gray-700 mb-1">Justificativa *</label>
                    <textarea name="justificativa" id="modal_prazo_justificativa" rows="3" required maxlength="1000"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Ex: Usuário estava em férias/ausente no período de liberação."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Campo obrigatório. Ficará registrado para fins de auditoria.</p>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="fecharModalPrazo()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-check mr-2"></i>Confirmar Liberação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalPrazo(userId, userNome, trainingId, trainingTitulo) {
    document.getElementById('modal_prazo_user_id').value = userId;
    document.getElementById('modal_prazo_usuario').textContent = userNome;
    document.getElementById('modal_prazo_training_id').value = trainingId;
    document.getElementById('modal_prazo_treinamento').textContent = trainingTitulo;

    document.getElementById('modalPrazo').classList.remove('hidden');
}

function fecharModalPrazo() {
    document.getElementById('modalPrazo').classList.add('hidden');
}

document.getElementById('modalPrazo').addEventListener('click', function (e) {
    if (e.target === this) {
        fecharModalPrazo();
    }
});
</script>
@endsection
