<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\OrderEventPublisherInterface;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * OrderEventPublisher
 *
 * Publishes order domain events to RabbitMQ.
 */
class OrderEventPublisher implements OrderEventPublisherInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly string $exchange,
    ) {
    }

    public function publishOrderCreated(Order $order): void
    {
        $payload = $this->buildPayload('order_created', $order, [
            'items'            => $order->items,
            'total_amount'     => $order->total_amount,
            'currency'         => $order->currency,
            'shipping_address' => $order->shipping_address,
        ]);

        $this->publish('order.created', $payload);
    }

    public function publishOrderCancelled(Order $order, string $reason = ''): void
    {
        $payload = $this->buildPayload('order_cancelled', $order, [
            'reason'       => $reason,
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
        ]);

        $this->publish('order.cancelled', $payload);
    }

    public function publishOrderConfirmed(Order $order): void
    {
        $payload = $this->buildPayload('order_confirmed', $order, [
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
        ]);

        $this->publish('order.confirmed', $payload);
    }

    public function publishOrderFailed(Order $order, string $reason = ''): void
    {
        $payload = $this->buildPayload('order_failed', $order, [
            'reason' => $reason,
        ]);

        $this->publish('order.failed', $payload);
    }

    private function buildPayload(string $eventType, Order $order, array $extra = []): array
    {
        return array_merge([
            'event_type'  => $eventType,
            'order_id'    => $order->id,
            'tenant_id'   => $order->tenant_id,
            'saga_id'     => $order->saga_id,
            'customer_id' => $order->customer_id,
            'status'      => $order->status,
            'timestamp'   => now()->toIso8601String(),
        ], $extra);
    }

    private function publish(string $routingKey, array $payload): void
    {
        Log::info('OrderEventPublisher: publishing event', [
            'routing_key' => $routingKey,
            'order_id'    => $payload['order_id'],
        ]);

        $this->broker->publish($this->exchange, $routingKey, $payload);
    }
}
