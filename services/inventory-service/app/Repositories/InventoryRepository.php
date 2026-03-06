<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\InventoryRepositoryInterface;
use App\Models\Inventory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of InventoryRepositoryInterface.
 *
 * All stock mutation methods use DB::table with atomic updates to avoid
 * race conditions in concurrent environments.
 */
class InventoryRepository implements InventoryRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findByProduct(string $productId, ?string $warehouseId = null): ?Inventory
    {
        $query = Inventory::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->first();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStock(string $inventoryId, array $quantities): Inventory
    {
        $inventory = Inventory::findOrFail($inventoryId);
        $inventory->update(array_merge($quantities, ['last_updated_at' => now()]));

        return $inventory->fresh();
    }

    /**
     * {@inheritDoc}
     *
     * Uses an atomic UPDATE to avoid lost-update anomalies.
     * The row is re-fetched after the update to return a consistent model.
     */
    public function decrementStock(string $productId, int $quantity, string $warehouseId): Inventory
    {
        DB::table('inventories')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update([
                'quantity_available' => DB::raw("quantity_available - {$quantity}"),
                'quantity_reserved'  => DB::raw("quantity_reserved + {$quantity}"),
                'last_updated_at'    => now(),
                'updated_at'         => now(),
            ]);

        return Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->firstOrFail();
    }

    /**
     * {@inheritDoc}
     */
    public function incrementStock(string $productId, int $quantity, string $warehouseId): Inventory
    {
        DB::table('inventories')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update([
                'quantity_available' => DB::raw("quantity_available + {$quantity}"),
                'quantity_reserved'  => DB::raw("GREATEST(0, quantity_reserved - {$quantity})"),
                'last_updated_at'    => now(),
                'updated_at'         => now(),
            ]);

        return Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->firstOrFail();
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return Inventory::byTenant($tenantId)
            ->with(['product', 'warehouse'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }
}
