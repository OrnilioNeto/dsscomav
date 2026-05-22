<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogSystemRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $baseContext = [
            'request_id' => $request->headers->get('X-Request-Id') ?: $request->attributes->get('request_id') ?: uniqid('req_', true),
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
            'user_agent' => $request->userAgent(),
        ];

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            Log::channel('system')->error('request_exception', $baseContext + [
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $status = $response->getStatusCode();
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info');

        Log::channel('system')->{$level}('request_completed', $baseContext + [
            'status' => $status,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'route' => optional($request->route())->getName(),
        ]);

        return $response;
    }
}
