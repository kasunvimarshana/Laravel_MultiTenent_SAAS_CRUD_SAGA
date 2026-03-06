<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\OrderNotFoundException;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * OrderRepository
 *
 * Eloquent-backed implementation of OrderRepositoryInterface.
 */
class OrderRepository implements OrderRepositoryInterface
{
    public function findById(string $id): ?Order
    {
        return Order::with('orderItems')->find($id);
    }

    public function findByTenant(string $tenantId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Order::with('orderItems')->byTenant($tenantId);

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->byCustomer($filters['customer_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Order
    {
        $order = Order::create($data);

        Log::debug('OrderRepository: created order', ['id' => $order->id]);

        return $order->load('orderItems');
    }

    public function update(string $id, array $data): Order
    {
        $order = $this->findOrFail($id);
        $order->update($data);

        Log::debug('OrderRepository: updated order', ['id' => $id]);

        return $order->fresh('orderItems');
    }

    public function updateStatus(string $id, string $status, array $additionalData = []): Order
    {
        $order = $this->findOrFail($id);
        $order->update(array_merge(['status' => $status], $additionalData));

        Log::debug('OrderRepository: status updated', ['id' => $id, 'status' => $status]);

        return $order->fresh();
    }

    public function delete(string $id): bool
    {
        $order = $this->findOrFail($id);
        $result = (bool) $order->delete();

        Log::debug('OrderRepository: deleted order', ['id' => $id]);

        return $result;
    }

    public function findByStatus(string $tenantId, string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('orderItems')
            ->byTenant($tenantId)
            ->byStatus($status)
            ->latest()
            ->paginate($perPage);
    }

    private function findOrFail(string $id): Order
    {
        $order = Order::find($id);

        if ($order === null) {
            throw new OrderNotFoundException("Order {$id} not found.");
        }

        return $order;
    }
}
