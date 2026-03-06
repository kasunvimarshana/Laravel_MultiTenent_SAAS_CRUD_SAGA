<?php

declare(strict_types=1);

use App\Http\Controllers\OrderController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([TenantMiddleware::class, ApiAuthMiddleware::class])
    ->group(function (): void {
        Route::post('/orders',              [OrderController::class, 'store']);
        Route::get('/orders',               [OrderController::class, 'index']);
        Route::get('/orders/{id}',          [OrderController::class, 'show']);
        Route::put('/orders/{id}',          [OrderController::class, 'update']);
        Route::delete('/orders/{id}',       [OrderController::class, 'destroy']);
        Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm']);
    });

Route::get('/health', static function (): \Illuminate\Http\JsonResponse {
    return response()->json(['status' => 'ok', 'service' => 'order-service']);
});
