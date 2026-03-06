<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant model representing an isolated customer account in the SaaS platform.
 *
 * @property string      $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $domain
 * @property bool        $is_active
 * @property array|null  $settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Tenant extends Model
{
    use HasUuids;

    /** @var string */
    protected $table = 'tenants';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'is_active',
        'settings',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    /**
     * Products belonging to this tenant.
     *
     * @return HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    /**
     * Warehouses belonging to this tenant.
     *
     * @return HasMany<Warehouse>
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'tenant_id');
    }

    /**
     * Reservations belonging to this tenant.
     *
     * @return HasMany<InventoryReservation>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'tenant_id');
    }
}
