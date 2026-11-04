<?php

namespace App\Modules\Frota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Frota\Services\DriverPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcessoController extends Controller
{
    public function bloqueado(Request $request, DriverPresenceService $presence)
    {
        $acesso = $presence->avaliar($request->user());

        if ($acesso['liberado']) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Seu acesso aos treinamentos foi liberado.');
        }

        return view('frota::treinamentos.bloqueado', compact('acesso'));
    }

    public function status(Request $request, DriverPresenceService $presence)
    {
        $acesso = $presence->avaliar($request->user(), $request->boolean('forcar'));

        return response()->json([
            'liberado' => $acesso['liberado'],
            'origem' => $acesso['origem'],
            'motivo' => $acesso['motivo'],
            'detalhe' => $acesso['detalhe'],
            'verificado_em' => optional($acesso['verificado_em'])->toIso8601String(),
            'liberado_ate' => optional($acesso['liberacao'])->fim?->toIso8601String(),
            'horas_sessao' => $acesso['horas_sessao'],
            'usuario' => Auth::id(),
        ]);
    }
}
