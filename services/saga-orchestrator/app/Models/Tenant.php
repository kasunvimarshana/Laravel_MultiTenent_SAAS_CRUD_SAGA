<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant model.
 *
 * Represents a single tenant within the multi-tenant SaaS platform.
 * Each tenant is isolated at the database level and identified by a
 * unique domain name.
 *
 * @property int                 $id
 * @property string              $name
 * @property string              $domain
 * @property string              $database_name
 * @property bool                $is_active
 * @property string|null         $api_token          Hashed bearer token for service-to-service auth.
 * @property array<string, mixed>|null $settings      JSON configuration overrides.
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var string */
    protected $table = 'tenants';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'domain',
        'database_name',
        'is_active',
        'api_token',
        'settings',
    ];

    /** @var list<string> */
    protected $hidden = [
        'api_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
    ];

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /**
     * Scope to only active tenants.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Tenant> $query
     * @return \Illuminate\Database\Eloquent\Builder<Tenant>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
