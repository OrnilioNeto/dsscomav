<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TrainingMaterialController extends Controller
{
    public function __construct()
    {
        // Garantir que a tabela de materiais existe quando o controller for carregado
        $this->ensureTrainingMaterialsTableExists();
    }

    /**
     * Verifica se a tabela training_materials existe,
     * se não existir, a cria automaticamente.
     */
    private function ensureTrainingMaterialsTableExists()
    {
        try {
            if (!Schema::hasTable('training_materials')) {
                Schema::create('training_materials', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('training_id');
                    $table->string('nome');
                    $table->text('descricao')->nullable();
                    $table->string('arquivo');
                    $table->string('tipo_arquivo');
                    $table->unsignedBigInteger('tamanho');
                    $table->integer('ordem')->default(0);
                    $table->timestamps();

                    $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                    $table->index('training_id');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar/criar tabela training_materials: ' . $e->getMessage());
        }
    }

    // Upload de material de apoio
    public function upload(Request $request, $trainingId)
    {
        // Validar que o usuário é admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $training = Training::findOrFail($trainingId);

        $validator = \Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'arquivo' => 'required|file|max:102400', // 100MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $arquivo = $request->file('arquivo');
            $nomeOriginal = $arquivo->getClientOriginalName();
            $extensao = $arquivo->getClientOriginalExtension();
            $tamanho = $arquivo->getSize();
            
            // Armazenar o arquivo
            $caminhoArmazenado = $arquivo->store(
                "materiais-apoio/training-{$trainingId}",
                'public'
            );

            // Obter a maior ordem existente e adicionar 1
            $proximaOrdem = TrainingMaterial::where('training_id', $trainingId)->max('ordem') ?? 0;

            // Criar registro no banco de dados
            $material = TrainingMaterial::create([
                'training_id' => $trainingId,
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'arquivo' => $caminhoArmazenado,
                'tipo_arquivo' => $extensao,
                'tamanho' => $tamanho,
                'ordem' => $proximaOrdem + 1,
            ]);

            return response()->json([
                'success' => true,
                'material' => $material,
                'icone' => $material->getIcone(),
                'tamanho_formatado' => $material->getTamanhoFormatado(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Deletar material
    public function delete($materialId)
    {
        // Validar que o usuário é admin
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $material = TrainingMaterial::findOrFail($materialId);

        try {
            // Deletar arquivo do storage
            if (Storage::disk('public')->exists($material->arquivo)) {
                Storage::disk('public')->delete($material->arquivo);
            }

            // Deletar registro do banco
            $material->delete();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Download de material (apenas usuários autenticados)
    public function download($materialId)
    {
        $material = TrainingMaterial::findOrFail($materialId);

        // Verificar se o usuário tem acesso ao treinamento
        $training = $material->training;
        $user = Auth::user();

        if (!$training->isPermittedFor($user->tipo_usuario)) {
            return redirect()->back()->with('error', 'Você não tem acesso a este material.');
        }

        // Verificar se o arquivo existe
        if (!Storage::disk('public')->exists($material->arquivo)) {
            return redirect()->back()->with('error', 'Arquivo não encontrado.');
        }

        // Fazer download do arquivo físico armazenado
        $fullPath = Storage::disk('public')->path($material->arquivo);
        $downloadName = basename($material->arquivo);

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => Storage::disk('public')->mimeType($material->arquivo) ?: 'application/octet-stream',
        ]);
    }

    // Atualizar ordem dos materiais (AJAX)
    public function updateOrder(Request $request, $trainingId)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'ordem' => 'required|array',
            'ordem.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            foreach ($request->ordem as $index => $materialId) {
                TrainingMaterial::where('id', $materialId)
                    ->where('training_id', $trainingId)
                    ->update(['ordem' => $index + 1]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
