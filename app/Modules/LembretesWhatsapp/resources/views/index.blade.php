@extends('layout')

@section('title', 'Lembretes WhatsApp')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fab fa-whatsapp text-green-600 mr-2"></i>Lembretes WhatsApp
            </h1>
            <p class="text-gray-500 mt-1">
                Histórico dos lembretes de treinamento disparados aos colaboradores pendentes.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @include('lembretes_whatsapp::partials.sessao-badge')
        </div>
    </div>

    @include('lembretes_whatsapp::partials.tabs')

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-yellow-100">
            <p class="text-sm text-gray-500">Na fila</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $resumo['fila'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-green-100">
            <p class="text-sm text-gray-500">Enviados hoje</p>
            <p class="text-2xl font-bold text-green-600">{{ $resumo['enviados_hoje'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-red-100">
            <p class="text-sm text-gray-500">Falhas</p>
            <p class="text-2xl font-bold text-red-600">{{ $resumo['falhas'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-slate-100">
            <p class="text-sm text-gray-500">Sem telefone</p>
            <p class="text-2xl font-bold text-slate-600">{{ $resumo['pulados'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4 text-sm text-gray-600 border border-gray-100">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <span>
                <i class="fas fa-power-off mr-1 {{ $configuracao['enabled'] ? 'text-green-600' : 'text-red-600' }}"></i>
                Integração: <strong>{{ $configuracao['enabled'] ? 'ativada' : 'desativada' }}</strong>
            </span>
            <span><i class="fas fa-server mr-1 text-gray-400"></i>Gateway: <strong>{{ $configuracao['gateway_url'] }}</strong></span>
            <span><i class="fas fa-mobile-alt mr-1 text-gray-400"></i>Sessão: <strong>{{ $configuracao['session_id'] }}</strong></span>
            <span><i class="fas fa-clock mr-1 text-gray-400"></i>Janela: <strong>{{ $configuracao['window_start'] }}–{{ $configuracao['window_end'] }}</strong>{{ $configuracao['allow_weekends'] ? ' (fins de semana)' : ' (dias úteis)' }}</span>
            <span><i class="fas fa-tachometer-alt mr-1 text-gray-400"></i>Cadência: <strong>5 min</strong> (intervalo mín. {{ $configuracao['delay_min'] }}–{{ $configuracao['delay_max'] }}s)</span>
            <span><i class="fas fa-calculator mr-1 text-gray-400"></i>Teto diário: <strong>{{ $configuracao['daily_cap'] }}</strong></span>
            <span><i class="fas fa-layer-group mr-1 text-gray-400"></i>Lote máx.: <strong>{{ $configuracao['batch_limit'] }}</strong></span>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.lembretes.index') }}" class="bg-white rounded-2xl shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Busca (nome ou CPF)</label>
            <input type="text" name="busca" value="{{ request('busca') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex.: João ou 12345678900">
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Treinamento</label>
            <select name="training_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($treinamentos as $treinamento)
                    <option value="{{ $treinamento->id }}" @selected((int) request('training_id') === $treinamento->id)>{{ $treinamento->titulo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($statusValidos as $slug => $label)
                    <option value="{{ $slug }}" @selected(request('status') === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2 md:col-span-2">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">De</label>
                <input type="date" name="de" value="{{ request('de') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Até</label>
                <input type="date" name="ate" value="{{ request('ate') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex items-end gap-2 md:col-span-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 transition">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            <a href="{{ route('admin.lembretes.index') }}" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition">
                <i class="fas fa-redo mr-1"></i>Limpar
            </a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Criado em</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Colaborador</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Treinamento</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Telefone</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Entrega</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Agendado</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Enviado</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm">Detalhe</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reminders as $reminder)
                        @php
                            $cores = [
                                'fila' => 'bg-yellow-100 text-yellow-900',
                                'enviado' => 'bg-green-100 text-green-900',
                                'falhou' => 'bg-red-100 text-red-900',
                                'pulado' => 'bg-slate-200 text-slate-700',
                                'cancelado' => 'bg-gray-200 text-gray-700',
                            ];
                        @endphp
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $reminder->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-800">{{ $reminder->user?->nome ?? 'Usuário removido' }}</div>
                                <div class="text-xs text-gray-500">por {{ $reminder->creator?->nome ?? 'sistema' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $reminder->training?->titulo ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $reminder->telefone ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $cores[$reminder->status] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 rounded-full text-xs font-semibold">
                                    {{ $reminder->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $coresEntrega = [
                                        'PENDING' => 'bg-yellow-100 text-yellow-900',
                                        'SENT' => 'bg-slate-200 text-slate-700',
                                        'DELIVERED' => 'bg-green-100 text-green-900',
                                        'READ' => 'bg-blue-100 text-blue-900',
                                        'FAILED' => 'bg-red-100 text-red-900',
                                    ];
                                @endphp
                                @if($reminder->entrega_label)
                                    <span class="{{ $coresEntrega[$reminder->delivery_status] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 rounded-full text-xs font-semibold"
                                          title="{{ $reminder->delivered_at ? 'Entregue em '.$reminder->delivered_at->format('d/m/Y H:i') : '' }}">
                                        {{ $reminder->entrega_label }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $reminder->agendado_para?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $reminder->enviado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs">
                                @if($reminder->gateway_message_id)
                                    <span title="ID no gateway">ID {{ \Illuminate\Support\Str::limit($reminder->gateway_message_id, 12) }}</span>
                                @endif
                                @if($reminder->erro)
                                    <div class="text-red-600">{{ \Illuminate\Support\Str::limit($reminder->erro, 80) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($reminder->status === 'fila')
                                    <form method="POST" action="{{ route('admin.lembretes.cancelar', $reminder) }}" onsubmit="return confirm('Cancelar este lembrete?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                            <i class="fas fa-ban mr-1"></i>Cancelar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-gray-500">
                                <i class="fab fa-whatsapp text-3xl text-gray-300 mb-2"></i>
                                <p>Nenhum lembrete registrado. Use o botão <strong>Lembrar pendentes</strong> no relatório de treinamentos.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reminders->hasPages())
        <div class="mt-6">{{ $reminders->links() }}</div>
    @endif
</div>
@endsection
