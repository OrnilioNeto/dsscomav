<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // O log de exceções é feito pelo middleware global LogSystemRequests
        // (request_exception), evitando entradas duplicadas no canal 'system'.
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof ThrottleRequestsException && ! $request->expectsJson()) {
            return redirect()->back()->with(
                'error',
                'Muitas tentativas em pouco tempo. Aguarde um minuto e tente novamente.'
            );
        }

        return parent::render($request, $e);
    }
}
