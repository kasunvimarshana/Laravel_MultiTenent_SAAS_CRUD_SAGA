<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * InventoryReservation represents a hold placed on stock during a SAGA transaction.
 *
 * Status lifecycle:
 *   PENDING → CONFIRMED (order fulfilled)
 *   PENDING → RELEASED  (compensation / cancellation)
 *   PENDING → EXPIRED   (TTL exceeded, cleaned up by scheduler)
 *
 * @property string      $id
 * @property string      $saga_id
 * @property string      $order_id
 * @property string      $tenant_id
 * @property string      $status
 * @property array       $items   [{product_id, quantity, reservation_unit_price}]
 * @property \Carbon\Carbon $reserved_at
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $released_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InventoryReservation extends Model
{
    use HasFactory;
    use HasUuids;

    // -------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------

    /** @var string */
    public const STATUS_PENDING = 'PENDING';

    /** @var string */
    public const STATUS_CONFIRMED = 'CONFIRMED';

    /** @var string */
    public const STATUS_RELEASED = 'RELEASED';

    /** @var string */
    public const STATUS_EXPIRED = 'EXPIRED';

    /** @var string */
    protected $table = 'inventory_reservations';

    /** @var list<string> */
    protected $fillable = [
        'saga_id',
        'order_id',
        'tenant_id',
        'status',
        'items',
        'reserved_at',
        'confirmed_at',
        'released_at',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'items'        => 'array',
        'reserved_at'  => 'datetime',
        'confirmed_at' => 'datetime',
        'released_at'  => 'datetime',
        'expires_at'   => 'datetime',
    ];

    // -------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------

    /**
     * Scope: reservations for a specific SAGA.
     *
     * @param  Builder<InventoryReservation>  $query
     * @param  string                         $sagaId
     * @return Builder<InventoryReservation>
     */
    public function scopeBySaga(Builder $query, string $sagaId): Builder
    {
        return $query->where('saga_id', $sagaId);
    }

    /**
     * Scope: reservations for a specific order.
     *
     * @param  Builder<InventoryReservation>  $query
     * @param  string                         $orderId
     * @return Builder<InventoryReservation>
     */
    public function scopeByOrder(Builder $query, string $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: filter by reservation status.
     *
     * @param  Builder<InventoryReservation>  $query
     * @param  string                         $status
     * @return Builder<InventoryReservation>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: PENDING reservations whose TTL has elapsed.
     *
     * @param  Builder<InventoryReservation>  $query
     * @return Builder<InventoryReservation>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '<', now());
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /**
     * Whether the reservation is still in the PENDING state.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Whether the reservation has been confirmed.
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Whether the reservation has been released.
     *
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }
}
