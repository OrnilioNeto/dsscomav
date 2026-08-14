<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceApiRootUrl
{
    /**
     * Gera URLs (asset/route) usando o host da requisição atual.
     * Necessário porque o app acessa a API por IP (ex.: http://192.168.0.10:9000)
     * e o APP_URL configurado aponta para localhost, o que quebraria
     * avatares, fotos de posts e materiais no celular.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getSchemeAndHttpHost();

        if ($host) {
            URL::forceRootUrl($host);
        }

        return $next($request);
    }
}
