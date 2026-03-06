<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

/**
 * Order Eloquent Model.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string|null $saga_id
 * @property string      $customer_id
 * @property string      $status
 * @property array       $items
 * @property float       $total_amount
 * @property string      $currency
 * @property array|null  $shipping_address
 * @property string|null $notes
 * @property string|null $cancelled_at
 * @property string|null $confirmed_at
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'orders';

    /** @var array<int, string> */
    protected $fillable = [
        'id',
        'tenant_id',
        'saga_id',
        'customer_id',
        'status',
        'items',
        'total_amount',
        'currency',
        'shipping_address',
        'notes',
        'cancelled_at',
        'confirmed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'items'            => 'array',
        'shipping_address' => 'array',
        'total_amount'     => 'float',
        'cancelled_at'     => 'datetime',
        'confirmed_at'     => 'datetime',
    ];

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FAILED    = 'FAILED';

    /** @var array<int, string> */
    public static array $statuses = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $model): void {
            if (empty($model->id)) {
                $model->id = Uuid::uuid4()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function scopeByTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer(Builder $query, string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_FAILED], true);
    }

    public function isCancellable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
