<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RankingCriterion;
use App\Models\RankingRule;
use App\Models\RankingSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RankingSettingsController extends Controller
{
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