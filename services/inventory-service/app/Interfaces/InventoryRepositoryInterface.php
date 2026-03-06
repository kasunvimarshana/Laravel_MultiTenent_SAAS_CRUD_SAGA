<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Inventory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface for Inventory repository operations.
 *
 * Abstracts all database interactions for stock-level management,
 * allowing the service layer to remain database-agnostic.
 */
interface InventoryRepositoryInterface
{
    /**
     * Find the inventory record for a specific product (and optional warehouse).
     *
     * @param  string       $productId   Product UUID
     * @param  string|null  $warehouseId Optional Warehouse UUID
     * @return Inventory|null
     */
    public function findByProduct(string $productId, ?string $warehouseId = null): ?Inventory;

    /**
     * Overwrite the stock quantities for a given inventory record.
     *
     * @param  string               $inventoryId  Inventory record UUID
     * @param  array<string, mixed> $quantities   Keys: quantity_available, quantity_reserved, quantity_on_hand
     * @return Inventory
     */
    public function updateStock(string $inventoryId, array $quantities): Inventory;

    /**
     * Atomically decrement the available quantity for a product.
     *
     * @param  string  $productId   Product UUID
     * @param  int     $quantity    Amount to decrement
     * @param  string  $warehouseId Warehouse UUID
     * @return Inventory
     */
    public function decrementStock(string $productId, int $quantity, string $warehouseId): Inventory;

    /**
     * Atomically increment the available quantity for a product.
     *
     * @param  string  $productId   Product UUID
     * @param  int     $quantity    Amount to increment
     * @param  string  $warehouseId Warehouse UUID
     * @return Inventory
     */
    public function incrementStock(string $productId, int $quantity, string $warehouseId): Inventory;

    /**
     * Retrieve a paginated list of inventory records for a tenant.
     *
     * @param  string  $tenantId  Tenant UUID
     * @param  int     $perPage   Items per page
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator;
}
