@extends('layout')

@section('title', 'Módulo de Exemplo')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow p-8">
        <div class="flex items-center gap-3 mb-4">
            <i class="fas fa-cube text-3xl text-emerald-700"></i>
            <h1 class="text-2xl font-bold text-gray-800">Módulo de Exemplo</h1>
        </div>

        <p class="text-gray-600 mb-4">
            Esta página é servida por <code class="bg-gray-100 px-1 rounded">app/Modules/Exemplo</code>
            e existe para demonstrar a infraestrutura de módulos:
        </p>

        <ul class="list-disc list-inside text-gray-700 space-y-1 mb-6">
            <li>Provider descoberto automaticamente (<code>ModulesServiceProvider</code>).</li>
            <li>Rotas do módulo em <code>routes/web.php</code> próprio.</li>
            <li>View no namespace <code>exemplo::index</code>.</li>
            <li>Migration própria carregada no <code>php artisan migrate</code>.</li>
            <li>Item de menu registrado via <code>registerMenu()</code>.</li>
        </ul>

        <p class="text-sm text-gray-500">
            Para criar um módulo real (ex.: Agendamento), siga o guia
            <code class="bg-gray-100 px-1 rounded">docs/modulos/GUIA_MODULOS.md</code>.
            Este módulo pode ser removido apagando a pasta <code>app/Modules/Exemplo</code>.
        </p>
    </div>
</div>
@endsection
