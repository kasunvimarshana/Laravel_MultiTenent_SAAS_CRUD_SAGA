<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SagaException;
use App\Interfaces\MessageBrokerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Illuminate\Support\Facades\Log;

/**
 * RabbitMQ implementation of the MessageBrokerInterface.
 *
 * Establishes a persistent connection to RabbitMQ and provides a clean
 * facade over php-amqplib for publishing and consuming SAGA event messages.
 */
final class RabbitMQService implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel          $channel    = null;

    /** Track which queues have already been declared. */
    private array $declaredQueues = [];

    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
        private readonly string $defaultExchange = 'saga_exchange',
    ) {}

    // -----------------------------------------------------------------------
    // MessageBrokerInterface
    // -----------------------------------------------------------------------

    /** {@inheritdoc} */
    public function publish(string $queue, array $message, array $options = []): void
    {
        try {
            $channel  = $this->getChannel();
            $exchange = $options['exchange'] ?? $this->defaultExchange;

            $this->declareQueue($queue, $exchange);

            $body    = json_encode($message, JSON_THROW_ON_ERROR);
            $headers = new AMQPTable(array_merge(
                [
                    'content-type' => 'application/json',
                    'timestamp'    => time(),
                    'message-id'   => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                ],
                $options['headers'] ?? [],
            ));

            $amqpMessage = new AMQPMessage($body, [
                'delivery_mode'  => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'   => 'application/json',
                'application_headers' => $headers,
            ]);

            $channel->basic_publish($amqpMessage, $exchange, $queue);

            Log::debug('[RabbitMQ] Published message', [
                'queue'   => $queue,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            throw new SagaException("Failed to publish to queue '{$queue}': {$e->getMessage()}", 0, $e);
        }
    }

    /** {@inheritdoc} */
    public function subscribe(string $queue, callable $callback, array $options = []): void
    {
        try {
            $channel  = $this->getChannel();
            $exchange = $options['exchange'] ?? $this->defaultExchange;

            $this->declareQueue($queue, $exchange);

            $prefetchCount = (int) ($options['prefetch_count'] ?? 1);
            $channel->basic_qos(0, $prefetchCount, false);

            $channel->basic_consume(
                queue: $queue,
                consumer_tag: '',
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: function (AMQPMessage $msg) use ($callback): void {
                    try {
                        $payload     = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                        $deliveryTag = (string) $msg->getDeliveryTag();

                        Log::debug('[RabbitMQ] Received message', [
                            'delivery_tag' => $deliveryTag,
                            'payload'      => $payload,
                        ]);

                        $callback($payload, $deliveryTag);
                    } catch (\Throwable $e) {
                        Log::error('[RabbitMQ] Error in consumer callback', ['error' => $e->getMessage()]);
                    }
                },
            );

            Log::info('[RabbitMQ] Consuming from queue', ['queue' => $queue]);

            while ($channel->is_consuming()) {
                $channel->wait(null, false, $options['timeout'] ?? 0);
            }
        } catch (\Throwable $e) {
            throw new SagaException("Failed to subscribe to queue '{$queue}': {$e->getMessage()}", 0, $e);
        }
    }

    /** {@inheritdoc} */
    public function acknowledge(string $deliveryTag): void
    {
        try {
            $this->getChannel()->basic_ack((int) $deliveryTag);
        } catch (\Throwable $e) {
            Log::error('[RabbitMQ] Failed to acknowledge message', [
                'delivery_tag' => $deliveryTag,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /** {@inheritdoc} */
    public function reject(string $deliveryTag, bool $requeue = false): void
    {
        try {
            $this->getChannel()->basic_nack((int) $deliveryTag, false, $requeue);
        } catch (\Throwable $e) {
            Log::error('[RabbitMQ] Failed to reject message', [
                'delivery_tag' => $deliveryTag,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /** {@inheritdoc} */
    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable $e) {
            Log::warning('[RabbitMQ] Error closing connection', ['error' => $e->getMessage()]);
        } finally {
            $this->channel    = null;
            $this->connection = null;
            $this->declaredQueues = [];
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Return an open channel, creating the connection if necessary.
     */
    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connect();
        }

        return $this->channel;
    }

    /**
     * Establish a connection and open a channel.
     */
    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                host:     $this->host,
                port:     $this->port,
                user:     $this->user,
                password: $this->password,
                vhost:    $this->vhost,
            );

            $this->channel = $this->connection->channel();

            Log::info('[RabbitMQ] Connected', [
                'host'  => $this->host,
                'port'  => $this->port,
                'vhost' => $this->vhost,
            ]);
        } catch (\Throwable $e) {
            throw new SagaException("Cannot connect to RabbitMQ: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Declare the exchange and queue (idempotent – tracks already-declared queues).
     */
    private function declareQueue(string $queue, string $exchange): void
    {
        $key = "{$exchange}:{$queue}";

        if (isset($this->declaredQueues[$key])) {
            return;
        }

        $this->channel->exchange_declare(
            exchange:    $exchange,
            type:        'direct',
            passive:     false,
            durable:     true,
            auto_delete: false,
        );

        $this->channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );

        $this->channel->queue_bind($queue, $exchange, $queue);

        $this->declaredQueues[$key] = true;
    }
}
