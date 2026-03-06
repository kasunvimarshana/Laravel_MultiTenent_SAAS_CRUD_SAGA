<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaStepInterface;
use App\Models\SagaTransaction;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPaymentStep – third step of the Order SAGA.
 *
 * Publishes a "process_payment" command to the payment-service and expects a
 * "payment_processed" event back.  On compensation, publishes "refund_payment".
 */
final class ProcessPaymentStep implements SagaStepInterface
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
    ) {}

    /** {@inheritdoc} */
    public function execute(SagaTransaction $saga, array $payload): array
    {
        $command = [
            'saga_id'          => $saga->saga_id,
            'tenant_id'        => $saga->tenant_id,
            'command'          => 'process_payment',
            'order_id'         => $payload['order_id'] ?? null,
            'customer_id'      => $payload['customer_id'] ?? null,
            'amount'           => $payload['total'] ?? 0,
            'currency'         => $payload['currency'] ?? 'USD',
            'payment_method'   => $payload['payment_method'] ?? null,
            'billing_address'  => $payload['billing_address'] ?? null,
            'timestamp'        => now()->toIso8601String(),
        ];

        Log::info('[ProcessPaymentStep] Publishing process_payment command', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.payment_commands', 'payment_commands'),
            message: $command,
        );

        return ['command_published_at' => now()->toIso8601String()];
    }

    /** {@inheritdoc} */
    public function compensate(SagaTransaction $saga, array $stepResult): void
    {
        $command = [
            'saga_id'        => $saga->saga_id,
            'tenant_id'      => $saga->tenant_id,
            'command'        => 'refund_payment',
            'order_id'       => $stepResult['order_id'] ?? ($saga->payload['order_id'] ?? null),
            'transaction_id' => $stepResult['transaction_id'] ?? null,
            'amount'         => $stepResult['amount'] ?? ($saga->payload['total'] ?? 0),
            'currency'       => $stepResult['currency'] ?? ($saga->payload['currency'] ?? 'USD'),
            'reason'         => 'saga_compensation',
            'timestamp'      => now()->toIso8601String(),
        ];

        Log::info('[ProcessPaymentStep] Publishing refund_payment compensation', [
            'saga_id' => $saga->saga_id,
        ]);

        $this->broker->publish(
            queue:   config('saga.queues.payment_commands', 'payment_commands'),
            message: $command,
        );
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'process_payment';
    }

    /** {@inheritdoc} */
    public function getTimeout(): int
    {
        return (int) config('saga.timeouts.process_payment', 60);
    }
}
