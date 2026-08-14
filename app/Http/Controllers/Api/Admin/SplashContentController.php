<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SplashContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SplashContentController extends Controller
{
    private function ensureSplashTableExists()
    {
        if (!Schema::hasTable('splash_contents')) {
            Schema::create('splash_contents', function ($table) {
                $table->id();
                $table->string('titulo');
                $table->text('texto_conteudo')->nullable();
                $table->string('material_path')->nullable();
                $table->string('material_tipo')->nullable();
                $table->date('data_inicio');
                $table->date('data_fim');
                $table->string('status')->default('ativo');
                $table->integer('ordem')->default(0);
                $table->timestamps();
            });
        }
    }

    private function serialize(SplashContent $c): array
    {
        return [
            'id' => $c->id,
            'titulo' => $c->titulo,
            'texto' => $c->texto_conteudo,
            'material_tipo' => $c->material_tipo,
            'material_url' => $c->material_path ? url($c->material_path) : null,
            'data_inicio' => $c->data_inicio,
            'data_fim' => $c->data_fim,
            'status' => $c->status,
            'ordem' => (int) $c->ordem,
        ];
    }

    public function __construct()
    {
        $this->ensureSplashTableExists();
    }

    public function index()
    {
        $contents = SplashContent::orderBy('ordem')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contents->map(fn ($c) => $this->serialize($c))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'titulo' => 'required|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'material' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['titulo', 'texto_conteudo', 'data_inicio', 'data_fim']);
        $data['status'] = 'ativo';
        $data['ordem'] = SplashContent::max('ordem') + 1;

        if ($request->hasFile('material')) {
            $file = $request->file('material');
            $filename = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('uploads/splash'), $filename);
            $data['material_path'] = 'uploads/splash/' . $filename;
            $data['material_tipo'] = strtolower($file->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'imagem';
        }

        $content = SplashContent::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Conteúdo de splash criado com sucesso!',
            'data' => $this->serialize($content),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $content = SplashContent::findOrFail($id);

        $validator = validator($request->all(), [
            'titulo' => 'required|max:255',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $request->only(['titulo', 'texto_conteudo', 'data_inicio', 'data_fim', 'status']);

        if ($request->hasFile('material')) {
            if ($content->material_path && file_exists(public_path($content->material_path))) {
                @unlink(public_path($content->material_path));
            }

            $file = $request->file('material');
            $filename = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('uploads/splash'), $filename);
            $data['material_path'] = 'uploads/splash/' . $filename;
            $data['material_tipo'] = strtolower($file->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'imagem';
        }

        $content->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Conteúdo atualizado com sucesso!',
            'data' => $this->serialize($content),
        ]);
    }

    public function destroy($id)
    {
        $content = SplashContent::findOrFail($id);

        if ($content->material_path && file_exists(public_path($content->material_path))) {
            @unlink(public_path($content->material_path));
        }

        $content->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Conteúdo excluído permanentemente.',
        ]);
    }

    public function toggleStatus($id)
    {
        $content = SplashContent::findOrFail($id);
        $content->status = $content->status === 'ativo' ? 'inativo' : 'ativo';
        $content->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status alterado com sucesso!',
            'data' => ['status' => $content->status],
        ]);
    }
}
