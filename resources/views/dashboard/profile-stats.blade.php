@extends('layout')

@section('title', 'Minha Performance')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <!-- Header Moderno -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-blue-50 text-blue-600 mb-4 shadow-inner">
            <i class="fas fa-chart-line text-3xl"></i>
        </div>
        <h1 class="text-4xl font-black text-slate-900 tracking-tight">Sua Jornada de Excelência</h1>
        <p class="text-slate-500 mt-2">Mês de referência: <span class="font-bold text-slate-700">{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</span></p>
    </div>

    <!-- Card de Nível e Mensagem de Gratidão -->
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-blue-100 overflow-hidden mb-12 border border-slate-100">
        <div class="p-8 md:p-12 flex flex-col md:flex-row items-center gap-10">
            <div class="relative">
                <div class="w-40 h-40 rounded-full flex items-center justify-center bg-slate-50 border-8 border-slate-100 shadow-xl">
                    <i class="fas {{ $rankingLevel['icon'] }} text-6xl" style="color: {{ $rankingLevel['color'] }}"></i>
                </div>
                <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-6 py-2 rounded-full bg-slate-900 text-white font-black text-sm uppercase tracking-tighter">
                    {{ $rankingLevel['name'] }}
                </div>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-3xl font-black text-slate-900 mb-4">Obrigado pela sua dedicação!</h2>
                <p class="text-lg text-slate-600 leading-relaxed mb-6">
                    {{ $rankingLevel['msg'] }}
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-8">
                    <div class="text-center md:text-left">
                        <span class="text-sm font-bold text-slate-400 uppercase tracking-widest block">Ranking</span>
                        <span class="text-4xl font-black text-blue-900">{{ $userRank > 0 ? $userRank . 'º' : '--' }}</span>
                    </div>
                    <div class="text-center md:text-left">
                        <span class="text-sm font-bold text-slate-400 uppercase tracking-widest block">Score Total</span>
                        <span class="text-4xl font-black text-blue-900">{{ number_format($totalPoints, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalhamento dos Pontos -->
    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i class="fas fa-list-ul text-blue-500"></i> O que compõe sua nota?
    </h3>

    <div class="space-y-6">
        @forelse($trainings as $training)
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-50">
                    <h4 class="font-bold text-slate-900">{{ $training->training_title }}</h4>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-black text-sm">
                        +{{ $training->raw_score }} pts
                    </span>
                </div>
                
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach($training->criteria as $criterion)
                        <div class="bg-slate-50 rounded-2xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ $criterion->label }}</p>
                            <div class="flex items-end justify-between">
                                <span class="text-lg font-bold text-slate-700">
                                    @if($criterion->slug == 'start_time') 
                                        {{ $criterion->value > 24 ? floor($criterion->value / 24) . 'd' : round($criterion->value, 1) . 'h' }}
                                    @elseif($criterion->slug == 'completion_time') 
                                        {{ $criterion->value }} dias
                                    @else 
                                        {{ $criterion->value }}ª tent.
                                    @endif
                                </span>
                                <span class="text-xs font-bold text-blue-600">+{{ $criterion->points }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                <p class="text-slate-400">Nenhum treinamento processado para este mês ainda.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-12 text-center">
        <a href="{{ route('dashboard') }}" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-bold hover:bg-slate-800 transition shadow-lg">
            <i class="fas fa-arrow-left mr-2"></i> Voltar ao Dashboard
        </a>
    </div>
</div>
@endsection