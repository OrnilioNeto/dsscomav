@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Ranking - Parâmetros</h1>

    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="POST" action="{{ route('admin.ranking.settings.update') }}" class="grid gap-4 md:grid-cols-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <select name="is_active" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ $settings->is_active ? 'selected' : '' }}>Ativo</option>
                    <option value="0" {{ ! $settings->is_active ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Período padrão</label>
                <select name="default_period" class="w-full border rounded px-3 py-2">
                    <option value="monthly" {{ $settings->default_period === 'monthly' ? 'selected' : '' }}>Mensal</option>
                    <option value="content" {{ $settings->default_period === 'content' ? 'selected' : '' }}>Por conteúdo</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-900 text-white rounded px-4 py-2">Salvar configurações</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-xl font-semibold mb-3">Critérios e faixas</h2>
        @forelse($criteria as $criterion)
            <div class="mb-4 border-b pb-4">
                <div class="font-bold">{{ $criterion->name }} <span class="text-gray-500 text-sm">({{ $criterion->slug }})</span></div>
                <div class="text-sm text-gray-600 mb-2">{{ $criterion->description }}</div>
                <div class="mb-3 rounded bg-gray-50 p-3">
                    <form method="POST" action="{{ route('admin.ranking.rules.store', $criterion) }}" class="grid gap-2 md:grid-cols-5 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold mb-1">Faixa</label>
                            <input type="text" name="label" class="w-full border rounded px-3 py-2" placeholder="Ex.: 0-1 hora">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Mín.</label>
                            <input type="number" step="0.01" name="min_value" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Máx.</label>
                            <input type="number" step="0.01" name="max_value" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Pontos</label>
                            <input type="number" name="points" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <button type="submit" class="bg-green-700 text-white rounded px-4 py-2">Adicionar faixa</button>
                        </div>
                    </form>
                </div>

                <ul class="list-disc ml-5 text-sm">
                    @forelse($criterion->rules as $rule)
                        <li>{{ $rule->label }}: {{ $rule->min_value }} - {{ $rule->max_value }} => {{ $rule->points }} pontos</li>
                    @empty
                        <li>Sem faixas cadastradas.</li>
                    @endforelse
                </ul>
            </div>
        @empty
            <p>Nenhum critério encontrado.</p>
        @endforelse
    </div>
</div>
@endsection
