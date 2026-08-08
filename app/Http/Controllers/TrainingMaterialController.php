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

        // Middleware de permissões
        $this->middleware('permission:trainings,edit')->except('download');
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

        // Log de debug para diagnosticar problemas de upload (arquivo 4.5MB falhando)
        try {
            \Log::info('Upload debug start', [
                'has_file' => $request->hasFile('arquivo'),
                'request_keys' => array_keys($request->all()),
                'php_upload_max' => ini_get('upload_max_filesize'),
                'php_post_max' => ini_get('post_max_size'),
                'php_tmp_dir' => ini_get('upload_tmp_dir'),
                'files_superglobal' => isset($_FILES['arquivo']) ? $_FILES['arquivo'] : null,
            ]);
        } catch (\Exception $e) {
            // Não interromper o fluxo em caso de erro de logging
            \Log::warning('Falha ao logar debug de upload: ' . $e->getMessage());
        }
        // Suporte a upload por chunks: se vierem os campos upload_id e chunk_index
        $isChunk = $request->filled('upload_id') && $request->filled('chunk_index') && $request->hasFile('chunk');

        if ($isChunk) {
            $uploadId = preg_replace('/[^A-Za-z0-9\-_]/', '', $request->input('upload_id'));
            $chunkIndex = (int) $request->input('chunk_index');
            $chunkCount = (int) $request->input('chunk_count');
            $originalName = $request->input('original_name', 'upload.bin');

            $tmpDir = storage_path('app/tmp/uploads');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . $uploadId . '.part';

            $chunkFile = $request->file('chunk');
            if (! $chunkFile->isValid()) {
                return response()->json(['error' => $this->getUploadErrorMessage($chunkFile->getError())], 422);
            }

            // Anexa o conteúdo do chunk ao arquivo temporário
            try {
                $contents = file_get_contents($chunkFile->getRealPath());
                file_put_contents($tmpPath, $contents, FILE_APPEND);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Falha ao gravar chunk: ' . $e->getMessage()], 500);
            }

            // Se for o último chunk, processa como upload completo
            if ($chunkIndex + 1 >= $chunkCount) {
                // Criar uma instância simulada para seguir o fluxo de armazenamento
                try {
                    $stream = fopen($tmpPath, 'r');
                    $filename = basename($originalName);
                    $extensao = pathinfo($filename, PATHINFO_EXTENSION);
                    $tamanho = filesize($tmpPath);

                    $relativePath = "materiais-apoio/training-{$trainingId}/" . uniqid() . '-' . $filename;
                    $fullStoragePath = storage_path('app/public/' . $relativePath);

                    // Garante diretório
                    $dir = dirname($fullStoragePath);
                    if (!is_dir($dir)) mkdir($dir, 0777, true);

                    // Move o arquivo temporário para o storage público
                    rename($tmpPath, $fullStoragePath);

                    // Criar registro no banco
                    $proximaOrdem = TrainingMaterial::where('training_id', $trainingId)->max('ordem') ?? 0;
                    $nomeMaterial = trim((string) $request->input('nome', ''));
                    if ($nomeMaterial === '') {
                        $nomeMaterial = pathinfo($filename, PATHINFO_FILENAME) ?: $filename;
                    }

                    $material = TrainingMaterial::create([
                        'training_id' => $trainingId,
                        'nome' => $nomeMaterial,
                        'descricao' => $request->input('descricao'),
                        'arquivo' => $relativePath,
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
                    return response()->json(['error' => 'Falha ao finalizar upload: ' . $e->getMessage()], 500);
                }
            }

            // Ainda faltam chunks
            return response()->json(['success' => true, 'chunk' => $chunkIndex], 200);
        }

        $validator = \Validator::make($request->all(), [
            'nome' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'arquivo' => 'required|file|max:256000', // 250MB max
        ], [
            'nome.max' => 'O nome do material deve ter no máximo 255 caracteres.',
            'arquivo.required' => 'Selecione um arquivo para o material de apoio.',
            'arquivo.file' => 'O arquivo não pôde ser enviado. Verifique se ele não excede o limite do PHP ou se o upload foi concluído corretamente.',
            'arquivo.uploaded' => 'O arquivo não pôde ser enviado. Verifique se o tamanho permitido no PHP é maior do que o arquivo selecionado.',
            'arquivo.max' => 'O arquivo deve ter no máximo 250 MB.',
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
            $nomeMaterial = trim((string) $request->input('nome', ''));
            if ($nomeMaterial === '') {
                $nomeMaterial = pathinfo($nomeOriginal, PATHINFO_FILENAME) ?: $nomeOriginal;
            }

            $material = TrainingMaterial::create([
                'training_id' => $trainingId,
                'nome' => $nomeMaterial,
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

    private function getUploadErrorMessage(?int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite configurado no PHP (upload_max_filesize). O limite atual é de 250 MB.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite permitido pelo formulário.',
            UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido antes de concluir.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'O servidor não possui diretório temporário para uploads.',
            UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o arquivo temporário.',
            UPLOAD_ERR_EXTENSION => 'O upload foi bloqueado por uma extensão do PHP.',
            default => 'O arquivo não pôde ser enviado. Verifique o limite de upload do servidor.',
        };
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

        if (!$training->isPermittedFor($user->tipo_usuario) && !$user->canAccessTraining($training)) {
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
