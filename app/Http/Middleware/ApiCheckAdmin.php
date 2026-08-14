<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autenticado.',
            ], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Acesso negado. Esta área é restrita a administradores.',
        ], 403);
    }
}
