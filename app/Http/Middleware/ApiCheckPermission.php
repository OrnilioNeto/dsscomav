<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCheckPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->hasPermission($module, $action)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Você não tem permissão para acessar este recurso.',
        ], 403);
    }
}
