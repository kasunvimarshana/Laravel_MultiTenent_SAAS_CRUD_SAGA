<?php
declare(strict_types=1);
namespace App\Services;

use App\Interfaces\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ adapter implementing MessageBrokerInterface via php-amqplib.
 */
class RabbitMQService implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
    ) {}

    /** Lazily create and return the AMQP channel. */
    private function channel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost,
            );
            $this->channel = $this->connection->channel();
        }
        return $this->channel;
    }

    /** {@inheritDoc} */
    public function publish(string $exchange, string $routingKey, array $payload, array $properties = []): void
    {
        $channel = $this->channel();
        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $msgProperties = array_merge(['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT], $properties);

        $message = new AMQPMessage($body, $msgProperties);
        $channel->basic_publish($message, $exchange, $routingKey);

        Log::debug('Published AMQP message', ['exchange' => $exchange, 'routing_key' => $routingKey]);
    }

    /** {@inheritDoc} */
    public function subscribe(string $queue, callable $callback): void
    {
        $channel = $this->channel();
        $channel->queue_declare($queue, false, true, false, false);
        $channel->basic_qos(0, 1, false);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($callback): void {
                $payload = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
                $ack = $callback($payload, $message);
                if ($ack) {
                    $message->ack();
                } else {
                    $message->nack(true);
                }
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /** {@inheritDoc} */
    public function acknowledge(mixed $deliveryTag): void
    {
        $this->channel()->basic_ack($deliveryTag);
    }

    /** {@inheritDoc} */
    public function reject(mixed $deliveryTag, bool $requeue = false): void
    {
        $this->channel()->basic_nack($deliveryTag, false, $requeue);
    }

    /** {@inheritDoc} */
    public function close(): void
    {
        $this->channel?->close();
        $this->connection?->close();
    }
}
