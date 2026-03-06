<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory model representing the stock levels for a product at a warehouse.
 *
 * @property string      $id
 * @property string      $product_id
 * @property string      $tenant_id
 * @property string      $warehouse_id
 * @property int         $quantity_available
 * @property int         $quantity_reserved
 * @property int         $quantity_on_hand
 * @property \Carbon\Carbon|null $last_updated_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Inventory extends Model
{
    use HasFactory;
    use HasUuids;

    /** @var string */
    protected $table = 'inventories';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'tenant_id',
        'warehouse_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_on_hand',
        'last_updated_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved'  => 'integer',
        'quantity_on_hand'   => 'integer',
        'last_updated_at'    => 'datetime',
    ];

    /**
     * The product this inventory record belongs to.
     *
     * @return BelongsTo<Product, Inventory>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * The warehouse this inventory record belongs to.
     *
     * @return BelongsTo<Warehouse, Inventory>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Computed attribute: units free to be reserved (available minus already reserved).
     *
     * @return int
     */
    public function getQuantityFreeAttribute(): int
    {
        return max(0, $this->quantity_available - $this->quantity_reserved);
    }

    /**
     * Append computed attributes when serialising.
     *
     * @var list<string>
     */
    protected $appends = ['quantity_free'];

    /**
     * Scope: filter records to a specific tenant.
     *
     * @param  Builder<Inventory>  $query
     * @param  string              $tenantId
     * @return Builder<Inventory>
     */
    public function scopeByTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('inventories.tenant_id', $tenantId);
    }
}
