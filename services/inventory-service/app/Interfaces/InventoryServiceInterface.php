<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Inventory;
use App\Models\InventoryReservation;

/**
 * Interface for the core Inventory domain service.
 *
 * Encapsulates business rules for stock checking, reservation lifecycle,
 * and restocking operations used by both HTTP controllers and SAGA consumers.
 */
interface InventoryServiceInterface
{
    /**
     * Check whether all requested items can be fulfilled from available stock.
     *
     * @param  string                $tenantId  Tenant UUID
     * @param  array<int, array{product_id: string, quantity: int}>  $items  Items to check
     * @return bool  True if all items are available
     */
    public function checkAvailability(string $tenantId, array $items): bool;

    /**
     * Reserve inventory items for a SAGA transaction.
     *
     * Decrements quantity_available, increments quantity_reserved,
     * and persists a new PENDING InventoryReservation – all within a
     * single database transaction with row-level locking.
     *
     * @param  string  $sagaId   SAGA correlation UUID
     * @param  string  $orderId  Order UUID
     * @param  string  $tenantId Tenant UUID
     * @param  array<int, array{product_id: string, quantity: int, unit_price: float}>  $items
     * @return InventoryReservation
     *
     * @throws \App\Exceptions\InsufficientStockException
     * @throws \App\Exceptions\ProductNotFoundException
     */
    public function reserveItems(
        string $sagaId,
        string $orderId,
        string $tenantId,
        array $items,
    ): InventoryReservation;

    /**
     * Release a reservation, returning stock to the available pool.
     *
     * Increments quantity_available, decrements quantity_reserved,
     * and transitions the reservation to RELEASED status.
     *
     * @param  string  $reservationId  Reservation UUID
     * @return InventoryReservation
     *
     * @throws \App\Exceptions\ReservationNotFoundException
     */
    public function releaseReservation(string $reservationId): InventoryReservation;

    /**
     * Confirm a reservation, committing stock as permanently consumed.
     *
     * Decrements quantity_on_hand (physical stock), decrements
     * quantity_reserved, and transitions the reservation to CONFIRMED.
     *
     * @param  string  $reservationId  Reservation UUID
     * @return InventoryReservation
     *
     * @throws \App\Exceptions\ReservationNotFoundException
     */
    public function confirmReservation(string $reservationId): InventoryReservation;

    /**
     * Return the current stock snapshot for a product.
     *
     * @param  string       $productId   Product UUID
     * @param  string|null  $warehouseId Optional warehouse scope
     * @return Inventory
     *
     * @throws \App\Exceptions\ProductNotFoundException
     */
    public function getStockLevel(string $productId, ?string $warehouseId = null): Inventory;

    /**
     * Add stock to a product's inventory (goods receipt / restock event).
     *
     * @param  string  $productId   Product UUID
     * @param  string  $warehouseId Warehouse UUID
     * @param  int     $quantity    Units to add
     * @param  string  $reason      Human-readable reason (e.g. "PO-1234 received")
     * @return Inventory
     *
     * @throws \App\Exceptions\ProductNotFoundException
     */
    public function restockProduct(
        string $productId,
        string $warehouseId,
        int $quantity,
        string $reason,
    ): Inventory;
}
