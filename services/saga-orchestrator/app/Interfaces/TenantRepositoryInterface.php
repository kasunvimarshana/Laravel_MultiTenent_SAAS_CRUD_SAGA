<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository contract for multi-tenant management.
 */
interface TenantRepositoryInterface
{
    /**
     * Create a new tenant record.
     *
     * @param  array<string, mixed> $data
     * @return Tenant
     */
    public function create(array $data): Tenant;

    /**
     * Find a tenant by primary key.
     *
     * @param  int $id
     * @return Tenant|null
     */
    public function findById(int $id): ?Tenant;

    /**
     * Find a tenant by its unique domain name.
     *
     * @param  string $domain
     * @return Tenant|null
     */
    public function findByDomain(string $domain): ?Tenant;

    /**
     * Find an active tenant by its unique domain name.
     *
     * @param  string $domain
     * @return Tenant|null
     */
    public function findActiveTenantByDomain(string $domain): ?Tenant;

    /**
     * Update tenant attributes.
     *
     * @param  int                  $id
     * @param  array<string, mixed> $data
     * @return Tenant
     */
    public function update(int $id, array $data): Tenant;

    /**
     * Delete a tenant.
     *
     * @param  int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Return all active tenants.
     *
     * @return Collection<int, Tenant>
     */
    public function allActive(): Collection;

    /**
     * Return all tenants (active and inactive).
     *
     * @return Collection<int, Tenant>
     */
    public function all(): Collection;
}
