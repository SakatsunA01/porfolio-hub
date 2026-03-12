<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            $status = 500;
            $code = 'internal_error';
            $message = 'Unexpected server error.';
            $errors = null;

            if ($e instanceof ValidationException) {
                $status = 422;
                $code = 'validation_error';
                $message = 'The provided data is invalid.';
                $errors = $e->errors();
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
                $code = 'unauthenticated';
                $message = 'Unauthenticated.';
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $code = match ($status) {
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    429 => 'too_many_requests',
                    default => 'http_error',
                };
                $message = $e->getMessage() !== '' ? $e->getMessage() : $message;
            }

            return response()->json(array_filter([
                'message' => $message,
                'code' => $code,
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
                'errors' => $errors,
            ], fn ($value) => $value !== null), $status);
        });
    }
}
