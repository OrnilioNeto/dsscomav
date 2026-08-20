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
        $this->ensureTrainingAssignmentsTableExists();
        $this->ensureTrainingComplianceColumnsExist();
        $this->ensureTrainingQuestionsTableExists();

        // Middleware de permissões
        $this->middleware('permission:trainings,view')->only(['index', 'show']);
        $this->middleware('permission:trainings,edit')->except(['index', 'show']);
    }

    /**
     * Garante que a tabela training_assignments exista,
     * se não existir, a cria automaticamente.
     */
    private function ensureTrainingAssignmentsTableExists()
    {
        try {
            if (!Schema::hasTable('training_assignments')) {
                Schema::create('training_assignments', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('training_id');
                    $table->unsignedBigInteger('user_id');
                    $table->timestamps();

                    $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->unique(['training_id', 'user_id']);
                    $table->index('user_id');
                });
            }
        } catch (\Exception $e) {
            // Se houver erro, apenas registra no log mas não interrompe a execução
            \Log::warning('Erro ao verificar/criar tabela training_assignments: ' . $e->getMessage());
        }
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

    /**
     * Garante as colunas de conformidade NR-01 (ementa, tipo, validade e prova).
     */
    private function ensureTrainingComplianceColumnsExist()
    {
        try {
            if (Schema::hasTable('trainings')) {
                if (!Schema::hasColumn('trainings', 'conteudo_programatico')) {
                    Schema::table('trainings', function ($table) {
                        $table->text('conteudo_programatico')->nullable()->after('descricao');
                    });
                }
                if (!Schema::hasColumn('trainings', 'tipo_treinamento')) {
                    Schema::table('trainings', function ($table) {
                        $table->string('tipo_treinamento', 20)->nullable()->after('tipo');
                    });
                }
                if (!Schema::hasColumn('trainings', 'dias_validade')) {
                    Schema::table('trainings', function ($table) {
                        $table->unsignedInteger('dias_validade')->nullable()->after('carga_horaria');
                    });
                }
                if (!Schema::hasColumn('trainings', 'quantidade_questoes_prova')) {
                    Schema::table('trainings', function ($table) {
                        $table->unsignedTinyInteger('quantidade_questoes_prova')->nullable()->after('avaliacao_resposta_correta');
                    });
                }
                if (!Schema::hasColumn('trainings', 'nota_minima_aprovacao')) {
                    Schema::table('trainings', function ($table) {
                        $table->unsignedTinyInteger('nota_minima_aprovacao')->default(70)->after('quantidade_questoes_prova');
                    });
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar/criar colunas de conformidade em trainings: ' . $e->getMessage());
        }
    }

    /**
     * Garante que a tabela training_questions exista.
     */
    private function ensureTrainingQuestionsTableExists()
    {
        try {
            if (!Schema::hasTable('training_questions')) {
                Schema::create('training_questions', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('training_id');
                    $table->text('pergunta');
                    $table->json('opcoes');
                    $table->unsignedTinyInteger('resposta_correta');
                    $table->unsignedTinyInteger('ordem')->default(0);
                    $table->timestamps();

                    $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                    $table->index('training_id');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Erro ao verificar/criar tabela training_questions: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $treinamentos = Training::paginate(15);
        return view('treinamentos.index', compact('treinamentos'));
    }

    public function create()
    {
        $funcionarios = $this->getAssignableUsers();

        return view('treinamentos.create', compact('funcionarios'));
    }

    public function store(Request $request)
    {
        $rules = [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'conteudo_programatico' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'tipo_treinamento' => 'required_if:tipo,treinamento|nullable|in:inicial,periodico,eventual',
            'url_video' => 'required|url',
            'tipo_video' => 'required|in:youtube,vimeo,upload',
            'carga_horaria' => 'required|integer|min:1',
            'dias_validade' => 'nullable|integer|min:1',
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'nullable|string|max:500',
            'avaliacao_opcoes' => 'nullable|array|min:2',
            'avaliacao_opcoes.*' => 'nullable|string|max:255',
            'avaliacao_resposta_correta' => 'nullable|integer|min:0',
            'quantidade_questoes_prova' => 'nullable|integer|min:1|max:10',
            'nota_minima_aprovacao' => 'nullable|integer|between:1,100',
            'questoes' => 'nullable|array|max:10',
            'questoes.*.pergunta' => 'required_with:questoes|string|max:500',
            'questoes.*.opcoes' => 'required_with:questoes|array|min:2|max:4',
            'questoes.*.opcoes.*' => 'required|string|max:255',
            'questoes.*.resposta_correta' => 'required_with:questoes|integer|min:0',
        ];

        // Treinamento direcionado: atribuições opcionais no cadastro (pode preencher depois)
        if ($request->input('tipo') === 'treinamento') {
            $rules['funcionarios'] = 'nullable|array';
            $rules['funcionarios.*'] = 'exists:users,id';
        } else {
            $rules['tipo_usuario_permitido'] = 'required|array';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));
        $questoesBanco = $this->normalizeQuestions($request);

        // Para treinamento é obrigatório haver avaliação: banco de questões OU pergunta única legada.
        if ($request->input('tipo') === 'treinamento') {
            $temLegado = !empty(trim((string) $request->avaliacao_pergunta)) && count($assessments) >= 2;
            if (empty($questoesBanco) && !$temLegado) {
                return redirect()->back()
                    ->withErrors(['questoes' => 'Cadastre pelo menos uma questão no banco de questões (ou preencha a pergunta única).'])
                    ->withInput();
            }
        }

        // Para DSS é obrigatório o formato de pergunta única (legado).
        if ($request->input('tipo') === 'dss') {
            $temLegado = !empty(trim((string) $request->avaliacao_pergunta))
                && count($assessments) >= 2
                && $request->filled('avaliacao_resposta_correta');
            if (!$temLegado) {
                return redirect()->back()
                    ->withErrors(['avaliacao_pergunta' => 'Para DSS é obrigatório preencher a pergunta, ao menos 2 opções e a resposta correta.'])
                    ->withInput();
            }
        }

        $data = [
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'conteudo_programatico' => $request->conteudo_programatico,
            'tipo' => $request->tipo,
            'tipo_treinamento' => $request->input('tipo') === 'treinamento' ? $request->tipo_treinamento : null,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido ?? ['motorista', 'funcionario', 'terceirizado'],
            'url_video' => $request->url_video,
            'tipo_video' => $request->tipo_video,
            'carga_horaria' => $request->carga_horaria,
            'dias_validade' => $request->filled('dias_validade') ? (int) $request->dias_validade : null,
            'obrigatorio' => $request->boolean('obrigatorio'),
            'data_publicacao' => now(),
            'status' => 'ativo',
            'avaliacao_pergunta' => !empty($questoesBanco) ? null : $request->avaliacao_pergunta,
            'avaliacao_opcoes' => !empty($questoesBanco) ? null : $assessments,
            'avaliacao_resposta_correta' => !empty($questoesBanco) ? null : (int) $request->avaliacao_resposta_correta,
            'quantidade_questoes_prova' => $request->filled('quantidade_questoes_prova') ? (int) $request->quantidade_questoes_prova : null,
            'nota_minima_aprovacao' => $request->filled('nota_minima_aprovacao') ? (int) $request->nota_minima_aprovacao : 70,
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

        $this->syncQuestions($training, $questoesBanco);

        // Gravar funcionários direcionados (somente para tipo treinamento)
        if ($training->tipo === 'treinamento') {
            $training->assignedUsers()->sync($request->input('funcionarios', []));
        }

        // Processar materiais de apoio enviados durante a criação
        // Importante: quando nome/descrição são opcionais, pode não existir
        // payload em input('materiais'), mas os arquivos ainda chegam em file('materiais').
        $materiaisArquivos = $request->file('materiais', []);
        if (is_array($materiaisArquivos) && !empty($materiaisArquivos)) {
            $ordem = 0;

            \Log::info('Create training: materiais recebidos', [
                'training_id' => $training->id,
                'count' => count($materiaisArquivos),
                'indices' => array_keys($materiaisArquivos),
            ]);

            foreach ($materiaisArquivos as $index => $materialArquivoData) {
                $file = is_array($materialArquivoData) ? ($materialArquivoData['arquivo'] ?? null) : null;

                if (!$file) {
                    continue;
                }

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

                // Obter informações do arquivo e metadados opcionais
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                $materialData = $request->input("materiais.{$index}", []);
                $nomeMaterial = trim((string) ($materialData['nome'] ?? ''));
                if ($nomeMaterial === '') {
                    $nomeMaterial = pathinfo($fileName, PATHINFO_FILENAME) ?: $fileName;
                }

                // Criar registro no banco de dados
                TrainingMaterial::create([
                    'training_id' => $training->id,
                    'nome' => $nomeMaterial,
                    'descricao' => $materialData['descricao'] ?? null,
                    'arquivo' => $filePath,
                    'tipo_arquivo' => $mimeType,
                    'tamanho' => $fileSize,
                    'ordem' => $ordem++,
                ]);

                \Log::info('Create training: material salvo', [
                    'training_id' => $training->id,
                    'arquivo' => $filePath,
                    'nome' => $nomeMaterial,
                    'tamanho' => $fileSize,
                ]);
            }
        }

        return redirect()->route('treinamentos.index')->with('success', 'Treinamento criado!');
    }

    public function show($id)
    {
        $training = Training::with('materials', 'assignedUsers')->findOrFail($id);
        return view('treinamentos.show', compact('training'));
    }

    public function edit($id)
    {
        $training = Training::with('materials', 'assignedUsers', 'questions')->findOrFail($id);
        $funcionarios = $this->getAssignableUsers();

        return view('treinamentos.edit', compact('training', 'funcionarios'));
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $rules = [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'conteudo_programatico' => 'nullable|string',
            'tipo' => 'required|in:dss,treinamento',
            'tipo_treinamento' => 'required_if:tipo,treinamento|nullable|in:inicial,periodico,eventual',
            'carga_horaria' => 'required|integer|min:1',
            'dias_validade' => 'nullable|integer|min:1',
            'obrigatorio' => 'nullable|boolean',
            'avaliacao_pergunta' => 'nullable|string|max:500',
            'avaliacao_opcoes' => 'nullable|array|min:2',
            'avaliacao_opcoes.*' => 'nullable|string|max:255',
            'avaliacao_resposta_correta' => 'nullable|integer|min:0',
            'quantidade_questoes_prova' => 'nullable|integer|min:1|max:10',
            'nota_minima_aprovacao' => 'nullable|integer|between:1,100',
            'questoes' => 'nullable|array|max:10',
            'questoes.*.pergunta' => 'required_with:questoes|string|max:500',
            'questoes.*.opcoes' => 'required_with:questoes|array|min:2|max:4',
            'questoes.*.opcoes.*' => 'required|string|max:255',
            'questoes.*.resposta_correta' => 'required_with:questoes|integer|min:0',
        ];

        // Treinamento direcionado: atribuições opcionais no cadastro (pode preencher depois)
        if ($request->input('tipo') === 'treinamento') {
            $rules['funcionarios'] = 'nullable|array';
            $rules['funcionarios.*'] = 'exists:users,id';
        } else {
            $rules['tipo_usuario_permitido'] = 'required|array';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assessments = array_values(array_filter($request->avaliacao_opcoes ?? []));
        $questoesBanco = $this->normalizeQuestions($request);

        // Para treinamento é obrigatório haver avaliação: banco de questões OU pergunta única legada.
        if ($request->input('tipo') === 'treinamento') {
            $temLegado = !empty(trim((string) $request->avaliacao_pergunta)) && count($assessments) >= 2;
            if (empty($questoesBanco) && !$temLegado) {
                return redirect()->back()
                    ->withErrors(['questoes' => 'Cadastre pelo menos uma questão no banco de questões (ou preencha a pergunta única).'])
                    ->withInput();
            }
        }

        // Para DSS é obrigatório o formato de pergunta única (legado).
        if ($request->input('tipo') === 'dss') {
            $temLegado = !empty(trim((string) $request->avaliacao_pergunta))
                && count($assessments) >= 2
                && $request->filled('avaliacao_resposta_correta');
            if (!$temLegado) {
                return redirect()->back()
                    ->withErrors(['avaliacao_pergunta' => 'Para DSS é obrigatório preencher a pergunta, ao menos 2 opções e a resposta correta.'])
                    ->withInput();
            }
        }

        $updateData = [
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'conteudo_programatico' => $request->conteudo_programatico,
            'tipo' => $request->tipo,
            'tipo_treinamento' => $request->input('tipo') === 'treinamento' ? $request->tipo_treinamento : null,
            'tipo_usuario_permitido' => $request->tipo_usuario_permitido ?? $training->tipo_usuario_permitido,
            'carga_horaria' => $request->carga_horaria,
            'dias_validade' => $request->filled('dias_validade') ? (int) $request->dias_validade : null,
            'status' => $request->status ?? $training->status,
            'obrigatorio' => $request->boolean('obrigatorio'),
            'avaliacao_pergunta' => !empty($questoesBanco) ? null : $request->avaliacao_pergunta,
            'avaliacao_opcoes' => !empty($questoesBanco) ? null : $assessments,
            'avaliacao_resposta_correta' => !empty($questoesBanco) ? null : (int) $request->avaliacao_resposta_correta,
            'quantidade_questoes_prova' => $request->filled('quantidade_questoes_prova') ? (int) $request->quantidade_questoes_prova : null,
            'nota_minima_aprovacao' => $request->filled('nota_minima_aprovacao') ? (int) $request->nota_minima_aprovacao : 70,
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

        $this->syncQuestions($training, $questoesBanco);

        // Sincronizar funcionários direcionados (limpa se virar DSS)
        if ($training->tipo === 'treinamento') {
            $training->assignedUsers()->sync($request->input('funcionarios', []));
        } else {
            $training->assignedUsers()->sync([]);
        }

        return redirect()->route('treinamentos.show', $training)->with('success', 'Treinamento atualizado!');
    }

    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return redirect()->route('treinamentos.index')->with('success', 'Treinamento deletado!');
    }

    /**
     * Normaliza as questões do banco de questões vindas do formulário:
     * remove vazias, reindexa e valida coerência da resposta correta.
     */
    private function normalizeQuestions(Request $request): array
    {
        $questoes = $request->input('questoes', []);
        if (!is_array($questoes)) {
            return [];
        }

        $normalizadas = [];
        foreach (array_values($questoes) as $q) {
            if (!is_array($q)) {
                continue;
            }

            $pergunta = trim((string) ($q['pergunta'] ?? ''));
            $opcoes = array_values(array_filter($q['opcoes'] ?? [], function ($o) {
                return trim((string) $o) !== '';
            }));
            $correta = isset($q['resposta_correta']) ? (int) $q['resposta_correta'] : null;

            if ($pergunta === '' || count($opcoes) < 2 || count($opcoes) > 4) {
                continue;
            }

            if ($correta === null || $correta < 0 || $correta >= count($opcoes)) {
                continue;
            }

            $normalizadas[] = [
                'pergunta' => $pergunta,
                'opcoes' => $opcoes,
                'resposta_correta' => $correta,
            ];
        }

        return $normalizadas;
    }

    /**
     * Substitui o banco de questões de um treinamento.
     */
    private function syncQuestions(Training $training, array $questoes): void
    {
        $training->questions()->delete();

        foreach ($questoes as $i => $q) {
            \App\Models\TrainingQuestion::create([
                'training_id' => $training->id,
                'pergunta' => $q['pergunta'],
                'opcoes' => $q['opcoes'],
                'resposta_correta' => $q['resposta_correta'],
                'ordem' => $i,
            ]);
        }

        // Garante que a quantidade de questões da prova não exceda o banco cadastrado
        if ($training->quantidade_questoes_prova && count($questoes) > 0 && $training->quantidade_questoes_prova > count($questoes)) {
            $training->update(['quantidade_questoes_prova' => count($questoes)]);
        }
    }

    /**
     * Usuários que podem receber um treinamento direcionado:
     * ativos, que não são super_admin (inclui usuários de teste).
     */
    private function getAssignableUsers()
    {
        return \App\Models\User::where('status', 'ativo')
            ->where(function ($query) {
                $query->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->where('nome', '<>', 'super_admin');
                    })
                    ->orWhere('participa_treinamentos', true);
            })
            ->orderBy('nome')
            ->get();
    }
}
