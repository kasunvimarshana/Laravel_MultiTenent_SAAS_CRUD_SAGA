<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Order;

/**
 * Interface OrderEventPublisherInterface
 *
 * Defines the contract for publishing order domain events.
 */
interface OrderEventPublisherInterface
{
    public function publishOrderCreated(Order $order): void;
    public function publishOrderCancelled(Order $order, string $reason = ''): void;
    public function publishOrderConfirmed(Order $order): void;
    public function publishOrderFailed(Order $order, string $reason = ''): void;
}
