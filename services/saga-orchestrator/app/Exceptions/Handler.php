<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Global exception handler that returns JSON responses for all exceptions.
 *
 * All responses follow the same envelope shape:
 * {
 *   "success": false,
 *   "message": "...",
 *   "errors":  { ... }   // present only for validation failures
 * }
 */
class Handler extends ExceptionHandler
{
    /**
     * Exception types that should never be reported to log aggregators.
     *
     * @var list<class-string<\Throwable>>
     */
    protected $dontReport = [
        SagaException::class,
    ];

    /**
     * Exception types whose validation messages should not be flashed to session.
     *
     * @var list<string>
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
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * Overrides the parent to ensure all responses are JSON even when the
     * request does not explicitly request JSON.
     *
     * @param  Request   $request
     * @param  Throwable $e
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // Validation errors – return 422 with field-level messages.
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Domain-specific saga errors.
        if ($e instanceof SagaException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 ? $e->getCode() : 422);
        }

        // Standard HTTP exceptions (404, 403, etc.).
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'HTTP error.',
            ], $e->getStatusCode());
        }

        // Unexpected exceptions – hide implementation details in production.
        $message = $this->isHttpDebugMode()
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Return true when the application is running in debug mode.
     */
    private function isHttpDebugMode(): bool
    {
        return (bool) config('app.debug', false);
    }
}
