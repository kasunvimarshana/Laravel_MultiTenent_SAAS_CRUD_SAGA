<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse model representing a physical or virtual stock location.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $name
 * @property string|null $location
 * @property bool        $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Warehouse extends Model
{
    use HasUuids;

    /** @var string */
    protected $table = 'warehouses';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'name',
        'location',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * All inventory records stored at this warehouse.
     *
     * @return HasMany<Inventory>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    /**
     * Scope: filter to a specific tenant.
     *
     * @param  Builder<Warehouse>  $query
     * @param  string              $tenantId
     * @return Builder<Warehouse>
     */
    public function scopeByTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: active warehouses only.
     *
     * @param  Builder<Warehouse>  $query
     * @return Builder<Warehouse>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
