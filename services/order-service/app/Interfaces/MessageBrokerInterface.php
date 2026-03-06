<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface MessageBrokerInterface
 *
 * Defines the contract for message broker operations (e.g., RabbitMQ).
 */
interface MessageBrokerInterface
{
    public function publish(string $exchange, string $routingKey, array $message): void;
    public function subscribe(string $queue, callable $callback): void;
    public function acknowledge(mixed $message): void;
    public function reject(mixed $message, bool $requeue = false): void;
    public function close(): void;
}
