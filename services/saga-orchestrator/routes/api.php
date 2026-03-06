<?php

declare(strict_types=1);

use App\Http\Controllers\SagaController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – SAGA Orchestrator
|--------------------------------------------------------------------------
|
| Route groups:
|   /api/sagas/*    – SAGA transaction management (tenant + auth protected)
|   /api/tenants/*  – Tenant CRUD (auth protected, admin-only in prod)
|   /api/health     – Unauthenticated liveness probe
|
*/

// -----------------------------------------------------------------------
// Health check – no authentication required
// -----------------------------------------------------------------------
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'service'   => 'saga-orchestrator',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// -----------------------------------------------------------------------
// SAGA routes – tenant resolution + bearer-token authentication
// -----------------------------------------------------------------------
Route::middleware(['tenant', 'api.auth'])
    ->prefix('sagas')
    ->group(function (): void {
        // Initiate a new order saga.
        Route::post('/order', [SagaController::class, 'initiateOrderSaga'])
             ->name('sagas.order.initiate');

        // List all saga transactions for the current tenant.
        Route::get('/', [SagaController::class, 'listSagas'])
             ->name('sagas.index');

        // Get the status of a specific saga.
        Route::get('/{sagaId}', [SagaController::class, 'getSagaStatus'])
             ->name('sagas.show')
             ->where('sagaId', '[0-9a-fA-F\-]{36}');

        // Retry a failed saga.
        Route::post('/{sagaId}/retry', [SagaController::class, 'retrySaga'])
             ->name('sagas.retry')
             ->where('sagaId', '[0-9a-fA-F\-]{36}');
    });

// -----------------------------------------------------------------------
// Tenant management routes – bearer-token authentication only
// -----------------------------------------------------------------------
Route::middleware(['api.auth'])
    ->prefix('tenants')
    ->group(function (): void {
        Route::get('/',       [TenantController::class, 'index'])  ->name('tenants.index');
        Route::post('/',      [TenantController::class, 'store'])  ->name('tenants.store');
        Route::get('/{id}',   [TenantController::class, 'show'])   ->name('tenants.show');
        Route::put('/{id}',   [TenantController::class, 'update']) ->name('tenants.update');
        Route::delete('/{id}',[TenantController::class, 'destroy'])->name('tenants.destroy');
    });
