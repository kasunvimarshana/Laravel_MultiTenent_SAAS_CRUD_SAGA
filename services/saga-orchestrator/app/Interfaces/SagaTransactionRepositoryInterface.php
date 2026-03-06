<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\SagaTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository contract for persisting and retrieving SAGA transaction records.
 */
interface SagaTransactionRepositoryInterface
{
    /**
     * Persist a new saga transaction.
     *
     * @param  array<string, mixed> $data  Attributes for the new record.
     * @return SagaTransaction
     */
    public function create(array $data): SagaTransaction;

    /**
     * Find a saga transaction by its primary key (UUID).
     *
     * @param  string $id
     * @return SagaTransaction|null
     */
    public function findById(string $id): ?SagaTransaction;

    /**
     * Find a saga transaction by its saga_id business key.
     *
     * @param  string $sagaId
     * @return SagaTransaction|null
     */
    public function findBySagaId(string $sagaId): ?SagaTransaction;

    /**
     * Update an existing saga transaction.
     *
     * @param  string               $id    Primary key of the record.
     * @param  array<string, mixed> $data  Attributes to update.
     * @return SagaTransaction
     */
    public function update(string $id, array $data): SagaTransaction;

    /**
     * Delete a saga transaction by its primary key.
     *
     * @param  string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Return a paginated list of sagas, optionally scoped to a tenant.
     *
     * @param  string|null $tenantId  Filter by tenant; null returns all tenants.
     * @param  int         $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(?string $tenantId = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * Retrieve all sagas currently in FAILED status that are eligible for retry.
     *
     * @param  int $maxRetries  Maximum number of attempts already made.
     * @return Collection<int, SagaTransaction>
     */
    public function findFailedRetryable(int $maxRetries = 3): Collection;

    /**
     * Update the status of a saga transaction.
     *
     * @param  string $id      Primary key.
     * @param  string $status  One of PENDING|RUNNING|COMPLETED|FAILED|COMPENSATING|COMPENSATED.
     * @return SagaTransaction
     */
    public function updateStatus(string $id, string $status): SagaTransaction;

    /**
     * Append a step name to the completed_steps JSON array on the transaction.
     *
     * @param  string $id       Primary key.
     * @param  string $stepName
     * @return SagaTransaction
     */
    public function appendCompletedStep(string $id, string $stepName): SagaTransaction;
}
