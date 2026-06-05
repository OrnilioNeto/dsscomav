<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RankingCriterion;
use App\Models\RankingRule;
use App\Models\RankingSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class RankingSettingsController extends Controller
{
    public function __construct()
    {
        $this->ensureRankingTablesExist();
    }

    /**
     * Verifica se as tabelas de parâmetros e dados de ranking existem, se não, as cria.
     */
    private function ensureRankingTablesExist()
    {
        // 1. Tabela de Configurações Gerais
        if (!Schema::hasTable('ranking_settings')) {
            Schema::create('ranking_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_active')->default(true);
                $table->string('default_period')->default('monthly');
                $table->timestamps();
            });
        }

        // 2. Tabela de Critérios (Início, Conclusão, Quiz)
        if (!Schema::hasTable('ranking_criteria')) {
            Schema::create('ranking_criteria', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Popular com os critérios base se a tabela foi recém criada
            DB::table('ranking_criteria')->insert([
                [
                    'name' => 'Velocidade de Início', 
                    'slug' => 'start_time', 
                    'description' => 'Tempo entre a liberação do conteúdo e o início da assistência (horas).', 
                    'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()
                ],
                [
                    'name' => 'Tempo de Conclusão', 
                    'slug' => 'completion_time', 
                    'description' => 'Tempo total para concluir o treinamento após o início (dias).', 
                    'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()
                ],
                [
                    'name' => 'Resultado do Quiz', 
                    'slug' => 'quiz_result', 
                    'description' => 'Performance na avaliação final baseada em tentativas.', 
                    'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()
                ],
            ]);
        }

        // 3. Tabela de Regras de Pontuação
        if (!Schema::hasTable('ranking_rules')) {
            Schema::create('ranking_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('criterion_id');
                $table->string('label');
                $table->float('min_value')->nullable();
                $table->float('max_value')->nullable();
                $table->integer('points')->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('criterion_id')->references('id')->on('ranking_criteria')->onDelete('cascade');
            });
        }

        // 4. Tabelas de Resultados (Onde o recálculo salva os dados)
        if (!Schema::hasTable('ranking_scores')) {
            Schema::create('ranking_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('training_id');
                $table->float('raw_score');
                $table->integer('month_reference');
                $table->integer('year_reference');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ranking_monthly_scores')) {
            Schema::create('ranking_monthly_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->float('average_score');
                $table->integer('month_reference');
                $table->integer('year_reference');
                $table->timestamps();
            });
        }
    }

    /**
     * Exibe a página de configurações do ranking.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Garante que sempre há uma entrada de configurações gerais do ranking
        $settings = RankingSetting::firstOrCreate([]);
        // Carrega todos os critérios de ranking com suas regras associadas, ordenados
        $criteria = RankingCriterion::with('rules')->orderBy('sort_order')->get();

        return view('admin.ranking.settings', compact('settings', 'criteria'));
    }

    /**
     * Atualiza as configurações gerais do ranking.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Valida os dados da requisição
        $request->validate([
            'is_active' => 'required|boolean', // Se o ranking está ativo ou inativo
            'default_period' => ['required', Rule::in(['monthly', 'content'])], // Período padrão para exibição
        ]);

        // Busca ou cria a entrada de configurações e a atualiza
        $settings = RankingSetting::firstOrCreate([]);
        $settings->update($request->only(['is_active', 'default_period']));

        // Redireciona de volta com mensagem de sucesso
        return redirect()->route('admin.ranking.settings')->with('success', 'Configurações gerais do ranking atualizadas com sucesso!');
    }

    /**
     * Armazena uma nova regra de pontuação para um critério específico.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RankingCriterion $criterion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRule(Request $request, RankingCriterion $criterion)
    {
        // Valida os dados da requisição para a nova regra
        $request->validate([
            'label' => 'required|string|max:255', // Rótulo da regra (ex: "Elite (até 1h)")
            'min_value' => 'nullable|numeric', // Valor mínimo da faixa
            'max_value' => 'nullable|numeric|gte:min_value', // Valor máximo da faixa (deve ser maior ou igual ao mínimo)
            'points' => 'required|integer|min:0', // Pontos atribuídos a esta regra
            'sort_order' => 'nullable|integer|min:0', // Ordem de exibição/aplicação da regra
        ]);

        // Cria a nova regra associada ao critério
        $criterion->rules()->create($request->all());

        // Redireciona de volta com mensagem de sucesso
        return redirect()->route('admin.ranking.settings')->with('success', 'Regra adicionada com sucesso!');
    }

    /**
     * Atualiza uma regra de pontuação existente.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RankingRule $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRule(Request $request, RankingRule $rule)
    {
        // Valida os dados da requisição para a atualização da regra
        $request->validate([
            'label' => 'required|string|max:255',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric|gte:min_value',
            'points' => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Atualiza a regra
        $rule->update($request->all());

        // Redireciona de volta com mensagem de sucesso
        return redirect()->route('admin.ranking.settings')->with('success', 'Regra atualizada com sucesso!');
    }

    /**
     * Remove uma regra de pontuação existente.
     *
     * @param \App\Models\RankingRule $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyRule(RankingRule $rule)
    {
        // Remove a regra
        $rule->delete();

        // Redireciona de volta com mensagem de sucesso
        return redirect()->route('admin.ranking.settings')->with('success', 'Regra removida com sucesso!');
    }
}