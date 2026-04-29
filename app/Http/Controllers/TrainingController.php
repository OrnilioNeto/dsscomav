<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    public function index()
    {
        $treinamentos = Training::paginate(15);
        return view('treinamentos.index', compact('treinamentos'));
    }

    public function create()
    {
        return view('treinamentos.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'tipo_usuario_permitido' => 'required|array',
            'url_video' => 'required|url',
            'tipo_video' => 'required|in:youtube,vimeo,upload',
            'carga_horaria' => 'required|integer|min:1',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));

        Training::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido,
            'url_video' => $request->url_video,
            'tipo_video' => $request->tipo_video,
            'carga_horaria' => $request->carga_horaria,
            'data_publicacao' => now(),
            'status' => 'ativo',
            'avaliacao_pergunta' => $request->avaliacao_pergunta,
            'avaliacao_opcoes' => $assessments,
            'avaliacao_resposta_correta' => (int) $request->avaliacao_resposta_correta,
        ]);

        return redirect()->route('treinamentos.index')->with('success', 'Treinamento criado!');
    }

    public function show($id)
    {
        $training = Training::findOrFail($id);
        return view('treinamentos.show', compact('training'));
    }

    public function edit($id)
    {
        $training = Training::findOrFail($id);
        return view('treinamentos.edit', compact('training'));
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'tipo_usuario_permitido' => 'required|array',
            'carga_horaria' => 'required|integer|min:1',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));

        $training->update([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido,
            'carga_horaria' => $request->carga_horaria,
            'status' => $request->status ?? $training->status,
            'avaliacao_pergunta' => $request->avaliacao_pergunta,
            'avaliacao_opcoes' => $assessments,
            'avaliacao_resposta_correta' => (int) $request->avaliacao_resposta_correta,
        ]);

        return redirect()->route('treinamentos.show', $training)->with('success', 'Treinamento atualizado!');
    }

    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return redirect()->route('treinamentos.index')->with('success', 'Treinamento deletado!');
    }
}
