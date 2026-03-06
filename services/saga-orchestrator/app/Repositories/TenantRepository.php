<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\TenantRepositoryInterface;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of TenantRepositoryInterface.
 */
final class TenantRepository implements TenantRepositoryInterface
{
    /** {@inheritdoc} */
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    /** {@inheritdoc} */
    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    /** {@inheritdoc} */
    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    /** {@inheritdoc} */
    public function findActiveTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)
            ->where('is_active', true)
            ->first();
    }

    /** {@inheritdoc} */
    public function update(int $id, array $data): Tenant
    {
        $tenant = $this->findById($id);

        if ($tenant === null) {
            throw new \RuntimeException("Tenant not found: {$id}");
        }

        $tenant->update($data);

        return $tenant->fresh();
    }

    /** {@inheritdoc} */
    public function delete(int $id): bool
    {
        $tenant = $this->findById($id);

        return $tenant !== null && (bool) $tenant->delete();
    }

    /** {@inheritdoc} */
    public function allActive(): Collection
    {
        return Tenant::active()->get();
    }

    /** {@inheritdoc} */
    public function all(): Collection
    {
        return Tenant::all();
    }
}
