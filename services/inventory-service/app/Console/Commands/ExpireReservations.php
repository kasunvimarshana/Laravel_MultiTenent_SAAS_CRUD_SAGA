<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\ReservationRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled command that finds expired PENDING reservations and releases their stock.
 *
 * Intended to run every minute via the Laravel scheduler.
 */
class ExpireReservations extends Command
{
    protected $signature = 'inventory:expire-reservations';
    protected $description = 'Release stock for PENDING reservations that have passed their TTL';

    public function __construct(
        private readonly ReservationRepositoryInterface $reservations,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly InventoryEventPublisherInterface $publisher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = $this->reservations->findExpired();

        if ($expired->isEmpty()) {
            $this->info('No expired reservations found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$expired->count()} expired reservation(s)...");

        foreach ($expired as $reservation) {
            try {
                $released = $this->inventoryService->releaseReservation($reservation->id);
                $released->update(['status' => 'EXPIRED']);
                $this->publisher->publishInventoryReleased($released);
                Log::info('Expired reservation released', ['id' => $reservation->id]);
            } catch (Throwable $e) {
                Log::error('Failed to expire reservation', ['id' => $reservation->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
