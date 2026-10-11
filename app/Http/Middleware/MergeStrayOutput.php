<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captura saída emitida fora do objeto de resposta (whitespace extraviado
 * de views, por exemplo) e a anexa ao corpo. Sem isso, esses bytes são
 * enviados antes da resposta e corrompem o gzip do CompressResponse.
 */
class MergeStrayOutput
{
    public function handle(Request $request, Closure $next): Response
    {
        ob_start();

        try {
            $response = $next($request);
        } finally {
            $stray = (string) ob_get_clean();
        }

        if ($stray !== '') {
            $content = $response->getContent();

            if ($content !== false) {
                $original = method_exists($response, 'getOriginalContent')
                    ? $response->getOriginalContent()
                    : null;

                $response->setContent($stray.$content);

                if (method_exists($response, 'getOriginalContent')) {
                    $response->original = $original;
                }

                $response->headers->remove('Content-Length');
            }
        }

        return $response;
    }
}
