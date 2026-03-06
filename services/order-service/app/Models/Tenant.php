<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

/**
 * Tenant Eloquent Model.
 *
 * @property string      $id
 * @property string      $name
 * @property string      $slug
 * @property string      $api_key
 * @property bool        $is_active
 * @property array|null  $settings
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'tenants';

    /** @var array<int, string> */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'api_key',
        'is_active',
        'settings',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'api_key',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tenant $model): void {
            if (empty($model->id)) {
                $model->id = Uuid::uuid4()->toString();
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'tenant_id');
    }
}
