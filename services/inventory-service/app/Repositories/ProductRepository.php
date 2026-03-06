<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of ProductRepositoryInterface.
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return Product::byTenant($tenantId)
            ->with('inventory')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySku(string $tenantId, string $sku): ?Product
    {
        return Product::byTenant($tenantId)
            ->where('sku', $sku)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): bool
    {
        $product = Product::findOrFail($id);

        return (bool) $product->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function findLowStock(string $tenantId): Collection
    {
        return Product::byTenant($tenantId)
            ->active()
            ->lowStock()
            ->with('inventory')
            ->get();
    }
}
