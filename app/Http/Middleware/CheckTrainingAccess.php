<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Training;

class CheckTrainingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $trainingId = $request->route('training');

        if (!$user || !$trainingId) {
            return $next($request);
        }

        $training = Training::find($trainingId);

        if (!$training) {
            abort(404);
        }

        // Super Admin e Admin têm acesso a tudo
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $next($request);
        }

        // Verifica se o usuário pode acessar este treinamento
        if (!$user->canAccessTraining($training)) {
            abort(403, 'Você não tem permissão para acessar este treinamento.');
        }

        return $next($request);
    }
}
