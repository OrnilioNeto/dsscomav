@extends('layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-4xl font-bold text-gray-800">
                {{ $isGeneral ? 'Ranking Geral Acumulado' : "Ranking de Engajamento - $month/$year" }}
            </h1>
            <p class="text-gray-500 mt-1">{{ $isGeneral ? 'Dados consolidados desde o início da plataforma' : 'Treinamentos lançados neste mês' }}</p>
        </div>
        
        <form action="{{ route('admin.ranking.recalculate') }}" method="POST" onsubmit="return confirm('Isso processará todos os certificados e progressos antigos para atualizar as notas. Deseja continuar?')">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition font-bold shadow-lg shadow-blue-200 flex items-center gap-2">
                <i class="fas fa-sync-alt"></i> Recalcular Dados Históricos
            </button>
        </form>
    </div>

    <!-- Painel Executivo: Alcance Operacional -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-slate-900 rounded-xl p-6 mb-8 text-white shadow-2xl border-b-4 border-blue-500">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-2 flex items-center">
                    <i class="fas fa-users-cog mr-3 text-blue-400"></i>Alcance e Adesão da Base
                </h2>
                <p class="text-blue-100 opacity-90 text-lg">
                    {{ $isGeneral ? 'No acumulado total,' : 'Neste período,' }} 
                    <span class="font-bold text-white underline decoration-blue-400 decoration-2">{{ number_format($taxaAdesao, 1) }}%</span> 
                    da sua base elegível concluiu treinamentos, totalizando <span class="font-bold text-white">{{ $totalCertificadosPeriodo }} certificados</span>.
                </p>
            </div>
            <div class="bg-white/10 p-4 rounded-lg text-center backdrop-blur-sm min-w-[150px]">
                <span class="text-4xl font-extrabold">{{ number_format($taxaAdesao, 1) }}%</span>
                <p class="text-xs uppercase tracking-wider text-blue-200 mt-1">Taxa de Adesão</p>
            </div>
        </div>
    </div>

    <!-- Cards de Engajamento de Elite -->
    <div class="grid lg:grid-cols-3 gap-6 mb-12">
        <!-- Pódio de Excelência (Baseado no Score da Tabela) -->
        <div class="bg-white p-1 rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-amber-50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-amber-900 flex items-center">
                    <i class="fas fa-crown mr-3 text-amber-500"></i>Pódio de Pontuação
                </h2>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-100 px-2 py-1 rounded">Consistência</span>
            </div>
            <div class="p-6">
                <p class="text-gray-500 text-sm mb-6">Os 3 melhores baseados no algoritmo de pontuação ponderada.</p>
                <div class="space-y-4">
                    @foreach($rows->take(3) as $index => $r)
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 border border-amber-100">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-sm">
                                {{ $index + 1 }}º
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 truncate text-sm">{{ $r->user->nome ?? '—' }}</p>
                                <p class="text-[10px] text-gray-500 uppercase">{{ $r->average_score }} pontos</p>
                            </div>
                        </div>
                    @endforeach
                    @if($rows->count() === 0)
                         <p class="text-gray-400 italic text-sm">Sem dados consolidados.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pioneiros -->
        <div class="bg-white p-1 rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-blue-900 flex items-center">
                    <i class="fas fa-rocket mr-3"></i>Elite: Pioneiros
                </h2>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-100 px-2 py-1 rounded">Velocidade</span>
            </div>
            <div class="p-6">
                <p class="text-gray-500 text-sm mb-6">Identifica os colaboradores mais proativos que acessam o conteúdo assim que liberado.</p>
                <p class="text-gray-500 text-sm mb-6">Iniciaram os conteúdos de <b>{{ $isGeneral ? 'todo o período' : "Mês $month" }}</b> mais rapidamente.</p>
                <div class="space-y-4">
                    @foreach($pioneiros as $index => $p)
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100 hover:border-blue-200 transition-colors">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
                                {{ $index + 1 }}º
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 truncate">{{ $p->user->nome }}</p>
                                <p class="text-xs text-gray-500">{{ $p->user->cargo }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-blue-700">{{ round($p->tempo_reacao / 3600, 1) }}h</p>
                                <p class="text-[10px] uppercase font-bold text-gray-400">Tempo de Resposta</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Focados -->
        <div class="bg-white p-1 rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-emerald-900 flex items-center">
                    <i class="fas fa-bullseye mr-3 text-emerald-600"></i>Elite: Focados
                </h2>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-100 px-2 py-1 rounded">Fluidez</span>
            </div>
            <div class="p-6">
                <p class="text-gray-500 text-sm mb-6">Usuários que iniciam e concluem o treinamento com foco contínuo.</p>
                <p class="text-gray-500 text-sm mb-6">Quem assistiu aos vídeos de <b>{{ $isGeneral ? 'todo o período' : "Mês $month" }}</b> com maior foco.</p>
                <div class="space-y-4">
                    @foreach($focados as $index => $f)
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100 hover:border-emerald-200 transition-colors">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold">
                                {{ $index + 1 }}º
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 truncate">{{ $f->user->nome }}</p>
                                <p class="text-xs text-gray-500">{{ $f->user->empresa }}</p>
                            </div>
                            <div class="text-right">
                                <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                                <p class="text-[10px] uppercase font-bold text-gray-400 mt-1">Alta Retenção</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gray-800 px-8 py-6 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold italic tracking-tight">PONTUAÇÃO GERAL ACUMULADA</h2>
                <p class="text-gray-400 text-sm">Baseado em certificados e volume de assistência</p>
            </div>
            
            <form method="get" class="flex flex-wrap gap-4">
                <select name="month" class="bg-gray-700 border-none text-white text-sm rounded-lg focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="0" {{ $month == 0 ? 'selected' : '' }}>Ranking Geral (Tudo)</option>
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Mês {{ $m }}</option>
                    @endfor
                </select>

                <select name="year" class="bg-gray-700 border-none text-white text-sm rounded-lg focus:ring-blue-500" onchange="this.form.submit()">
                    @for($y=2024; $y<=2026; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <select name="top" class="bg-gray-700 border-none text-white text-sm rounded-lg focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="10" {{ $top==10 ? 'selected' : '' }}>Top 10</option>
                    <option value="20" {{ $top==20 ? 'selected' : '' }}>Top 20</option>
                    <option value="0" {{ $top==0 ? 'selected' : '' }}>Ver Tudo</option>
                </select>

                <select name="type" class="bg-gray-700 border-none text-white text-sm rounded-lg focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>Todos os Perfis</option>
                    <option value="motorista" {{ ($type ?? 'all') === 'motorista' ? 'selected' : '' }}>Motoristas</option>
                    <option value="funcionario" {{ ($type ?? 'all') === 'funcionario' ? 'selected' : '' }}>Funcionários</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Posição</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase">Colaborador</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase text-center">Pontuação Total</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase text-center">Conteúdos</th>
                        <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $index => $r)
                        <tr class="hover:bg-blue-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <span class="text-2xl font-black {{ $index < 3 ? 'text-amber-500' : 'text-gray-300' }}">#{{ $r->position ?? ($index + 1) }}</span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="user-name font-bold text-gray-900 cursor-help" data-user-id="{{ $r->user_id ?? $r->user?->id }}" data-month="{{ $month }}" data-year="{{ $year }}">
                                        {{ $r->user->nome ?? $r->user?->nome ?? '—' }}
                                    </span>
                                    <span class="text-xs text-gray-500 uppercase tracking-tighter">{{ $r->user->tipo_usuario ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-black bg-blue-100 text-blue-800">
                                    {{ $r->average_score ?? '—' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center font-bold text-gray-600">
                                {{ $r->real_content_count ?? '0' }}
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button class="text-gray-400 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-8 py-12 text-center text-gray-400 italic">Sem registros no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('extra_js')
<style>
/* Tooltip básico */
.ranking-tooltip{position:absolute;z-index:9999;background:#fff;border:1px solid rgba(0,0,0,.12);padding:10px;border-radius:6px;box-shadow:0 4px 14px rgba(0,0,0,.08);max-width:320px;font-size:13px}
.ranking-tooltip h4{margin:0 0 6px 0;font-size:13px}
.ranking-tooltip ul{margin:0;padding:0 0 0 18px}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    let tooltip;

    function showTooltip(target, html){
        if (tooltip) tooltip.remove();
        tooltip = document.createElement('div');
        tooltip.className = 'ranking-tooltip';
        tooltip.innerHTML = html;
        document.body.appendChild(tooltip);

        const rect = target.getBoundingClientRect();
        tooltip.style.top = (window.scrollY + rect.top - tooltip.offsetHeight - 8) + 'px';
        tooltip.style.left = (window.scrollX + rect.left) + 'px';
    }

    function hideTooltip(){ if (tooltip) { tooltip.remove(); tooltip = null; } }

    document.querySelectorAll('.user-name').forEach(function(el){
        let timer;
        el.addEventListener('mouseenter', function(){
            timer = setTimeout(async () => {
                const userId = el.dataset.userId;
                const month = el.dataset.month;
                const year = el.dataset.year;
                try {
                    const res = await fetch('/admin/ranking/breakdown/' + userId + '?month=' + month + '&year=' + year, {headers:{'X-Requested-With':'XMLHttpRequest'}});
                    if (!res.ok) throw new Error('Erro');
                    const json = await res.json();
                    let html = '<h4>Detalhes da pontuação</h4>';
                    if (!json.trainings || json.trainings.length === 0) html += '<div>Nenhum treinamento encontrado</div>';
                    json.trainings.forEach(function(t){
                        html += '<div style="margin-bottom:8px"><strong>' + (t.training_title || 'Treinamento') + '</strong>';
                        html += '<div class="text-sm">Raw: ' + t.raw_score + ' / Max: ' + t.max_possible + ' → ' + t.normalized + '%</div>';
                        html += '<ul>'; 
                        t.criteria.forEach(function(c){
                            html += '<li>' + c.label + ': <strong>' + c.points + ' pts</strong> (' + c.value + ')</li>';
                        });
                        html += '</ul></div>';
                    });
                    showTooltip(el, html);
                } catch (e) { showTooltip(el, '<div>Erro ao obter detalhes</div>'); }
            }, 220);
        });

        el.addEventListener('mouseleave', function(){ clearTimeout(timer); hideTooltip(); });
    });
});
</script>
@endsection
