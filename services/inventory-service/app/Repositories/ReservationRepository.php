<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ReservationRepositoryInterface;
use App\Models\InventoryReservation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of ReservationRepositoryInterface.
 */
class ReservationRepository implements ReservationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?InventoryReservation
    {
        return InventoryReservation::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function createReservation(array $data): InventoryReservation
    {
        return InventoryReservation::create(array_merge($data, [
            'status'      => InventoryReservation::STATUS_PENDING,
            'reserved_at' => now(),
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function confirmReservation(string $reservationId): InventoryReservation
    {
        $reservation = InventoryReservation::findOrFail($reservationId);
        $reservation->update([
            'status'       => InventoryReservation::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $reservation->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function releaseReservation(string $reservationId): InventoryReservation
    {
        $reservation = InventoryReservation::findOrFail($reservationId);
        $reservation->update([
            'status'      => InventoryReservation::STATUS_RELEASED,
            'released_at' => now(),
        ]);

        return $reservation->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function findBySaga(string $sagaId): Collection
    {
        return InventoryReservation::bySaga($sagaId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByOrder(string $orderId): Collection
    {
        return InventoryReservation::byOrder($orderId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findExpired(): Collection
    {
        return InventoryReservation::expired()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return InventoryReservation::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
