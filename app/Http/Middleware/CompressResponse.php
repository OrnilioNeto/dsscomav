<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    private const COMPRESSIBLE_TYPES = [
        'text/',
        'application/json',
        'application/javascript',
        'application/xml',
        'application/rss+xml',
        'application/xhtml+xml',
        'image/svg+xml',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('app.compress_responses', false)) {
            return $response;
        }

        if (! str_contains((string) $request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }

        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < 1024) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $compressible = false;

        foreach (self::COMPRESSIBLE_TYPES as $type) {
            if (str_contains($contentType, $type)) {
                $compressible = true;
                break;
            }
        }

        if (! $compressible) {
            return $response;
        }

        $response->setContent(gzencode($content, 5));
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
