<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * OrderItem Eloquent Model.
 *
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property string $product_name
 * @property int    $quantity
 * @property float  $unit_price
 * @property float  $total_price
 */
class OrderItem extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'order_items';

    /** @var array<int, string> */
    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'float',
        'total_price' => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OrderItem $model): void {
            if (empty($model->id)) {
                $model->id = Uuid::uuid4()->toString();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
