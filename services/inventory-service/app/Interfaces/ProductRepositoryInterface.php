<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface for Product repository operations.
 *
 * Defines the contract for all product data access methods,
 * enabling dependency inversion and easy testing via mocks.
 */
interface ProductRepositoryInterface
{
    /**
     * Find a product by its UUID.
     *
     * @param  string  $id  Product UUID
     * @return Product|null
     */
    public function findById(string $id): ?Product;

    /**
     * Retrieve a paginated list of products belonging to a tenant.
     *
     * @param  string  $tenantId  Tenant UUID
     * @param  int     $perPage   Items per page
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a product by its SKU within a specific tenant scope.
     *
     * @param  string  $tenantId  Tenant UUID
     * @param  string  $sku       Product SKU
     * @return Product|null
     */
    public function findBySku(string $tenantId, string $sku): ?Product;

    /**
     * Persist a new product record.
     *
     * @param  array<string, mixed>  $data  Validated product attributes
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update an existing product record.
     *
     * @param  string               $id    Product UUID
     * @param  array<string, mixed> $data  Attributes to update
     * @return Product
     */
    public function update(string $id, array $data): Product;

    /**
     * Soft-delete a product by its UUID.
     *
     * @param  string  $id  Product UUID
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Retrieve products whose current stock is below their minimum stock level.
     *
     * @param  string  $tenantId  Tenant UUID
     * @return Collection<int, Product>
     */
    public function findLowStock(string $tenantId): Collection;
}
