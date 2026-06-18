<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SplashContent;
use Carbon\Carbon;

class CheckSplashContent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apenas para usuários autenticados e que não sejam Super Admin
        if ($request->user() && !$request->user()->isSuperAdmin()) {
            $activeSplashContents = SplashContent::where('status', 'ativo')
                ->whereDate('data_inicio', '<=', Carbon::now())
                ->whereDate('data_fim', '>=', Carbon::now())
                ->orderBy('ordem')
                ->get();

            if ($activeSplashContents->isNotEmpty()) {
                $request->session()->flash('splash_contents', $activeSplashContents);
            }
        }

        return $next($request);
    }
}