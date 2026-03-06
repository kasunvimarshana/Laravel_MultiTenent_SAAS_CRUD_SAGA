<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TenantNotFoundException;
use App\Interfaces\TenantResolverInterface;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * TenantResolver
 *
 * Resolves tenants by ID, caching lookups to reduce database pressure.
 */
class TenantResolver implements TenantResolverInterface
{
    private ?Tenant $currentTenant = null;

    public function __construct(
        private readonly int $cacheTtl = 300,
    ) {
    }

    public function resolveTenant(string $identifier): Tenant
    {
        $tenant = $this->getTenantById($identifier);

        if ($tenant === null) {
            throw new TenantNotFoundException("Tenant '{$identifier}' not found.");
        }

        if (!$tenant->is_active) {
            throw new TenantNotFoundException("Tenant '{$identifier}' is inactive.");
        }

        $this->currentTenant = $tenant;

        return $tenant;
    }

    public function getTenantById(string $id): ?Tenant
    {
        return Cache::remember(
            "tenant:{$id}",
            $this->cacheTtl,
            static fn (): ?Tenant => Tenant::find($id),
        );
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }
}
