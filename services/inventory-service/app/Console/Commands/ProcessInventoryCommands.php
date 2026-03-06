<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Exceptions\InsufficientStockException;
use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\MessageBrokerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Long-running AMQP consumer that processes SAGA inventory commands.
 *
 * Supported commands: reserve_inventory, release_inventory, confirm_inventory
 */
class ProcessInventoryCommands extends Command
{
    protected $signature = 'inventory:process-commands';
    protected $description = 'Consume inventory command messages from RabbitMQ (SAGA Orchestrator)';

    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly InventoryEventPublisherInterface $publisher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue = config('inventory.queues.commands', 'inventory.commands');
        $this->info("Listening on queue: {$queue}");

        $this->broker->subscribe($queue, function (array $payload): bool {
            $command = $payload['command'] ?? 'unknown';

            try {
                match ($command) {
                    'reserve_inventory' => $this->handleReserve($payload),
                    'release_inventory' => $this->handleRelease($payload),
                    'confirm_inventory' => $this->handleConfirm($payload),
                    default             => Log::warning("Unknown inventory command: {$command}"),
                };
            } catch (Throwable $e) {
                Log::error("Error processing command [{$command}]", ['error' => $e->getMessage(), 'payload' => $payload]);

                if ($command === 'reserve_inventory') {
                    $this->publisher->publishReservationFailed(
                        $payload['saga_id'] ?? '',
                        $payload['order_id'] ?? '',
                        $e->getMessage(),
                    );
                }
            }

            return true; // always acknowledge to avoid infinite requeue loops
        });

        return self::SUCCESS;
    }

    private function handleReserve(array $payload): void
    {
        $reservation = $this->inventoryService->reserveItems(
            $payload['saga_id'],
            $payload['order_id'],
            $payload['tenant_id'],
            $payload['items'],
        );
        $this->publisher->publishInventoryReserved($reservation);
        $this->info("Reserved inventory for saga {$payload['saga_id']}");
    }

    private function handleRelease(array $payload): void
    {
        $reservation = $this->inventoryService->releaseReservation($payload['reservation_id']);
        $this->publisher->publishInventoryReleased($reservation);
        $this->info("Released reservation {$payload['reservation_id']}");
    }

    private function handleConfirm(array $payload): void
    {
        $reservation = $this->inventoryService->confirmReservation($payload['reservation_id']);
        $this->publisher->publishInventoryConfirmed($reservation);
        $this->info("Confirmed reservation {$payload['reservation_id']}");
    }
}
