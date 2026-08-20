@extends('layout')

@section('title', 'Projetos Pedagógicos')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-book-open text-blue-900 mr-2"></i>Projetos Pedagógicos
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Um projeto pedagógico pode atender a <strong>um ou mais treinamentos</strong> (NR-01 Anexo II 3.1). Documento administrativo disponível à fiscalização, sindicato, CIPA e contratantes (4.1/4.1.1).
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('projetos-pedagogicos.create') }}" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold rounded-lg shadow">
                <i class="fas fa-plus mr-1"></i> Novo Projeto Pedagógico
            </a>
            <a href="{{ route('treinamentos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-300 text-center">
                <i class="fas fa-arrow-left mr-1"></i> Treinamentos
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-semibold">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif

    @if($treinamentosSemPP > 0)
        <div class="mb-5 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            {{ $treinamentosSemPP }} treinamento(s) ainda <strong>sem projeto pedagógico</strong>.
            <a href="{{ route('projetos-pedagogicos.create') }}" class="font-bold underline ml-1">Cadastrar agora</a>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Versão</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Treinamentos atendidos</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Preenchimento</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Validação</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Próxima revisão</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($projetos as $pp)
                        @php
                            $status = $pp->status_revisao;
                            $statusCfg = [
                                'ok' => ['bg-emerald-100 text-emerald-800', 'OK'],
                                'proxima' => ['bg-amber-100 text-amber-800', 'Revisão próxima'],
                                'vencida' => ['bg-rose-100 text-rose-800', 'Revisão vencida'],
                                'sem_revisao' => ['bg-gray-100 text-gray-600', 'Sem data de revisão'],
                            ][$status];
                            $treinamentosPp = $pp->trainings_list;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 bg-gray-100 border border-gray-300 rounded font-mono text-xs font-bold text-gray-700">v{{ $pp->versao ?? '1.0' }}</span>
                                @if($pp->assinatura_rt)
                                    <div class="mt-1 text-[10px] text-emerald-700 font-bold"><i class="fas fa-signature mr-1"></i>Assinado</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($treinamentosPp as $treinamento)
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-800 border border-blue-200 rounded text-xs font-semibold">
                                            {{ $treinamento->titulo }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center gap-2 justify-center">
                                    <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $pp->percentual_preenchimento === 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $pp->percentual_preenchimento }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600">{{ $pp->percentual_preenchimento }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">
                                {{ $pp->data_validacao?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">
                                {{ $pp->data_proxima_revisao?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusCfg[0] }}">{{ $statusCfg[1] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('projetos-pedagogicos.edit', $pp) }}" class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg hover:bg-blue-100 border border-blue-200 mr-1">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                                <a href="{{ route('projetos-pedagogicos.download', $pp) }}" class="inline-flex items-center px-2.5 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-100 border border-emerald-200 mr-1" title="Baixar PDF padrão do projeto pedagógico">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </a>
                                @if($pp->arquivo_pdf)
                                    <a href="{{ route('projetos-pedagogicos.download-arquivo', $pp) }}" class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-200 mr-1" title="Baixar documento assinado">
                                        <i class="fas fa-file-signature mr-1"></i> Assinado
                                    </a>
                                @endif
                                <form action="{{ route('projetos-pedagogicos.destroy', $pp) }}" method="POST" class="inline" onsubmit="return confirm('Excluir este projeto pedagógico? Os treinamentos vinculados ficarão livres para um novo vínculo.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-rose-50 text-rose-700 text-xs font-bold rounded-lg hover:bg-rose-100 border border-rose-200" title="Excluir">
                                        <i class="fas fa-trash mr-1"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                <i class="fas fa-book-open text-4xl text-gray-300 mb-2"></i>
                                <p class="font-semibold text-gray-500">Nenhum projeto pedagógico cadastrado ainda.</p>
                                <p class="text-sm mt-1">Clique em "Novo Projeto Pedagógico" para começar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection