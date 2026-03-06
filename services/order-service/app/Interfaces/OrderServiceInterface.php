<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface OrderServiceInterface
 *
 * Defines the contract for order business logic operations.
 */
interface OrderServiceInterface
{
    public function createOrder(string $tenantId, array $data, ?string $sagaId = null): Order;
    public function updateOrder(string $id, string $tenantId, array $data): Order;
    public function cancelOrder(string $id, string $tenantId, string $reason = ''): Order;
    public function confirmOrder(string $id, string $tenantId): Order;
    public function getOrder(string $id, string $tenantId): Order;
    public function listOrders(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function markAsFailed(string $id, string $tenantId, string $reason = ''): Order;
}
