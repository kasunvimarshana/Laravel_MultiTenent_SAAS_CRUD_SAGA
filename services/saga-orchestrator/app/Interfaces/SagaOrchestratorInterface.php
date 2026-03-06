<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\SagaTransaction;

/**
 * Contract for the SAGA Orchestrator.
 *
 * The orchestrator is responsible for driving a saga through its steps,
 * managing state transitions, and triggering compensating transactions on failure.
 */
interface SagaOrchestratorInterface
{
    /**
     * Start a new saga of the given type with the supplied payload.
     *
     * @param  string               $sagaType  Identifier for the saga workflow (e.g. "order_saga").
     * @param  array<string, mixed> $payload   Initial data required by the saga.
     * @param  string|null          $tenantId  Tenant scope for multi-tenancy.
     * @return SagaTransaction                 The newly created saga transaction record.
     *
     * @throws \App\Exceptions\SagaException   If the saga type is unknown or cannot be started.
     */
    public function startSaga(string $sagaType, array $payload, ?string $tenantId = null): SagaTransaction;

    /**
     * Process an inbound saga event and advance the saga to the next step.
     *
     * @param  string               $sagaId    Unique identifier of the saga transaction.
     * @param  string               $eventName Name of the event received (e.g. "order_created").
     * @param  array<string, mixed> $eventData Event payload from the responding service.
     * @return SagaTransaction                 Updated saga transaction after processing the event.
     *
     * @throws \App\Exceptions\SagaException   If the saga is not found or the event is unexpected.
     */
    public function processSagaEvent(string $sagaId, string $eventName, array $eventData): SagaTransaction;

    /**
     * Trigger compensating transactions for the given saga.
     *
     * Walks backwards through the completed steps and calls each step's
     * compensate() method to undo its effects.
     *
     * @param  string $sagaId  Unique identifier of the saga transaction.
     * @param  string $reason  Human-readable explanation of why compensation was triggered.
     * @return SagaTransaction Updated saga transaction in COMPENSATING/COMPENSATED status.
     *
     * @throws \App\Exceptions\SagaException   If the saga is not found or already compensated.
     */
    public function compensateSaga(string $sagaId, string $reason): SagaTransaction;

    /**
     * Retrieve the current status of a saga transaction.
     *
     * @param  string $sagaId  Unique identifier of the saga transaction.
     * @return SagaTransaction The saga transaction with its current state.
     *
     * @throws \App\Exceptions\SagaException   If the saga is not found.
     */
    public function getSagaStatus(string $sagaId): SagaTransaction;
}
