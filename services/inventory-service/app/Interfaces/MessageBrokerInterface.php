<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface for the message broker adapter.
 *
 * Abstracts the underlying transport (RabbitMQ, SQS, etc.) so that
 * application code does not depend on a specific messaging library.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to the specified exchange with a routing key.
     *
     * @param  string               $exchange    Target exchange name
     * @param  string               $routingKey  Routing / binding key
     * @param  array<string, mixed> $payload     Message body (will be JSON-encoded)
     * @param  array<string, mixed> $properties  Optional AMQP message properties
     * @return void
     */
    public function publish(
        string $exchange,
        string $routingKey,
        array $payload,
        array $properties = [],
    ): void;

    /**
     * Subscribe to a queue and invoke the callback for each incoming message.
     *
     * The callback receives the decoded payload array and the raw delivery tag.
     * It should return true to acknowledge or false to reject the message.
     *
     * @param  string    $queue     Queue name to consume from
     * @param  callable  $callback  fn(array $payload, mixed $tag): bool
     * @return void
     */
    public function subscribe(string $queue, callable $callback): void;

    /**
     * Acknowledge a successfully processed message.
     *
     * @param  mixed  $deliveryTag  The AMQP delivery tag returned by the broker
     * @return void
     */
    public function acknowledge(mixed $deliveryTag): void;

    /**
     * Reject a message, optionally re-queuing it for retry.
     *
     * @param  mixed  $deliveryTag  The AMQP delivery tag returned by the broker
     * @param  bool   $requeue      Whether to re-queue the message
     * @return void
     */
    public function reject(mixed $deliveryTag, bool $requeue = false): void;

    /**
     * Close the broker connection gracefully.
     *
     * @return void
     */
    public function close(): void;
}
