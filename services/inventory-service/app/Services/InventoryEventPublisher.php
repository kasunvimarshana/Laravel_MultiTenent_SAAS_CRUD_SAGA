<?php
declare(strict_types=1);
namespace App\Services;

use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\MessageBrokerInterface;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\Log;

/**
 * Publishes inventory domain events to the AMQP exchange.
 */
class InventoryEventPublisher implements InventoryEventPublisherInterface
{
    private const EXCHANGE = 'inventory';

    public function __construct(private readonly MessageBrokerInterface $broker) {}

    /** {@inheritDoc} */
    public function publishInventoryReserved(InventoryReservation $reservation): void
    {
        $this->publish('inventory.reserved', [
            'event'          => 'inventory.reserved',
            'reservation_id' => $reservation->id,
            'saga_id'        => $reservation->saga_id,
            'order_id'       => $reservation->order_id,
            'tenant_id'      => $reservation->tenant_id,
            'items'          => $reservation->items,
            'reserved_at'    => $reservation->reserved_at?->toIso8601String(),
            'expires_at'     => $reservation->expires_at?->toIso8601String(),
        ]);
    }

    /** {@inheritDoc} */
    public function publishInventoryReleased(InventoryReservation $reservation): void
    {
        $this->publish('inventory.released', [
            'event'          => 'inventory.released',
            'reservation_id' => $reservation->id,
            'saga_id'        => $reservation->saga_id,
            'order_id'       => $reservation->order_id,
            'tenant_id'      => $reservation->tenant_id,
            'released_at'    => $reservation->released_at?->toIso8601String(),
        ]);
    }

    /** {@inheritDoc} */
    public function publishInventoryConfirmed(InventoryReservation $reservation): void
    {
        $this->publish('inventory.confirmed', [
            'event'          => 'inventory.confirmed',
            'reservation_id' => $reservation->id,
            'saga_id'        => $reservation->saga_id,
            'order_id'       => $reservation->order_id,
            'tenant_id'      => $reservation->tenant_id,
            'confirmed_at'   => $reservation->confirmed_at?->toIso8601String(),
        ]);
    }

    /** {@inheritDoc} */
    public function publishStockUpdated(Inventory $inventory, string $reason): void
    {
        $this->publish('inventory.stock_updated', [
            'event'              => 'inventory.stock_updated',
            'inventory_id'       => $inventory->id,
            'product_id'         => $inventory->product_id,
            'tenant_id'          => $inventory->tenant_id,
            'quantity_available' => $inventory->quantity_available,
            'quantity_reserved'  => $inventory->quantity_reserved,
            'quantity_on_hand'   => $inventory->quantity_on_hand,
            'reason'             => $reason,
        ]);
    }

    /** {@inheritDoc} */
    public function publishLowStockAlert(Inventory $inventory): void
    {
        $this->publish('inventory.low_stock_alert', [
            'event'              => 'inventory.low_stock_alert',
            'inventory_id'       => $inventory->id,
            'product_id'         => $inventory->product_id,
            'tenant_id'          => $inventory->tenant_id,
            'quantity_available' => $inventory->quantity_available,
        ]);
    }

    /** {@inheritDoc} */
    public function publishReservationFailed(string $sagaId, string $orderId, string $reason): void
    {
        $this->publish('inventory.reservation_failed', [
            'event'    => 'inventory.reservation_failed',
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
            'reason'   => $reason,
        ]);
    }

    private function publish(string $routingKey, array $payload): void
    {
        try {
            $this->broker->publish(self::EXCHANGE, $routingKey, $payload);
        } catch (\Throwable $e) {
            Log::error('Failed to publish inventory event', ['routing_key' => $routingKey, 'error' => $e->getMessage()]);
        }
    }
}
