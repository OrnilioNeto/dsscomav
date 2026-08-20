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
                Projetos pedagógicos dos treinamentos (NR-01 Anexo II 3.1). Documento administrativo disponível à fiscalização, sindicato, CIPA e contratantes (4.1/4.1.1).
            </p>
        </div>
        <a href="{{ route('treinamentos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-300 text-center">
            <i class="fas fa-arrow-left mr-1"></i> Voltar aos Treinamentos
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm font-semibold">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Treinamento</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Preenchimento</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Validação</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Próxima revisão</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($treinamentos as $treinamento)
                        @php
                            $pp = $treinamento->projetoPedagogico;
                            $status = $pp ? $pp->status_revisao : 'pendente';
                            $statusCfg = [
                                'ok' => ['bg-emerald-100 text-emerald-800', 'OK'],
                                'proxima' => ['bg-amber-100 text-amber-800', 'Revisão próxima'],
                                'vencida' => ['bg-rose-100 text-rose-800', 'Revisão vencida'],
                                'sem_revisao' => ['bg-gray-100 text-gray-600', 'Sem data de revisão'],
                                'pendente' => ['bg-rose-100 text-rose-800', 'Pendente'],
                            ][$status];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900">{{ $treinamento->titulo }}</div>
                                @if($treinamento->tipo_treinamento)
                                    <span class="text-xs text-gray-400">{{ ucfirst($treinamento->tipo_treinamento) }} · {{ $treinamento->carga_horaria }} min</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $treinamento->tipo === 'treinamento' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                                    {{ strtoupper($treinamento->tipo) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($pp)
                                    <div class="flex items-center gap-2 justify-center">
                                        <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full {{ $pp->percentual_preenchimento === 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $pp->percentual_preenchimento }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-600">{{ $pp->percentual_preenchimento }}%</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">
                                {{ $pp?->data_validacao?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">
                                {{ $pp?->data_proxima_revisao?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusCfg[0] }}">{{ $statusCfg[1] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('projetos-pedagogicos.edit', $treinamento) }}" class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg hover:bg-blue-100 border border-blue-200 mr-1">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                                @if($pp)
                                    <a href="{{ route('projetos-pedagogicos.download', $treinamento) }}" class="inline-flex items-center px-2.5 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-100 border border-emerald-200 mr-1" title="Baixar PDF padrão do projeto pedagógico">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                    @if($pp->arquivo_pdf)
                                        <a href="{{ route('projetos-pedagogicos.download-arquivo', $treinamento) }}" class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-200" title="Baixar documento assinado">
                                            <i class="fas fa-file-signature mr-1"></i> Assinado
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">Nenhum treinamento cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection