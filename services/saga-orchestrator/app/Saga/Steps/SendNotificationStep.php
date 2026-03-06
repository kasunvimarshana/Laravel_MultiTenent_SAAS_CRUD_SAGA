<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaStepInterface;
use App\Models\SagaTransaction;
use Illuminate\Support\Facades\Log;

/**
 * SendNotificationStep – final step of the Order SAGA.
 *
 * Publishes a "send_notification" command to the notification-service.
 * This is a fire-and-forget step; no compensation is required because
 * notifications are informational and do not modify system state.
 */
final class SendNotificationStep implements SagaStepInterface
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
            'command'     => 'send_notification',
            'order_id'    => $payload['order_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'type'        => 'order_confirmed',
            'channels'    => $payload['notification_channels'] ?? ['email'],
            'data'        => [
                'order_id'        => $payload['order_id'] ?? null,
                'total'           => $payload['total'] ?? 0,
                'currency'        => $payload['currency'] ?? 'USD',
                'transaction_id'  => $payload['transaction_id'] ?? null,
            ],
            'timestamp'   => now()->toIso8601String(),
        ];

        Log::info('[SendNotificationStep] Publishing send_notification command', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.notification_commands', 'notification_commands'),
            message: $command,
        );

        return ['command_published_at' => now()->toIso8601String()];
    }

    /**
     * No compensation required – notifications are informational only.
     *
     * {@inheritdoc}
     */
    public function compensate(SagaTransaction $saga, array $stepResult): void
    {
        Log::info('[SendNotificationStep] No compensation needed for notification step', [
            'saga_id' => $saga->saga_id,
        ]);
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'send_notification';
    }

    /** {@inheritdoc} */
    public function getTimeout(): int
    {
        return (int) config('saga.timeouts.send_notification', 15);
    }
}
