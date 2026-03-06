<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface OrderRepositoryInterface
 *
 * Defines the contract for order data access operations.
 */
interface OrderRepositoryInterface
{
    public function findById(string $id): ?Order;
    public function findByTenant(string $tenantId, int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Order;
    public function update(string $id, array $data): Order;
    public function updateStatus(string $id, string $status, array $additionalData = []): Order;
    public function delete(string $id): bool;
    public function findByStatus(string $tenantId, string $status, int $perPage = 15): LengthAwarePaginator;
}
