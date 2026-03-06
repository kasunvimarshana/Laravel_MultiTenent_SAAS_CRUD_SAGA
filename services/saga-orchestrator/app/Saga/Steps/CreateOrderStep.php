<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Exceptions\SagaException;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaStepInterface;
use App\Models\SagaTransaction;
use Illuminate\Support\Facades\Log;

/**
 * CreateOrderStep – first step of the Order SAGA.
 *
 * Publishes a "create_order" command to the order-service and expects an
 * "order_created" event back.  On compensation, publishes "cancel_order".
 */
final class CreateOrderStep implements SagaStepInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
    ) {}

    /** {@inheritdoc} */
    public function execute(SagaTransaction $saga, array $payload): array
    {
        $command = [
            'saga_id'     => $saga->saga_id,
            'tenant_id'   => $saga->tenant_id,
            'command'     => 'create_order',
            'customer_id' => $payload['customer_id'] ?? null,
            'items'       => $payload['items'] ?? [],
            'total'       => $payload['total'] ?? 0,
            'currency'    => $payload['currency'] ?? 'USD',
            'metadata'    => $payload['metadata'] ?? [],
            'timestamp'   => now()->toIso8601String(),
        ];

        Log::info('[CreateOrderStep] Publishing create_order command', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.order_commands', 'order_commands'),
            message: $command,
        );

        return ['command_published_at' => now()->toIso8601String()];
    }

    /** {@inheritdoc} */
    public function compensate(SagaTransaction $saga, array $stepResult): void
    {
        $command = [
            'saga_id'   => $saga->saga_id,
            'tenant_id' => $saga->tenant_id,
            'command'   => 'cancel_order',
            'order_id'  => $stepResult['order_id'] ?? null,
            'reason'    => 'saga_compensation',
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('[CreateOrderStep] Publishing cancel_order compensation', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.order_commands', 'order_commands'),
            message: $command,
        );
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'create_order';
    }

    /** {@inheritdoc} */
    public function getTimeout(): int
    {
        return (int) config('saga.timeouts.create_order', 30);
    }
}
