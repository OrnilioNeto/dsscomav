@extends('layout')

@section('content')
<div class="container">
    <h1>Ranking - {{ $month }}/{{ $year }}</h1>

    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <label class="form-label">Top</label>
            <select name="top" class="form-select" onchange="this.form.submit()">
                <option value="5" {{ $top==5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ $top==10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $top==20 ? 'selected' : '' }}>20</option>
                <option value="0" {{ $top==0 ? 'selected' : '' }}>Todos</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label">Tipo</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="motorista" {{ ($type ?? 'all') === 'motorista' ? 'selected' : '' }}>Motoristas</option>
                <option value="funcionario" {{ ($type ?? 'all') === 'funcionario' ? 'selected' : '' }}>Funcionários</option>
            </select>
        </div>
    </form>

    @if(isset($hasRealRanking) && ! $hasRealRanking)
        <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-yellow-900">
            O ranking mensal ainda não foi consolidado para este período. Mostrando certificados concluídos do mês como base inicial.
        </div>
    @endif

    @if($rows->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-6 text-gray-700">
            Nenhum dado encontrado para este período.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Posição</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Pontuação</th>
                        <th>Qtd Conteúdos</th>
                        <th>Último treinamento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $r)
                        <tr>
                            <td>{{ $r->position ?? ($index + 1) }}</td>
                            <td>
                                <span class="user-name" data-user-id="{{ $r->user_id ?? $r->user?->id }}" data-month="{{ $month }}" data-year="{{ $year }}">{{ $r->user->nome ?? $r->user?->nome ?? '—' }}</span>
                            </td>
                            <td>{{ $r->user->tipo_usuario ?? '—' }}</td>
                            <td>
                                <span class="average-score">{{ $r->average_score ?? '—' }}</span>
                                @if(isset($r->fallback_source))
                                    <span class="ml-2 inline-block rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">baseado em certificados</span>
                                @endif
                            </td>
                            <td>{{ $r->content_count ?? '—' }}</td>
                            <td>{{ $r->last_training_title ?? ($r->training->titulo ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
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
