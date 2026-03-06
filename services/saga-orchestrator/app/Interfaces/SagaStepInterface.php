<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\SagaTransaction;

/**
 * Contract for a single step within a SAGA workflow.
 *
 * Each step encapsulates a single remote operation and its corresponding
 * compensating action.  Steps are executed sequentially by the orchestrator.
 */
interface SagaStepInterface
{
    /**
     * Execute the forward action of this step.
     *
     * Publishes a command message to the appropriate microservice and
     * stores any result data for later use during compensation.
     *
     * @param  SagaTransaction      $saga    The parent saga transaction.
     * @param  array<string, mixed> $payload Data needed to execute the step.
     * @return array<string, mixed>          Result data to persist against the saga step record.
     *
     * @throws \App\Exceptions\SagaException If the step cannot be executed.
     */
    public function execute(SagaTransaction $saga, array $payload): array;

    /**
     * Execute the compensating action for this step.
     *
     * Called when the saga must be rolled back.  Should publish a compensating
     * command message to the appropriate microservice.
     *
     * @param  SagaTransaction      $saga       The parent saga transaction.
     * @param  array<string, mixed> $stepResult The result data persisted when the step executed.
     * @return void
     *
     * @throws \App\Exceptions\SagaException If the compensation cannot be dispatched.
     */
    public function compensate(SagaTransaction $saga, array $stepResult): void;

    /**
     * Return the canonical name of this step.
     *
     * Used as a key when persisting step records and when determining
     * which event responses map to which step.
     *
     * @return string  E.g. "create_order", "reserve_inventory".
     */
    public function getName(): string;

    /**
     * Return the maximum number of seconds the orchestrator should wait
     * for an acknowledgement event before considering the step timed out.
     *
     * @return int  Timeout in seconds.
     */
    public function getTimeout(): int;
}
