@extends('layout')

@section('title', 'Configurações — Folgas')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

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

    <div class="mt-8 bg-gray-50 rounded-xl p-6 border border-gray-200 text-sm text-gray-600 space-y-2">
        <h3 class="font-bold text-gray-800 flex items-center">
            <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Regras do Sistema
        </h3>
        <p>• A cada <strong>{{ $settings->dias_para_folga }} dias trabalhados consecutivos</strong>, o motorista ganha 1 folga (crédito no banco).</p>
        <p>• Folga, atestado e licença <strong>interrompem</strong> a sequência de dias contínuos.</p>
        <p>• Feriados contam como dias trabalhados (não interrompem a sequência).</p>
        <p>• Saldo positivo acumula pro mês seguinte. Saldo negativo = motorista deve folgas.</p>
        <p>• Dias sem lançamento são considerados como <strong>trabalho presumido</strong>.</p>
    </div>

</div>
@endsection
