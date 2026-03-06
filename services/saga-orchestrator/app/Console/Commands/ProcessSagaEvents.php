<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SagaException;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaOrchestratorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSagaEvents – long-running Artisan command that consumes SAGA event
 * messages from RabbitMQ and forwards them to the SAGA orchestrator.
 *
 * Usage:
 *   php artisan saga:process-events [--queue=saga_events]
 */
final class ProcessSagaEvents extends Command
{
    /** @var string */
    protected $signature = 'saga:process-events
                            {--queue= : Override the queue name to consume from}';

    /** @var string */
    protected $description = 'Consume SAGA event messages from RabbitMQ and drive saga state transitions.';

    public function __construct(
        private readonly SagaOrchestratorInterface $orchestrator,
        private readonly MessageBrokerInterface    $broker,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queue = $this->option('queue')
            ?? config('saga.queues.saga_events', 'saga_events');

        $this->info("[ProcessSagaEvents] Listening on queue: {$queue}");
        Log::info('[ProcessSagaEvents] Starting consumer', ['queue' => $queue]);

        try {
            $this->broker->subscribe(
                queue:    $queue,
                callback: function (array $message, string $deliveryTag): void {
                    $this->processMessage($message, $deliveryTag);
                },
            );
        } catch (SagaException $e) {
            $this->error("[ProcessSagaEvents] Fatal error: {$e->getMessage()}");
            Log::error('[ProcessSagaEvents] Fatal consumer error', ['error' => $e->getMessage()]);
            return self::FAILURE;
        } finally {
            $this->broker->close();
        }

        return self::SUCCESS;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Validate and dispatch a single inbound message to the orchestrator.
     *
     * @param array<string, mixed> $message
     */
    private function processMessage(array $message, string $deliveryTag): void
    {
        $sagaId    = $message['saga_id']    ?? null;
        $eventName = $message['event_name'] ?? $message['event'] ?? null;

        if ($sagaId === null || $eventName === null) {
            $this->warn("[ProcessSagaEvents] Discarding malformed message (missing saga_id or event_name).");
            Log::warning('[ProcessSagaEvents] Malformed message rejected', ['message' => $message]);
            $this->broker->reject($deliveryTag, false);
            return;
        }

        try {
            $this->orchestrator->processSagaEvent($sagaId, $eventName, $message);
            $this->broker->acknowledge($deliveryTag);

            $this->line("[ProcessSagaEvents] Processed event '{$eventName}' for saga '{$sagaId}'.");
            Log::info('[ProcessSagaEvents] Event processed', [
                'saga_id'    => $sagaId,
                'event_name' => $eventName,
            ]);
        } catch (SagaException $e) {
            $this->error("[ProcessSagaEvents] SagaException: {$e->getMessage()}");
            Log::error('[ProcessSagaEvents] SagaException while processing event', [
                'saga_id'    => $sagaId,
                'event_name' => $eventName,
                'error'      => $e->getMessage(),
            ]);
            // Reject without re-queue to avoid poison-message loops.
            $this->broker->reject($deliveryTag, false);
        } catch (\Throwable $e) {
            $this->error("[ProcessSagaEvents] Unexpected error: {$e->getMessage()}");
            Log::error('[ProcessSagaEvents] Unexpected error', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Re-queue on unexpected errors for later retry.
            $this->broker->reject($deliveryTag, true);
        }
    }
}
