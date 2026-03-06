<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product model representing a sellable item in the inventory system.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $sku
 * @property string      $name
 * @property string|null $description
 * @property string|null $category
 * @property string      $unit_of_measure
 * @property int         $minimum_stock_level
 * @property bool        $is_active
 * @property array|null  $attributes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /** @var string */
    protected $table = 'products';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'category',
        'unit_of_measure',
        'minimum_stock_level',
        'is_active',
        'attributes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active'           => 'boolean',
        'minimum_stock_level' => 'integer',
        'attributes'          => 'array',
    ];

    /**
     * The inventory record associated with this product.
     *
     * @return HasOne<Inventory>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    /**
     * Scope: filter products to a specific tenant.
     *
     * @param  Builder<Product>  $query
     * @param  string            $tenantId
     * @return Builder<Product>
     */
    public function scopeByTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: filter to active products only.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter to products whose available stock is below minimum_stock_level.
     *
     * Joins the inventories table to compare quantity_available against the threshold.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->whereColumn('inventories.quantity_available', '<', 'products.minimum_stock_level')
            ->select('products.*');
    }
}
