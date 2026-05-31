<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RankingSetting;
use App\Models\RankingCriterion;
use App\Models\RankingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingSettingsController extends Controller
{
    public function index()
    {
        $settings = RankingSetting::first() ?: RankingSetting::create([
            'is_active' => true,
            'default_period' => 'monthly',
        ]);

        $criteria = RankingCriterion::with('rules')->orderBy('sort_order')->get();

        return view('admin.ranking.settings', compact('settings', 'criteria'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'default_period' => ['required', 'string', 'in:monthly,content'],
        ]);

        $settings = RankingSetting::first() ?: new RankingSetting();
        $settings->is_active = $request->boolean('is_active');
        $settings->default_period = $validated['default_period'];
        $settings->save();

        return redirect()->route('admin.ranking.settings')->with('success', 'Configurações atualizadas.');
    }

    public function storeRule(Request $request, RankingCriterion $criterion)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'points' => ['required', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $criterion->rules()->create($validated + [
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Faixa adicionada.');
    }
}
