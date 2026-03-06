<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OrderNotFoundException;
use App\Exceptions\OrderStateException;
use App\Interfaces\OrderEventPublisherInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\OrderServiceInterface;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderService
 *
 * Core business logic for managing orders.
 */
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface     $repository,
        private readonly OrderEventPublisherInterface $eventPublisher,
    ) {
    }

    public function createOrder(string $tenantId, array $data, ?string $sagaId = null): Order
    {
        return DB::transaction(function () use ($tenantId, $data, $sagaId): Order {
            $totalAmount = $this->calculateTotal($data['items']);

            $order = $this->repository->create([
                'tenant_id'        => $tenantId,
                'saga_id'          => $sagaId,
                'customer_id'      => $data['customer_id'],
                'status'           => Order::STATUS_PENDING,
                'items'            => $data['items'],
                'total_amount'     => $totalAmount,
                'currency'         => $data['currency'] ?? 'USD',
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            Log::info('OrderService: order created', [
                'order_id'  => $order->id,
                'tenant_id' => $tenantId,
            ]);

            $this->eventPublisher->publishOrderCreated($order);

            return $order;
        });
    }

    public function updateOrder(string $id, string $tenantId, array $data): Order
    {
        return DB::transaction(function () use ($id, $tenantId, $data): Order {
            $order = $this->fetchOrder($id, $tenantId);

            if ($order->isTerminal()) {
                throw new OrderStateException(
                    "Order {$id} is in terminal state {$order->status} and cannot be updated."
                );
            }

            if (isset($data['items'])) {
                $data['total_amount'] = $this->calculateTotal($data['items']);
            }

            $updated = $this->repository->update($id, $data);

            Log::info('OrderService: order updated', ['order_id' => $id]);

            return $updated;
        });
    }

    public function cancelOrder(string $id, string $tenantId, string $reason = ''): Order
    {
        return DB::transaction(function () use ($id, $tenantId, $reason): Order {
            $order = $this->fetchOrder($id, $tenantId);

            if (!$order->isCancellable()) {
                throw new OrderStateException(
                    "Order {$id} cannot be cancelled from status {$order->status}."
                );
            }

            $cancelled = $this->repository->updateStatus($id, Order::STATUS_CANCELLED, [
                'cancelled_at' => now(),
            ]);

            Log::info('OrderService: order cancelled', ['order_id' => $id, 'reason' => $reason]);

            $this->eventPublisher->publishOrderCancelled($cancelled, $reason);

            return $cancelled;
        });
    }

    public function confirmOrder(string $id, string $tenantId): Order
    {
        return DB::transaction(function () use ($id, $tenantId): Order {
            $order = $this->fetchOrder($id, $tenantId);

            if (!$order->isConfirmable()) {
                throw new OrderStateException(
                    "Order {$id} cannot be confirmed from status {$order->status}."
                );
            }

            $confirmed = $this->repository->updateStatus($id, Order::STATUS_CONFIRMED, [
                'confirmed_at' => now(),
            ]);

            Log::info('OrderService: order confirmed', ['order_id' => $id]);

            $this->eventPublisher->publishOrderConfirmed($confirmed);

            return $confirmed;
        });
    }

    public function getOrder(string $id, string $tenantId): Order
    {
        return $this->fetchOrder($id, $tenantId);
    }

    public function listOrders(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $filters);
    }

    public function markAsFailed(string $id, string $tenantId, string $reason = ''): Order
    {
        return DB::transaction(function () use ($id, $tenantId, $reason): Order {
            $order = $this->fetchOrder($id, $tenantId);

            if ($order->isTerminal()) {
                throw new OrderStateException(
                    "Order {$id} is already in terminal state {$order->status}."
                );
            }

            $failed = $this->repository->updateStatus($id, Order::STATUS_FAILED);

            Log::warning('OrderService: order marked as failed', [
                'order_id' => $id,
                'reason'   => $reason,
            ]);

            $this->eventPublisher->publishOrderFailed($failed, $reason);

            return $failed;
        });
    }

    private function fetchOrder(string $id, string $tenantId): Order
    {
        $order = $this->repository->findById($id);

        if ($order === null || $order->tenant_id !== $tenantId) {
            throw new OrderNotFoundException("Order {$id} not found.");
        }

        return $order;
    }

    private function calculateTotal(array $items): float
    {
        return array_reduce($items, static function (float $carry, array $item): float {
            return $carry + ($item['unit_price'] * $item['quantity']);
        }, 0.0);
    }
}
