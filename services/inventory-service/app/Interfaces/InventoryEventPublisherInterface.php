<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Inventory;
use App\Models\InventoryReservation;

/**
 * Interface for publishing inventory domain events to the message broker.
 *
 * Each method corresponds to a domain event that downstream services
 * (e.g. order-service, notification-service) can subscribe to.
 */
interface InventoryEventPublisherInterface
{
    /**
     * Publish an event confirming that inventory has been successfully reserved.
     *
     * Routing key: inventory.reserved
     *
     * @param  InventoryReservation  $reservation  The created reservation
     * @return void
     */
    public function publishInventoryReserved(InventoryReservation $reservation): void;

    /**
     * Publish an event indicating that a reservation has been released.
     *
     * Routing key: inventory.released
     *
     * @param  InventoryReservation  $reservation  The released reservation
     * @return void
     */
    public function publishInventoryReleased(InventoryReservation $reservation): void;

    /**
     * Publish an event confirming that reserved stock has been permanently consumed.
     *
     * Routing key: inventory.confirmed
     *
     * @param  InventoryReservation  $reservation  The confirmed reservation
     * @return void
     */
    public function publishInventoryConfirmed(InventoryReservation $reservation): void;

    /**
     * Publish an event indicating that stock levels have changed for a product.
     *
     * Routing key: inventory.stock_updated
     *
     * @param  Inventory  $inventory  Updated inventory record
     * @param  string     $reason     Human-readable reason for the update
     * @return void
     */
    public function publishStockUpdated(Inventory $inventory, string $reason): void;

    /**
     * Publish an alert when a product's stock drops below its minimum level.
     *
     * Routing key: inventory.low_stock_alert
     *
     * @param  Inventory  $inventory  The inventory record that triggered the alert
     * @return void
     */
    public function publishLowStockAlert(Inventory $inventory): void;

    /**
     * Publish an event signalling that inventory reservation failed.
     *
     * Routing key: inventory.reservation_failed
     *
     * @param  string  $sagaId   SAGA correlation UUID
     * @param  string  $orderId  Order UUID
     * @param  string  $reason   Failure reason message
     * @return void
     */
    public function publishReservationFailed(string $sagaId, string $orderId, string $reason): void;
}
