<?php

namespace App\Modules\CronogramaConteudo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CronogramaConteudo\Models\CronogramaConteudo;
use Illuminate\Http\Request;

class CronogramaConteudoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cronograma_conteudo,view')->only(['index']);
        $this->middleware('permission:cronograma_conteudo,edit')->except(['index']);
    }

    public function index(Request $request)
    {
        $query = CronogramaConteudo::query()->with('training');

        if ($request->filled('busca')) {
            $query->where('nome', 'like', '%'.$request->input('busca').'%');
        }

        if ($request->filled('status')) {
            // status é calculado; filtramos em memória após carregar
        }

        $itens = $query->orderBy('data_prevista_liberacao')->orderBy('nome')->paginate(30)->withQueryString();

        if ($request->filled('status')) {
            $statusFiltro = $request->input('status');
            $itens->setCollection(
                $itens->getCollection()->filter(fn ($item) => $item->statusLabel() === $statusFiltro)->values()
            );
        }

        $resumo = [
            'total' => CronogramaConteudo::count(),
            'listados' => CronogramaConteudo::all()->filter(fn ($i) => $i->statusLabel() === 'Listado')->count(),
            'previsoes' => CronogramaConteudo::all()->filter(fn ($i) => $i->statusLabel() === 'Previsão')->count(),
            'programados' => CronogramaConteudo::all()->filter(fn ($i) => $i->statusLabel() === 'Programado')->count(),
            'realizados' => CronogramaConteudo::all()->filter(fn ($i) => $i->statusLabel() === 'Realizado')->count(),
        ];

        return view('cronograma_conteudo::index', [
            'itens' => $itens,
            'resumo' => $resumo,
            'statusFiltro' => $request->input('status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'data_prevista_liberacao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
            'training_id' => ['nullable', 'integer', 'exists:trainings,id'],
            'pptx_ok' => ['nullable', 'boolean'],
            'pdf_ok' => ['nullable', 'boolean'],
            'video_ok' => ['nullable', 'boolean'],
            'youtube_ok' => ['nullable', 'boolean'],
        ]);

        foreach (['pptx_ok', 'pdf_ok', 'video_ok', 'youtube_ok'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['created_by'] = $request->user()?->id;

        CronogramaConteudo::create($data);

        return redirect()->route('admin.cronograma.index')->with('success', 'Conteúdo adicionado ao cronograma.');
    }

    public function update(Request $request, CronogramaConteudo $cronograma)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'data_prevista_liberacao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
            'training_id' => ['nullable', 'integer', 'exists:trainings,id'],
            'pptx_ok' => ['nullable', 'boolean'],
            'pdf_ok' => ['nullable', 'boolean'],
            'video_ok' => ['nullable', 'boolean'],
            'youtube_ok' => ['nullable', 'boolean'],
        ]);

        foreach (['pptx_ok', 'pdf_ok', 'video_ok', 'youtube_ok'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $cronograma->update($data);

        return redirect()->route('admin.cronograma.index')->with('success', 'Conteúdo atualizado.');
    }

    public function destroy(CronogramaConteudo $cronograma)
    {
        $cronograma->delete();

        return redirect()->route('admin.cronograma.index')->with('success', 'Conteúdo removido do cronograma.');
    }

    /**
     * Endpoint JSON para listar pendentes (sem treinamento vinculado) — usado no cadastro de treinamento.
     */
    public function pendentes()
    {
        $itens = CronogramaConteudo::query()
            ->orderBy('data_prevista_liberacao')
            ->get()
            ->filter(fn ($item) => $item->matchedTraining() === null)
            ->values();

        return response()->json($itens->map(fn ($item) => [
            'id' => $item->id,
            'nome' => $item->nome,
            'data_prevista_liberacao' => $item->data_prevista_liberacao?->format('Y-m-d'),
        ]));
    }
}