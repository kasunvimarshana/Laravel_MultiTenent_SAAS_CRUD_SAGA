<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global exception handler — transforms exceptions into JSON API responses.
 */
class Handler extends ExceptionHandler
{
    /** @var array<int, class-string<Throwable>> */
    protected $dontReport = [];

    /** @var array<int, string> */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->renderable(function (OrderNotFoundException $e): JsonResponse {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $this->renderable(function (OrderStateException $e): JsonResponse {
            return response()->json(['error' => $e->getMessage()], 422);
        });

        $this->renderable(function (TenantNotFoundException $e): JsonResponse {
            return response()->json(['error' => $e->getMessage()], 403);
        });

        $this->renderable(function (NotFoundHttpException $e): JsonResponse {
            return response()->json(['error' => 'Resource not found.'], 404);
        });

        $this->renderable(function (AuthenticationException $e): JsonResponse {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        });
    }

    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'error'  => 'Validation failed.',
            'errors' => $exception->errors(),
        ], $exception->status);
    }
}
