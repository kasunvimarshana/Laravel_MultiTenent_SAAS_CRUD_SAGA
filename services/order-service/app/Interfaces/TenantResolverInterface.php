<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Tenant;

/**
 * Interface TenantResolverInterface
 *
 * Defines the contract for tenant resolution in the multi-tenant system.
 */
interface TenantResolverInterface
{
    /**
     * @throws \App\Exceptions\TenantNotFoundException
     */
    public function resolveTenant(string $identifier): Tenant;
    public function getTenantById(string $id): ?Tenant;
    public function getCurrentTenant(): ?Tenant;
}
