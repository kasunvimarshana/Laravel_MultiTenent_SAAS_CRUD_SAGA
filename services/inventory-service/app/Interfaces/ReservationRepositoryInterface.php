<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\InventoryReservation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface for InventoryReservation repository operations.
 *
 * Abstracts persistence of reservation lifecycle events
 * (create, confirm, release) used in the SAGA pattern.
 */
interface ReservationRepositoryInterface
{
    /**
     * Find a reservation by its UUID.
     *
     * @param  string  $id  Reservation UUID
     * @return InventoryReservation|null
     */
    public function findById(string $id): ?InventoryReservation;

    /**
     * Persist a new reservation record with PENDING status.
     *
     * @param  array<string, mixed>  $data  Validated reservation attributes
     * @return InventoryReservation
     */
    public function createReservation(array $data): InventoryReservation;

    /**
     * Transition an existing reservation to CONFIRMED status.
     *
     * @param  string  $reservationId  Reservation UUID
     * @return InventoryReservation
     */
    public function confirmReservation(string $reservationId): InventoryReservation;

    /**
     * Transition an existing reservation to RELEASED status.
     *
     * @param  string  $reservationId  Reservation UUID
     * @return InventoryReservation
     */
    public function releaseReservation(string $reservationId): InventoryReservation;

    /**
     * Find all reservations associated with a SAGA transaction.
     *
     * @param  string  $sagaId  SAGA correlation UUID
     * @return Collection<int, InventoryReservation>
     */
    public function findBySaga(string $sagaId): Collection;

    /**
     * Find all reservations associated with an order.
     *
     * @param  string  $orderId  Order UUID
     * @return Collection<int, InventoryReservation>
     */
    public function findByOrder(string $orderId): Collection;

    /**
     * Find all PENDING reservations that have passed their expiry timestamp.
     *
     * @return Collection<int, InventoryReservation>
     */
    public function findExpired(): Collection;

    /**
     * Retrieve a paginated list of reservations for a tenant.
     *
     * @param  string  $tenantId  Tenant UUID
     * @param  int     $perPage   Items per page
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, int $perPage = 20): LengthAwarePaginator;
}
