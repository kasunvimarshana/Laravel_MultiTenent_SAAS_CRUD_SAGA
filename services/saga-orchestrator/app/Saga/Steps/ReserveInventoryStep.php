<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaStepInterface;
use App\Models\SagaTransaction;
use Illuminate\Support\Facades\Log;

/**
 * ReserveInventoryStep – second step of the Order SAGA.
 *
 * Publishes a "reserve_inventory" command to the inventory-service and expects
 * an "inventory_reserved" event back.  On compensation, publishes "release_inventory".
 */
final class ReserveInventoryStep implements SagaStepInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
    ) {}

    /** {@inheritdoc} */
    public function execute(SagaTransaction $saga, array $payload): array
    {
        $command = [
            'saga_id'   => $saga->saga_id,
            'tenant_id' => $saga->tenant_id,
            'command'   => 'reserve_inventory',
            'order_id'  => $payload['order_id'] ?? null,
            'items'     => $payload['items'] ?? [],
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('[ReserveInventoryStep] Publishing reserve_inventory command', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.inventory_commands', 'inventory_commands'),
            message: $command,
        );

        return ['command_published_at' => now()->toIso8601String()];
    }

    /** {@inheritdoc} */
    public function compensate(SagaTransaction $saga, array $stepResult): void
    {
        $command = [
            'saga_id'      => $saga->saga_id,
            'tenant_id'    => $saga->tenant_id,
            'command'      => 'release_inventory',
            'order_id'     => $stepResult['order_id'] ?? ($saga->payload['order_id'] ?? null),
            'reservation_id' => $stepResult['reservation_id'] ?? null,
            'reason'       => 'saga_compensation',
            'timestamp'    => now()->toIso8601String(),
        ];

        Log::info('[ReserveInventoryStep] Publishing release_inventory compensation', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.inventory_commands', 'inventory_commands'),
            message: $command,
        );
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'reserve_inventory';
    }

    /** {@inheritdoc} */
    public function getTimeout(): int
    {
        return (int) config('saga.timeouts.reserve_inventory', 30);
    }
}
