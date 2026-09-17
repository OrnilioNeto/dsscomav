<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogSystemRequests
{
    private const SENSITIVE_QUERY_KEYS = 'token|codigo|code|password|password_confirmation|api_key|secret|access_token';

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $requestId = $this->resolveRequestId($request);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            Log::channel('system')->error('request_exception', $this->context($request, $requestId) + [
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $status = $response->getStatusCode();
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info');

        Log::channel('system')->{$level}('request_completed', $this->context($request, $requestId) + [
            'status' => $status,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'route' => optional($request->route())->getName(),
        ]);

        return $response;
    }

    private function context(Request $request, string $requestId): array
    {
        return [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $this->safePath($request),
            'full_url' => $this->safeUrl($request),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
            'tenant_id' => app(TenantManager::class)->id(),
            'user_agent' => $request->userAgent(),
        ];
    }

    private function resolveRequestId(Request $request): string
    {
        $header = (string) $request->headers->get('X-Request-Id', '');

        if ($header !== '' && preg_match('/^[A-Za-z0-9._\-]{1,64}$/', $header) === 1) {
            return $header;
        }

        return (string) ($request->attributes->get('request_id') ?: uniqid('req_', true));
    }

    private function safePath(Request $request): string
    {
        return preg_replace('#^(ficha|validar)/[^/]+#', '$1/***', $request->path());
    }

    private function safeUrl(Request $request): string
    {
        $url = preg_replace('#/(ficha|validar)/[^/?]+#', '/$1/***', $request->fullUrl());

        return preg_replace(
            '/([?&](?:'.self::SENSITIVE_QUERY_KEYS.')=)[^&#]*/i',
            '$1***',
            $url
        );
    }
}
