@extends('layout')

@section('title', 'Configurações — Folgas')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-cog text-indigo-600 mr-2"></i>Configurações — Folgas
        </h1>
        <a href="{{ route('admin.folgas.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
    </div>

    <form action="{{ route('admin.folgas.config.update') }}" method="POST" class="bg-white rounded-xl shadow-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Dias trabalhados para ganhar 1 folga</label>
            <input type="number" name="dias_para_folga" value="{{ $settings->dias_para_folga }}" min="1" max="30"
                   class="w-32 border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="text-xs text-gray-500 mt-1">Quantos dias trabalhados consecutivos geram 1 crédito de folga. Padrão: 6.</p>
        </div>

        <div class="border-t pt-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="exige_domingo" value="1" {{ $settings->exige_domingo ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5">
                <div>
                    <span class="text-sm font-bold text-gray-700">Exigir folga no domingo</span>
                    <p class="text-xs text-gray-500">Pelo menos 1 folga deve ser em domingo no mês. O sistema exibirá alerta visual quando não cumprido.</p>
                </div>
            </label>
        </div>

        <div class="border-t pt-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="bloquear_sem_domingo" value="1" {{ $settings->bloquear_sem_domingo ? 'checked' : '' }}
                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-5 h-5">
                <div>
                    <span class="text-sm font-bold text-gray-700">Bloquear fechamento sem domingo</span>
                    <p class="text-xs text-gray-500">Quando ativado, o sistema bloqueará a confirmação do mês se algum motorista não tiver folga no domingo.</p>
                </div>
            </label>
        </div>

        <div class="border-t pt-4 flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition font-bold">
                <i class="fas fa-save mr-2"></i>Salvar Configurações
            </button>
        </div>
    </form>

    {{-- Marco zero: início do controle + saldo inicial por motorista --}}
    <form action="{{ route('admin.folgas.config.saldos') }}" method="POST" class="mt-8 bg-white rounded-xl shadow-lg p-6 space-y-4">
        @csrf

        <div>
            <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-flag-checkered text-emerald-600 mr-2"></i>Início do Controle (marco zero)</h2>
            <p class="text-xs text-gray-500 mt-1">
                A partir desta data o banco de folgas passa a contar. Nada antes dela é considerado
                (nem créditos, nem folgas tiradas).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Controle a partir de</label>
                <input type="date" name="data_inicio_controle" id="dataInicioControle"
                       value="{{ $settings->data_inicio_controle?->format('Y-m-d') }}"
                       class="w-full border-gray-300 rounded-lg text-sm focus:border-emerald-500 focus:ring-emerald-500" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar motorista</label>
                <input type="text" id="buscaMotoristaSaldo" placeholder="Filtrar por nome ou CPF..."
                       class="w-full border-gray-300 rounded-lg text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </div>

        <div class="border rounded-lg overflow-hidden">
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 text-left font-bold text-gray-600">Motorista</th>
                            <th class="p-2 text-left font-bold text-gray-600">CPF</th>
                            <th class="p-2 text-center font-bold text-gray-600 w-40">Saldo inicial (folgas)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="tabelaSaldos">
                        @forelse($motoristas as $m)
                            <tr class="linha-saldo hover:bg-emerald-50/40" data-busca="{{ strtolower($m->nome.' '.$m->cpf) }}">
                                <td class="p-2 font-medium text-gray-800">{{ $m->nome }}</td>
                                <td class="p-2 text-gray-500 font-mono text-xs">{{ $m->cpf }}</td>
                                <td class="p-2 text-center">
                                    <input type="number" name="saldos[{{ $m->id }}]" min="0" max="365"
                                           value="{{ (int) $m->saldo_inicial_folgas }}"
                                           class="w-24 border-gray-300 rounded-lg text-sm text-center">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-400 text-xs">Nenhum motorista ativo encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-xs text-gray-500">
            Exemplo: motorista com <strong>2 folgas</strong> em <strong>01/10/2026</strong> — informe a data e o saldo 2 para ele (e para os demais de uma vez).
        </p>

        <div class="flex justify-end">
            <button type="submit" class="bg-emerald-700 text-white px-6 py-2 rounded-lg hover:bg-emerald-800 transition font-bold"
                    onclick="return confirm('Salvar a data de início do controle e os saldos iniciais de todos os motoristas?')">
                <i class="fas fa-save mr-2"></i>Salvar Data e Saldos Iniciais
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filtro = document.getElementById('buscaMotoristaSaldo');
            if (!filtro) return;
            filtro.addEventListener('input', function () {
                const termo = this.value.toLowerCase().trim();
                document.querySelectorAll('#tabelaSaldos .linha-saldo').forEach(function (linha) {
                    linha.style.display = linha.dataset.busca.includes(termo) ? '' : 'none';
                });
            });
        });
    </script>

    <div class="mt-8 bg-gray-50 rounded-xl p-6 border border-gray-200 text-sm text-gray-600 space-y-2">
        <h3 class="font-bold text-gray-800 flex items-center">
            <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Regras do Sistema
        </h3>
        <p>• A cada <strong>{{ $settings->dias_para_folga }} dias trabalhados</strong>, o motorista ganha 1 folga (crédito no banco).</p>
        <p>• Folga, atestado, licença e <strong>férias</strong> zeram a sequência e ela recomeça no dia seguinte; a contagem atravessa meses.</p>
        <p>• A <strong>Previsão do mês</strong> (card/relatórios) usa o ciclo 6x1: 6 dias trabalhados + 1 folga que não conta para o próximo crédito. É a mesma para todos e não considera lançamentos.</p>
        <p>• A coluna <strong>Saldo</strong> é o saldo real — só créditos efetivos (conforme os lançamentos), acumulando mês a mês.</p>
        <p>• Se o motorista tirar mais folgas do que tem, o banco <strong>zera e recomeça</strong> — não fica devendo folgas.</p>
        <p>• Dias sem lançamento são considerados como <strong>trabalho presumido</strong>.</p>
        <p>• Antes da data de início do controle, nada é contabilizado; o saldo inicial informado vale como saldo de partida.</p>
    </div>

</div>
@endsection
