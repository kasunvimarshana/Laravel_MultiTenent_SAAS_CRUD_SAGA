<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\OrderNotFoundException;
use App\Exceptions\OrderStateException;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\OrderServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * ProcessOrderCommands
 *
 * Long-running Artisan command consuming RabbitMQ order commands from the SAGA Orchestrator.
 */
class ProcessOrderCommands extends Command
{
    protected $signature = 'order:process-commands';

    protected $description = 'Process incoming SAGA order commands from RabbitMQ';

    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly OrderServiceInterface  $orderService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue = config('rabbitmq.queues.order_commands', 'order.commands');

        $this->info("Listening on queue: {$queue}");
        Log::info('ProcessOrderCommands: starting consumer', ['queue' => $queue]);

        $this->broker->subscribe($queue, function (AMQPMessage $message): void {
            $this->processMessage($message);
        });

        return self::SUCCESS;
    }

    private function processMessage(AMQPMessage $message): void
    {
        $body = $message->getBody();

        try {
            $payload     = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $commandType = $payload['command'] ?? null;
            $tenantId    = $payload['tenant_id'] ?? null;

            Log::info('ProcessOrderCommands: received command', [
                'command'   => $commandType,
                'tenant_id' => $tenantId,
            ]);

            match ($commandType) {
                'create_order' => $this->handleCreateOrder($payload),
                'cancel_order' => $this->handleCancelOrder($payload),
                default        => $this->handleUnknownCommand((string) $commandType, $payload),
            };

            $this->broker->acknowledge($message);
        } catch (\JsonException $e) {
            Log::error('ProcessOrderCommands: invalid JSON payload', ['error' => $e->getMessage()]);
            $this->broker->reject($message, false);
        } catch (OrderNotFoundException | OrderStateException $e) {
            Log::warning('ProcessOrderCommands: business rule violation', ['error' => $e->getMessage()]);
            $this->broker->reject($message, false);
        } catch (Throwable $e) {
            Log::error('ProcessOrderCommands: unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->broker->reject($message, true);
        }
    }

    private function handleCreateOrder(array $payload): void
    {
        $tenantId = $payload['tenant_id'] ?? throw new \InvalidArgumentException('Missing tenant_id');
        $sagaId   = $payload['saga_id']   ?? null;

        // Saga-orchestrator sends order data at the top level of the payload.
        $data = [
            'customer_id'      => $payload['customer_id'] ?? null,
            'items'            => $payload['items'] ?? [],
            'currency'         => $payload['currency'] ?? 'USD',
            'shipping_address' => $payload['shipping_address'] ?? null,
            'notes'            => $payload['notes'] ?? null,
        ];

        $order = $this->orderService->createOrder($tenantId, $data, $sagaId);

        $this->info("Order created: {$order->id}");
    }

    private function handleCancelOrder(array $payload): void
    {
        $tenantId = $payload['tenant_id'] ?? throw new \InvalidArgumentException('Missing tenant_id');
        $orderId  = $payload['order_id']  ?? throw new \InvalidArgumentException('Missing order_id');
        $reason   = $payload['reason']    ?? '';

        $order = $this->orderService->cancelOrder($orderId, $tenantId, $reason);

        $this->info("Order cancelled: {$order->id}");
    }

    private function handleUnknownCommand(string $commandType, array $payload): void
    {
        Log::warning('ProcessOrderCommands: unknown command type', [
            'command' => $commandType,
            'payload' => $payload,
        ]);

        $this->warn("Unknown command type: {$commandType}");
    }
}
