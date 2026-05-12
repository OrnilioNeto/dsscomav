<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TrainingController extends Controller
{
    public function __construct()
    {
        // Garantir que a tabela de materiais existe quando o controller for carregado
        $this->ensureTrainingMaterialsTableExists();
        $this->ensureTrainingReleaseColumnExists();
        $this->ensureTrainingMandatoryColumnExists();
    }

    /**
     * Verifica se a tabela training_materials existe,
     * se não existir, a cria automaticamente.
     */
    private function ensureTrainingMaterialsTableExists()
    {
        try {
            if (!Schema::hasTable('training_materials')) {
                // Criar a tabela training_materials
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
            // Se houver erro, apenas registra no log mas não interrompe a execução
            \Log::warning('Erro ao verificar/criar tabela training_materials: ' . $e->getMessage());
        }
    }

    /**
     * Garante que a coluna data_liberacao exista na tabela trainings.
     */
    private function ensureTrainingReleaseColumnExists()
    {
        try {
            if (Schema::hasTable('trainings') && !Schema::hasColumn('trainings', 'data_liberacao')) {
                Schema::table('trainings', function ($table) {
                    $table->dateTime('data_liberacao')->nullable()->after('data_publicacao');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar/criar coluna data_liberacao em trainings: ' . $e->getMessage());
        }
    }

    /**
     * Garante que a coluna obrigatorio exista na tabela trainings.
     */
    private function ensureTrainingMandatoryColumnExists()
    {
        try {
            if (Schema::hasTable('trainings') && !Schema::hasColumn('trainings', 'obrigatorio')) {
                Schema::table('trainings', function ($table) {
                    $table->boolean('obrigatorio')->default(false)->after('status');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar/criar coluna obrigatorio em trainings: ' . $e->getMessage());
        }
    }

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
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));

        $data = [
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido,
            'url_video' => $request->url_video,
            'tipo_video' => $request->tipo_video,
            'carga_horaria' => $request->carga_horaria,
            'obrigatorio' => $request->boolean('obrigatorio'),
            'data_publicacao' => now(),
            'status' => 'ativo',
            'avaliacao_pergunta' => $request->avaliacao_pergunta,
            'avaliacao_opcoes' => $assessments,
            'avaliacao_resposta_correta' => (int) $request->avaliacao_resposta_correta,
        ];

        // Se o cliente enviou data de liberação local, salva como horário de São Paulo
        if ($request->filled('data_liberacao')) {
            try {
                // Parsear como hora local de São Paulo (UTC-3)
                $data['data_liberacao'] = Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->input('data_liberacao'),
                    'America/Sao_Paulo'
                );
            } catch (\Exception $e) {
                // ignorar problema de parse e não definir a data
            }
        }

        $training = Training::create($data);

        // Processar materiais de apoio enviados durante a criação
        if ($request->has('materiais') && is_array($request->input('materiais'))) {
            $ordem = 0;
            foreach ($request->input('materiais') as $index => $materialData) {
                // Verificar se há arquivo para este material
                if ($request->hasFile("materiais.{$index}.arquivo")) {
                    $file = $request->file("materiais.{$index}.arquivo");
                    
                    // Validar o arquivo
                    $fileValidator = Validator::make(
                        ['arquivo' => $file],
                        ['arquivo' => 'required|file|max:102400'] // 100MB max
                    );
                    
                    if ($fileValidator->fails()) {
                        continue; // Pular este arquivo se não passar na validação
                    }
                    
                    // Armazenar o arquivo
                    $storagePath = "materiais-apoio/training-{$training->id}";
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->storeAs($storagePath, $fileName, 'public');
                    
                    // Obter informações do arquivo
                    $fileSize = $file->getSize();
                    $mimeType = $file->getMimeType();
                    
                    // Criar registro no banco de dados
                    TrainingMaterial::create([
                        'training_id' => $training->id,
                        'nome' => $materialData['nome'] ?? 'Material',
                        'descricao' => $materialData['descricao'] ?? null,
                        'arquivo' => $filePath,
                        'tipo_arquivo' => $mimeType,
                        'tamanho' => $fileSize,
                        'ordem' => $ordem++,
                    ]);
                }
            }
        }

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
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'required|string|max:500',
            'avaliacao_opcoes' => 'required|array|min:2',
            'avaliacao_opcoes.*' => 'required|string|max:255',
            'avaliacao_resposta_correta' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));

        $updateData = [
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'tipo' => $request->tipo,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido,
            'carga_horaria' => $request->carga_horaria,
            'status' => $request->status ?? $training->status,
            'obrigatorio' => $request->boolean('obrigatorio'),
            'avaliacao_pergunta' => $request->avaliacao_pergunta,
            'avaliacao_opcoes' => $assessments,
            'avaliacao_resposta_correta' => (int) $request->avaliacao_resposta_correta,
        ];

        if ($request->filled('data_liberacao')) {
            try {
                // Parsear como hora local de São Paulo (UTC-3)
                $updateData['data_liberacao'] = Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $request->input('data_liberacao'),
                    'America/Sao_Paulo'
                );
            } catch (\Exception $e) {
            }
        }

        $training->update($updateData);

        return redirect()->route('treinamentos.show', $training)->with('success', 'Treinamento atualizado!');
    }

    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return redirect()->route('treinamentos.index')->with('success', 'Treinamento deletado!');
    }
}
