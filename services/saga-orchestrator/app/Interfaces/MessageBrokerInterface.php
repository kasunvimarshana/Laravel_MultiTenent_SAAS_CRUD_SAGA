<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contract for an asynchronous message broker adapter.
 *
 * Provides a vendor-agnostic abstraction over message brokers such as
 * RabbitMQ, Kafka, or AWS SQS so that higher-level services remain
 * decoupled from the underlying transport.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to the given exchange / queue.
     *
     * @param  string               $queue      Destination queue or routing key.
     * @param  array<string, mixed> $message    Message body; will be JSON-encoded.
     * @param  array<string, mixed> $options    Optional broker-specific options
     *                                          (e.g. ['exchange' => 'saga', 'priority' => 5]).
     * @return void
     *
     * @throws \App\Exceptions\SagaException On broker connection or publish failure.
     */
    public function publish(string $queue, array $message, array $options = []): void;

    /**
     * Register a callback to consume messages from the given queue.
     *
     * The callback receives the decoded message payload and a delivery tag
     * that can be used with acknowledge() or reject().
     *
     * @param  string   $queue     Queue to consume from.
     * @param  callable $callback  function(array $message, string $deliveryTag): void
     * @param  array<string, mixed> $options  Optional consumer options.
     * @return void
     *
     * @throws \App\Exceptions\SagaException On broker connection failure.
     */
    public function subscribe(string $queue, callable $callback, array $options = []): void;

    /**
     * Acknowledge a successfully processed message.
     *
     * @param  string $deliveryTag  The delivery tag returned by the broker.
     * @return void
     */
    public function acknowledge(string $deliveryTag): void;

    /**
     * Reject (nack) a message, optionally re-queuing it for later processing.
     *
     * @param  string $deliveryTag  The delivery tag returned by the broker.
     * @param  bool   $requeue      Whether to put the message back on the queue.
     * @return void
     */
    public function reject(string $deliveryTag, bool $requeue = false): void;

    /**
     * Gracefully close the broker connection and release resources.
     *
     * @return void
     */
    public function close(): void;
}
