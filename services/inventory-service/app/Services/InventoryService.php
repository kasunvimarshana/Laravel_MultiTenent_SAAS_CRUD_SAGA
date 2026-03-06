<?php
declare(strict_types=1);
namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ReservationNotFoundException;
use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\ReservationRepositoryInterface;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core inventory domain service.
 * Implements all stock reservation, release and confirmation logic
 * with database-level locking to prevent race conditions.
 */
class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {}

    /** {@inheritDoc} */
    public function checkAvailability(string $tenantId, array $items): bool
    {
        foreach ($items as $item) {
            $inventory = $this->inventoryRepository->findByProduct($item['product_id']);
            if ($inventory === null || $inventory->quantity_free < $item['quantity']) {
                return false;
            }
        }
        return true;
    }

    /** {@inheritDoc} */
    public function reserveItems(string $sagaId, string $orderId, string $tenantId, array $items): InventoryReservation
    {
        return DB::transaction(function () use ($sagaId, $orderId, $tenantId, $items) {
            $reservationItems = [];

            foreach ($items as $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventory->quantity_free < $item['quantity']) {
                    throw new InsufficientStockException(
                        $item['product_id'],
                        $item['quantity'],
                        $inventory->quantity_free,
                    );
                }

                $product = Product::find($item['product_id']);
                if ($product === null) {
                    throw new ProductNotFoundException($item['product_id']);
                }

                $this->inventoryRepository->decrementStock(
                    $item['product_id'],
                    $item['quantity'],
                    $inventory->warehouse_id,
                );

                $reservationItems[] = [
                    'product_id'               => $item['product_id'],
                    'quantity'                 => $item['quantity'],
                    'reservation_unit_price'   => $item['unit_price'] ?? 0,
                ];
            }

            $expiryMinutes = config('inventory.reservation_expiry_minutes', 30);

            $reservation = $this->reservationRepository->createReservation([
                'saga_id'    => $sagaId,
                'order_id'   => $orderId,
                'tenant_id'  => $tenantId,
                'items'      => $reservationItems,
                'expires_at' => now()->addMinutes($expiryMinutes),
            ]);

            Log::info('Inventory reserved', ['reservation_id' => $reservation->id, 'saga_id' => $sagaId]);

            return $reservation;
        });
    }

    /** {@inheritDoc} */
    public function releaseReservation(string $reservationId): InventoryReservation
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = $this->reservationRepository->findById($reservationId);

            if ($reservation === null) {
                throw new ReservationNotFoundException($reservationId);
            }

            if (!$reservation->isPending()) {
                Log::warning('Attempted to release non-pending reservation', ['id' => $reservationId, 'status' => $reservation->status]);
                return $reservation;
            }

            foreach ($reservation->items as $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])->lockForUpdate()->first();
                if ($inventory !== null) {
                    $this->inventoryRepository->incrementStock(
                        $item['product_id'],
                        $item['quantity'],
                        $inventory->warehouse_id,
                    );
                }
            }

            $released = $this->reservationRepository->releaseReservation($reservationId);
            Log::info('Inventory reservation released', ['reservation_id' => $reservationId]);

            return $released;
        });
    }

    /** {@inheritDoc} */
    public function confirmReservation(string $reservationId): InventoryReservation
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = $this->reservationRepository->findById($reservationId);

            if ($reservation === null) {
                throw new ReservationNotFoundException($reservationId);
            }

            if (!$reservation->isPending()) {
                Log::warning('Attempted to confirm non-pending reservation', ['id' => $reservationId]);
                return $reservation;
            }

            foreach ($reservation->items as $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])->lockForUpdate()->first();
                if ($inventory !== null) {
                    DB::table('inventories')
                        ->where('product_id', $item['product_id'])
                        ->update([
                            'quantity_reserved' => DB::raw("GREATEST(0, quantity_reserved - {$item['quantity']})"),
                            'quantity_on_hand'  => DB::raw("GREATEST(0, quantity_on_hand - {$item['quantity']})"),
                            'last_updated_at'   => now(),
                            'updated_at'        => now(),
                        ]);
                }
            }

            $confirmed = $this->reservationRepository->confirmReservation($reservationId);
            Log::info('Inventory reservation confirmed', ['reservation_id' => $reservationId]);

            return $confirmed;
        });
    }

    /** {@inheritDoc} */
    public function getStockLevel(string $productId, ?string $warehouseId = null): Inventory
    {
        $inventory = $this->inventoryRepository->findByProduct($productId, $warehouseId);

        if ($inventory === null) {
            throw new ProductNotFoundException($productId);
        }

        return $inventory;
    }

    /** {@inheritDoc} */
    public function restockProduct(string $productId, string $warehouseId, int $quantity, string $reason): Inventory
    {
        $inventory = $this->inventoryRepository->findByProduct($productId, $warehouseId);

        if ($inventory === null) {
            throw new ProductNotFoundException($productId);
        }

        DB::table('inventories')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update([
                'quantity_available' => DB::raw("quantity_available + {$quantity}"),
                'quantity_on_hand'   => DB::raw("quantity_on_hand + {$quantity}"),
                'last_updated_at'    => now(),
                'updated_at'         => now(),
            ]);

        Log::info('Product restocked', ['product_id' => $productId, 'quantity' => $quantity, 'reason' => $reason]);

        return $inventory->fresh();
    }
}
