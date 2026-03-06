<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQService
 *
 * Concrete implementation of MessageBrokerInterface using php-amqplib.
 */
class RabbitMQService implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
    ) {
    }

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost,
            );
        }

        return $this->connection;
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->channel = $this->getConnection()->channel();
        }

        return $this->channel;
    }

    public function publish(string $exchange, string $routingKey, array $message): void
    {
        try {
            $channel = $this->getChannel();
            $channel->exchange_declare($exchange, 'topic', false, true, false);

            $body = json_encode($message, JSON_THROW_ON_ERROR);

            $amqpMessage = new AMQPMessage(
                $body,
                [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($amqpMessage, $exchange, $routingKey);

            Log::info('RabbitMQ: message published', [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('RabbitMQ: failed to publish message', [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function subscribe(string $queue, callable $callback): void
    {
        try {
            $channel = $this->getChannel();
            $channel->queue_declare($queue, false, true, false, false);
            $channel->basic_qos(null, 1, null);
            $channel->basic_consume($queue, '', false, false, false, false, $callback);

            Log::info('RabbitMQ: subscribed to queue', ['queue' => $queue]);

            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (\Throwable $e) {
            Log::error('RabbitMQ: subscription error', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function acknowledge(mixed $message): void
    {
        $message->ack();
    }

    public function reject(mixed $message, bool $requeue = false): void
    {
        $message->nack($requeue);
    }

    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable $e) {
            Log::warning('RabbitMQ: error closing connection', ['error' => $e->getMessage()]);
        }
    }
}
