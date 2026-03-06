<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\SagaException;
use App\Http\Requests\InitiateOrderSagaRequest;
use App\Interfaces\SagaOrchestratorInterface;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Models\SagaTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * SagaController – REST interface for managing SAGA transactions.
 *
 * Routes:
 *   POST   /api/sagas/order          → initiateOrderSaga
 *   GET    /api/sagas/{sagaId}        → getSagaStatus
 *   GET    /api/sagas                 → listSagas
 *   POST   /api/sagas/{sagaId}/retry  → retrySaga
 */
class SagaController extends Controller
{
    public function __construct(
        private readonly SagaOrchestratorInterface $orchestrator,
        private readonly SagaTransactionRepositoryInterface $repository,
    ) {}

    // -----------------------------------------------------------------------
    // Endpoints
    // -----------------------------------------------------------------------

    /**
     * Initiate a new order saga.
     *
     * POST /api/sagas/order
     */
    public function initiateOrderSaga(InitiateOrderSagaRequest $request): JsonResponse
    {
        try {
            $tenantId = $request->header('X-Tenant-ID');
            $saga     = $this->orchestrator->startSaga(
                sagaType: 'order_saga',
                payload:  $request->validated(),
                tenantId: $tenantId,
            );

            return response()->json([
                'success' => true,
                'message' => 'Order saga initiated successfully.',
                'data'    => $this->formatSaga($saga),
            ], 201);
        } catch (SagaException $e) {
            Log::error('[SagaController] Failed to initiate order saga', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return the current status of a saga.
     *
     * GET /api/sagas/{sagaId}
     */
    public function getSagaStatus(string $sagaId): JsonResponse
    {
        try {
            $saga = $this->orchestrator->getSagaStatus($sagaId);

            return response()->json([
                'success' => true,
                'data'    => $this->formatSaga($saga),
            ]);
        } catch (SagaException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Return a paginated list of saga transactions for the current tenant.
     *
     * GET /api/sagas
     */
    public function listSagas(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', '20');
        $perPage  = min(max($perPage, 1), 100);

        $paginator = $this->repository->paginate($tenantId, $perPage);

        return response()->json([
            'success' => true,
            'data'    => collect($paginator->items())->map(fn ($s) => $this->formatSaga($s)),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Retry a failed saga transaction.
     *
     * POST /api/sagas/{sagaId}/retry
     */
    public function retrySaga(string $sagaId): JsonResponse
    {
        $saga = $this->repository->findBySagaId($sagaId);

        if ($saga === null) {
            return response()->json([
                'success' => false,
                'message' => "Saga not found: {$sagaId}",
            ], 404);
        }

        if (!$saga->isFailed()) {
            return response()->json([
                'success' => false,
                'message' => "Only FAILED sagas can be retried. Current status: {$saga->status}",
            ], 422);
        }

        $maxRetries = (int) config('saga.retry.max_attempts', 3);

        if ($saga->retry_count >= $maxRetries) {
            return response()->json([
                'success' => false,
                'message' => "Saga has exceeded maximum retry attempts ({$maxRetries}).",
            ], 422);
        }

        try {
            $saga->incrementRetry();

            $retried = $this->orchestrator->startSaga(
                sagaType: $saga->saga_type,
                payload:  $saga->payload ?? [],
                tenantId: $saga->tenant_id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Saga retry initiated.',
                'data'    => $this->formatSaga($retried),
            ], 201);
        } catch (SagaException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Format a SagaTransaction for JSON output.
     *
     * @return array<string, mixed>
     */
    private function formatSaga(SagaTransaction $saga): array
    {
        return [
            'saga_id'         => $saga->saga_id,
            'tenant_id'       => $saga->tenant_id,
            'saga_type'       => $saga->saga_type,
            'status'          => $saga->status,
            'current_step'    => $saga->current_step,
            'completed_steps' => $saga->completed_steps,
            'failed_step'     => $saga->failed_step,
            'error_message'   => $saga->error_message,
            'retry_count'     => $saga->retry_count,
            'started_at'      => $saga->started_at?->toIso8601String(),
            'completed_at'    => $saga->completed_at?->toIso8601String(),
            'created_at'      => $saga->created_at->toIso8601String(),
            'steps'           => $saga->relationLoaded('steps')
                ? $saga->steps->map(fn ($s) => [
                    'step_name'    => $s->step_name,
                    'step_order'   => $s->step_order,
                    'status'       => $s->status,
                    'started_at'   => $s->started_at?->toIso8601String(),
                    'completed_at' => $s->completed_at?->toIso8601String(),
                ])->toArray()
                : [],
        ];
    }
}
