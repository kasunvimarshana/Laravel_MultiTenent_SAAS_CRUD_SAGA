<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SagaException;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaOrchestratorInterface;
use App\Interfaces\SagaStepInterface;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Models\SagaStep;
use App\Models\SagaTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * SAGA Orchestrator – coordinates distributed transactions.
 *
 * The orchestrator drives each saga step forward in sequence, persists state
 * after every transition, and triggers compensating transactions when a step
 * fails so that the overall system remains consistent.
 */
final class SagaOrchestrator implements SagaOrchestratorInterface
{
    /**
     * Registry mapping saga type names to their ordered step lists.
     *
     * @var array<string, list<SagaStepInterface>>
     */
    private array $sagaSteps = [];

    /**
     * @param SagaTransactionRepositoryInterface $repository  Persistence layer.
     * @param MessageBrokerInterface             $broker      Message transport.
     */
    public function __construct(
        private readonly SagaTransactionRepositoryInterface $repository,
        private readonly MessageBrokerInterface $broker,
    ) {}

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    /**
     * Register the ordered steps for a saga type.
     *
     * @param string                    $sagaType
     * @param list<SagaStepInterface>   $steps
     */
    public function registerSteps(string $sagaType, array $steps): void
    {
        $this->sagaSteps[$sagaType] = $steps;
    }

    // -----------------------------------------------------------------------
    // SagaOrchestratorInterface
    // -----------------------------------------------------------------------

    /** {@inheritdoc} */
    public function startSaga(string $sagaType, array $payload, ?string $tenantId = null): SagaTransaction
    {
        $this->guardKnownSagaType($sagaType);

        $sagaId = Uuid::uuid4()->toString();

        Log::info('[SagaOrchestrator] Starting saga', [
            'saga_id'   => $sagaId,
            'saga_type' => $sagaType,
            'tenant_id' => $tenantId,
        ]);

        return DB::transaction(function () use ($sagaId, $sagaType, $payload, $tenantId): SagaTransaction {
            $saga = $this->repository->create([
                'saga_id'         => $sagaId,
                'tenant_id'       => $tenantId,
                'saga_type'       => $sagaType,
                'status'          => SagaTransaction::STATUS_RUNNING,
                'payload'         => $payload,
                'completed_steps' => [],
                'started_at'      => now(),
            ]);

            $this->createStepRecords($saga);
            $this->executeNextStep($saga, $payload);

            return $saga->fresh(['steps']);
        });
    }

    /** {@inheritdoc} */
    public function processSagaEvent(string $sagaId, string $eventName, array $eventData): SagaTransaction
    {
        $saga = $this->repository->findBySagaId($sagaId);

        if ($saga === null) {
            throw new SagaException("Saga not found: {$sagaId}");
        }

        if ($saga->isTerminal()) {
            Log::warning('[SagaOrchestrator] Received event for terminal saga', [
                'saga_id'    => $sagaId,
                'event_name' => $eventName,
                'status'     => $saga->status,
            ]);
            return $saga;
        }

        Log::info('[SagaOrchestrator] Processing saga event', [
            'saga_id'    => $sagaId,
            'event_name' => $eventName,
        ]);

        $isFailureEvent = $this->isFailureEvent($eventName);

        if ($isFailureEvent) {
            return $this->handleStepFailure($saga, $eventName, $eventData);
        }

        return $this->handleStepSuccess($saga, $eventName, $eventData);
    }

    /** {@inheritdoc} */
    public function compensateSaga(string $sagaId, string $reason): SagaTransaction
    {
        $saga = $this->repository->findBySagaId($sagaId);

        if ($saga === null) {
            throw new SagaException("Saga not found for compensation: {$sagaId}");
        }

        if ($saga->status === SagaTransaction::STATUS_COMPENSATED) {
            Log::info('[SagaOrchestrator] Saga already compensated', ['saga_id' => $sagaId]);
            return $saga;
        }

        Log::warning('[SagaOrchestrator] Compensating saga', [
            'saga_id' => $sagaId,
            'reason'  => $reason,
        ]);

        $this->repository->update($saga->id, [
            'status'        => SagaTransaction::STATUS_COMPENSATING,
            'error_message' => $reason,
        ]);

        $completedSteps = array_reverse($saga->completed_steps ?? []);
        $steps          = $this->sagaSteps[$saga->saga_type] ?? [];
        $stepMap        = $this->buildStepMap($steps);

        foreach ($completedSteps as $stepName) {
            if (!isset($stepMap[$stepName])) {
                continue;
            }

            $step     = $stepMap[$stepName];
            $stepRecord = $saga->steps()->where('step_name', $stepName)->first();
            $result   = $stepRecord?->result ?? [];

            try {
                $this->updateStepStatus($saga, $stepName, SagaStep::STATUS_COMPENSATING);
                $step->compensate($saga, $result);
                $this->updateStepStatus($saga, $stepName, SagaStep::STATUS_COMPENSATED);

                Log::info('[SagaOrchestrator] Compensation step dispatched', [
                    'saga_id'   => $sagaId,
                    'step_name' => $stepName,
                ]);
            } catch (\Throwable $e) {
                Log::error('[SagaOrchestrator] Compensation step failed', [
                    'saga_id'   => $sagaId,
                    'step_name' => $stepName,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $this->repository->update($saga->id, [
            'status'       => SagaTransaction::STATUS_COMPENSATED,
            'completed_at' => now(),
        ]);
    }

    /** {@inheritdoc} */
    public function getSagaStatus(string $sagaId): SagaTransaction
    {
        $saga = $this->repository->findBySagaId($sagaId);

        if ($saga === null) {
            throw new SagaException("Saga not found: {$sagaId}");
        }

        return $saga->load('steps');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Create SagaStep records for every step in the saga workflow.
     */
    private function createStepRecords(SagaTransaction $saga): void
    {
        $steps = $this->sagaSteps[$saga->saga_type] ?? [];

        foreach ($steps as $order => $step) {
            SagaStep::create([
                'saga_transaction_id' => $saga->id,
                'step_name'           => $step->getName(),
                'step_order'          => $order + 1,
                'status'              => SagaStep::STATUS_PENDING,
                'payload'             => $saga->payload,
            ]);
        }
    }

    /**
     * Find and execute the next pending step for this saga.
     */
    private function executeNextStep(SagaTransaction $saga, array $payload): void
    {
        $steps = $this->sagaSteps[$saga->saga_type] ?? [];

        foreach ($steps as $step) {
            if (!in_array($step->getName(), $saga->completed_steps ?? [], true)) {
                $this->repository->update($saga->id, ['current_step' => $step->getName()]);
                $this->updateStepStatus($saga, $step->getName(), SagaStep::STATUS_RUNNING);

                Log::info('[SagaOrchestrator] Executing step', [
                    'saga_id'   => $saga->saga_id,
                    'step_name' => $step->getName(),
                ]);

                $step->execute($saga, $payload);
                return;
            }
        }

        // All steps completed – mark the saga done.
        $this->repository->update($saga->id, [
            'status'       => SagaTransaction::STATUS_COMPLETED,
            'current_step' => null,
            'completed_at' => now(),
        ]);

        Log::info('[SagaOrchestrator] Saga completed', ['saga_id' => $saga->saga_id]);
    }

    /**
     * Handle a successful step-completion event.
     */
    private function handleStepSuccess(SagaTransaction $saga, string $eventName, array $eventData): SagaTransaction
    {
        $currentStep = $saga->current_step;

        if ($currentStep === null) {
            throw new SagaException("Received success event '{$eventName}' but saga has no current step.");
        }

        $this->updateStepStatus($saga, $currentStep, SagaStep::STATUS_COMPLETED, $eventData);
        $this->repository->appendCompletedStep($saga->id, $currentStep);

        $saga->refresh();

        $this->executeNextStep($saga, array_merge($saga->payload ?? [], $eventData));

        return $saga->fresh(['steps']);
    }

    /**
     * Handle a step-failure event by triggering compensation.
     */
    private function handleStepFailure(
        SagaTransaction $saga,
        string $eventName,
        array $eventData,
    ): SagaTransaction {
        $errorMessage = $eventData['error'] ?? $eventData['message'] ?? $eventName;

        $this->repository->update($saga->id, [
            'status'        => SagaTransaction::STATUS_FAILED,
            'failed_step'   => $saga->current_step,
            'error_message' => $errorMessage,
        ]);

        if ($saga->current_step) {
            $this->updateStepStatus($saga, $saga->current_step, SagaStep::STATUS_FAILED, $eventData);
        }

        Log::error('[SagaOrchestrator] Step failed – triggering compensation', [
            'saga_id'    => $saga->saga_id,
            'step'       => $saga->current_step,
            'event_name' => $eventName,
            'error'      => $errorMessage,
        ]);

        return $this->compensateSaga($saga->saga_id, $errorMessage);
    }

    /**
     * Update the status (and optionally result) of a single step record.
     *
     * @param array<string, mixed> $result
     */
    private function updateStepStatus(
        SagaTransaction $saga,
        string $stepName,
        string $status,
        array $result = [],
    ): void {
        $saga->steps()
            ->where('step_name', $stepName)
            ->update(array_filter([
                'status'       => $status,
                'result'       => $result ? json_encode($result) : null,
                'started_at'   => $status === SagaStep::STATUS_RUNNING ? now() : null,
                'completed_at' => in_array($status, [
                    SagaStep::STATUS_COMPLETED,
                    SagaStep::STATUS_FAILED,
                    SagaStep::STATUS_COMPENSATED,
                ], true) ? now() : null,
            ], fn ($v) => $v !== null));
    }

    /**
     * Determine whether the event name indicates a failure.
     */
    private function isFailureEvent(string $eventName): bool
    {
        return str_ends_with($eventName, '_failed')
            || str_ends_with($eventName, '_error')
            || str_contains($eventName, 'failure');
    }

    /**
     * Build a step-name → SagaStepInterface map for quick lookups.
     *
     * @param  list<SagaStepInterface>          $steps
     * @return array<string, SagaStepInterface>
     */
    private function buildStepMap(array $steps): array
    {
        $map = [];
        foreach ($steps as $step) {
            $map[$step->getName()] = $step;
        }
        return $map;
    }

    /**
     * Throw if the given saga type has not been registered.
     */
    private function guardKnownSagaType(string $sagaType): void
    {
        if (!isset($this->sagaSteps[$sagaType])) {
            throw new SagaException("Unknown saga type: '{$sagaType}'");
        }
    }
}
