<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    private function serialize(Training $training): array
    {
        $training->loadMissing('materials');

        return [
            'id' => $training->id,
            'titulo' => $training->titulo,
            'descricao' => $training->descricao,
            'tipo' => $training->tipo,
            'tipo_usuario_permitido' => $training->tipo_usuario_permitido,
            'url_video' => $training->url_video,
            'tipo_video' => $training->tipo_video,
            'carga_horaria' => (int) $training->carga_horaria,
            'thumbnail' => $training->thumbnail,
            'data_publicacao' => $training->data_publicacao?->toISOString(),
            'data_liberacao' => $training->data_liberacao?->toISOString(),
            'status' => $training->status,
            'obrigatorio' => (bool) $training->obrigatorio,
            'avaliacao_pergunta' => $training->avaliacao_pergunta,
            'avaliacao_opcoes' => $training->avaliacao_opcoes,
            'avaliacao_resposta_correta' => $training->avaliacao_resposta_correta,
            'assigned_users' => $training->assignedUsers()->pluck('users.id'),
            'materiais' => $training->materials->map(fn ($m) => [
                'id' => $m->id,
                'nome' => $m->nome,
                'descricao' => $m->descricao,
                'arquivo' => $m->arquivo,
                'tipo_arquivo' => $m->tipo_arquivo,
                'tamanho' => (int) ($m->tamanho ?? 0),
                'ordem' => (int) ($m->ordem ?? 0),
                'url_download' => url("/api/v1/materials/{$m->id}/download"),
            ])->values(),
        ];
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $tipo = $request->input('tipo');
        $perPage = (int) $request->input('per_page', 20);

        $query = Training::with('materials');

        if ($search) {
            $query->where('titulo', 'like', '%' . $search . '%');
        }

        if ($tipo && in_array($tipo, ['dss', 'treinamento'], true)) {
            $query->where('tipo', $tipo);
        }

        $trainings = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => collect($trainings->items())->map(fn ($t) => $this->serialize($t))->values(),
            'meta' => [
                'total' => $trainings->total(),
                'per_page' => $trainings->perPage(),
                'current_page' => $trainings->currentPage(),
                'last_page' => $trainings->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $training = Training::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->serialize($training),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'url_video' => 'required|url',
            'tipo_video' => 'required|in:youtube,vimeo,upload',
            'carga_horaria' => 'required|integer|min:1',
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
            'data_liberacao' => 'nullable|date_format:Y-m-d\TH:i',
            'funcionarios' => 'required_if:tipo,treinamento|array',
            'funcionarios.*' => 'exists:users,id',
            'tipo_usuario_permitido' => 'required_if:tipo,dss|array',
            'tipo_usuario_permitido.*' => 'in:motorista,funcionario,terceirizado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'titulo', 'descricao', 'tipo', 'url_video', 'tipo_video',
            'carga_horaria', 'obrigatorio', 'avaliacao_pergunta',
            'avaliacao_opcoes', 'avaliacao_resposta_correta',
        ]);

        $data['status'] = 'ativo';
        $data['data_publicacao'] = now();
        $data['obrigatorio'] = $request->boolean('obrigatorio');
        $data['avaliacao_resposta_correta'] = (int) $data['avaliacao_resposta_correta'];

        if ($request->has('data_liberacao') && $request->filled('data_liberacao')) {
            try {
                $data['data_liberacao'] = \Carbon\Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->input('data_liberacao'),
                    'America/Sao_Paulo'
                );
            } catch (\Throwable $e) {
                // falha silenciosa, igual ao web
            }
        }

        if ($request->input('tipo') === 'dss') {
            $data['tipo_usuario_permitido'] = $request->input('tipo_usuario_permitido');
        } else {
            $data['tipo_usuario_permitido'] = null;
        }

        $training = Training::create($data);

        if ($request->input('tipo') === 'treinamento' && $request->has('funcionarios')) {
            $training->assignedUsers()->sync($request->input('funcionarios'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento criado!',
            'data' => $this->serialize($training),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'carga_horaria' => 'required|integer|min:1',
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
            'data_liberacao' => 'nullable|date_format:Y-m-d\TH:i',
            'funcionarios' => 'required_if:tipo,treinamento|array',
            'funcionarios.*' => 'exists:users,id',
            'tipo_usuario_permitido' => 'required_if:tipo,dss|array',
            'tipo_usuario_permitido.*' => 'in:motorista,funcionario,terceirizado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'titulo', 'descricao', 'tipo', 'carga_horaria', 'obrigatorio',
            'avaliacao_pergunta', 'avaliacao_opcoes', 'avaliacao_resposta_correta',
        ]);

        $data['obrigatorio'] = $request->boolean('obrigatorio');
        $data['avaliacao_resposta_correta'] = (int) $data['avaliacao_resposta_correta'];

        if ($request->has('data_liberacao') && $request->filled('data_liberacao')) {
            try {
                $data['data_liberacao'] = \Carbon\Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->input('data_liberacao'),
                    'America/Sao_Paulo'
                );
            } catch (\Throwable $e) {
            }
        } else {
            $data['data_liberacao'] = null;
        }

        if ($request->input('tipo') === 'dss') {
            $data['tipo_usuario_permitido'] = $request->input('tipo_usuario_permitido');
        } else {
            $data['tipo_usuario_permitido'] = null;
        }

        $training->update($data);

        if ($request->input('tipo') === 'treinamento' && $request->has('funcionarios')) {
            $training->assignedUsers()->sync($request->input('funcionarios'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento atualizado!',
            'data' => $this->serialize($training),
        ]);
    }

    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento deletado!',
        ]);
    }

    public function toggleStatus($id)
    {
        $training = Training::findOrFail($id);
        $training->update([
            'status' => $training->status === 'ativo' ? 'inativo' : 'ativo',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status do treinamento alterado!',
            'data' => ['status' => $training->status],
        ]);
    }

    public function uploadMaterial(Request $request, $trainingId)
    {
        $training = Training::findOrFail($trainingId);

        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|max:256000',
            'nome' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $file = $request->file('arquivo');
        $path = $file->store("materiais-apoio/training-{$training->id}", 'public');

        $material = TrainingMaterial::create([
            'training_id' => $training->id,
            'nome' => $request->input('nome', $file->getClientOriginalName()),
            'descricao' => $request->input('descricao'),
            'arquivo' => $path,
            'tipo_arquivo' => strtolower($file->getClientOriginalExtension() ?: 'arquivo'),
            'tamanho' => $file->getSize(),
            'ordem' => TrainingMaterial::where('training_id', $training->id)->count() + 1,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Material enviado com sucesso!',
            'data' => $material,
        ], 201);
    }

    public function destroyMaterial($materialId)
    {
        $material = TrainingMaterial::findOrFail($materialId);

        Storage::disk('public')->delete($material->arquivo);

        $material->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Material excluído!',
        ]);
    }
}
