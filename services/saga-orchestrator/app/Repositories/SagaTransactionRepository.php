<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\SagaException;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Models\SagaTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of SagaTransactionRepositoryInterface.
 */
final class SagaTransactionRepository implements SagaTransactionRepositoryInterface
{
    /** {@inheritdoc} */
    public function create(array $data): SagaTransaction
    {
        return SagaTransaction::create($data);
    }

    /** {@inheritdoc} */
    public function findById(string $id): ?SagaTransaction
    {
        return SagaTransaction::find($id);
    }

    /** {@inheritdoc} */
    public function findBySagaId(string $sagaId): ?SagaTransaction
    {
        return SagaTransaction::where('saga_id', $sagaId)->first();
    }

    /** {@inheritdoc} */
    public function update(string $id, array $data): SagaTransaction
    {
        $saga = $this->findById($id);

        if ($saga === null) {
            throw new SagaException("SagaTransaction not found for update: {$id}");
        }

        $saga->update($data);

        return $saga->fresh();
    }

    /** {@inheritdoc} */
    public function delete(string $id): bool
    {
        $saga = $this->findById($id);

        if ($saga === null) {
            return false;
        }

        return (bool) $saga->delete();
    }

    /** {@inheritdoc} */
    public function paginate(?string $tenantId = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = SagaTransaction::with('steps')
            ->orderBy('created_at', 'desc');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->paginate($perPage);
    }

    /** {@inheritdoc} */
    public function findFailedRetryable(int $maxRetries = 3): Collection
    {
        return SagaTransaction::where('status', SagaTransaction::STATUS_FAILED)
            ->where('retry_count', '<', $maxRetries)
            ->get();
    }

    /** {@inheritdoc} */
    public function updateStatus(string $id, string $status): SagaTransaction
    {
        return $this->update($id, ['status' => $status]);
    }

    /** {@inheritdoc} */
    public function appendCompletedStep(string $id, string $stepName): SagaTransaction
    {
        $saga = $this->findById($id);

        if ($saga === null) {
            throw new SagaException("SagaTransaction not found when appending step: {$id}");
        }

        $steps   = $saga->completed_steps ?? [];
        $steps[] = $stepName;

        $saga->update(['completed_steps' => array_values(array_unique($steps))]);

        return $saga->fresh();
    }
}
