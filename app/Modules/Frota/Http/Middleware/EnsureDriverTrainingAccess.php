<?php

namespace App\Modules\Frota\Http\Middleware;

use App\Modules\Frota\Services\DriverPresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portão de acesso aos treinamentos para motoristas.
 *
 * Só é aplicado nas rotas de treinamento (player, progresso, avaliação,
 * conclusão, stream e materiais). Login, dashboard, perfil, EPI, social e
 * certificados já emitidos continuam acessíveis.
 */
class EnsureDriverTrainingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        try {
            $avaliacao = app(DriverPresenceService::class)->avaliar($user);
        } catch (\Throwable $e) {
            // Falha interna inesperada não pode travar todos os treinamentos.
            report($e);

            return $next($request);
        }

        if ($avaliacao['liberado']) {
            $request->attributes->set('frota_acesso_treinamentos', $avaliacao);

            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'code' => 'DRIVER_TRAINING_BLOCKED',
                'message' => $avaliacao['motivo'],
                'acesso_treinamentos' => $this->payload($avaliacao),
            ], 403);
        }

        return redirect()
            ->route('treinamentos.bloqueado')
            ->with('frota_origem_bloqueio', $avaliacao['origem']);
    }

    /**
     * @param  array<string, mixed>  $avaliacao
     * @return array<string, mixed>
     */
    private function payload(array $avaliacao): array
    {
        return [
            'liberado' => false,
            'origem' => $avaliacao['origem'],
            'motivo' => $avaliacao['motivo'],
            'verificado_em' => optional($avaliacao['verificado_em'])->toIso8601String(),
            'liberado_ate' => optional($avaliacao['liberacao'])->fim?->toIso8601String(),
        ];
    }
}
