@extends('layout')
@section('title', 'Ranking de Performance')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800"><i class="fas fa-trophy text-yellow-500 mr-3"></i>Elite da Segurança</h1>
        <form action="{{ route('admin.ranking.recalculate') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition">
                <i class="fas fa-sync-alt mr-2"></i>Recalcular com Certificados
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <table class="w-full">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-center text-sm font-bold text-gray-500 uppercase w-20">Posição</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-500 uppercase">Colaborador</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-gray-500 uppercase">Pontuação Total</th>
                    <th class="px-6 py-4 text-center text-sm font-bold text-gray-500 uppercase">Índice Médio</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($topUsers as $index => $score)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-center font-bold text-lg text-gray-700">
                        {{ $index + 1 }}º
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="{{ $score->user->getFotoPerfilUrl() }}" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-bold text-gray-800">{{ $score->user->nome }}</p>
                                <p class="text-xs text-gray-500">{{ $score->user->empresa }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-xl font-black text-blue-900">{{ number_format($score->total_points, 0) }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            {{ number_format($score->avg_normalized, 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection