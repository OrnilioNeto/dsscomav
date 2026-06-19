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
        // Apenas para requisições HTML GET, usuários autenticados e não super_admin
        if ($request->isMethod('get') && !$request->ajax() && !$request->wantsJson() && $request->user() && !$request->user()->isSuperAdmin()) {
            if (!$request->session()->has('splash_shown')) {
                $activeSplashContents = SplashContent::where('status', 'ativo')
                    ->whereDate('data_inicio', '<=', Carbon::now())
                    ->whereDate('data_fim', '>=', Carbon::now())
                    ->orderBy('ordem')
                    ->get();

                if ($activeSplashContents->isNotEmpty()) {
                    $request->session()->flash('splash_contents', $activeSplashContents);
                }
                
                // Marca que o splash foi exibido nesta sessão
                $request->session()->put('splash_shown', true);
            }
        }

        return $next($request);
    }
}