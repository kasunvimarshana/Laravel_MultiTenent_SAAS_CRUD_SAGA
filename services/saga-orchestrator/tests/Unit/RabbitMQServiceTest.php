<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SagaException;
use App\Services\RabbitMQService;
use Mockery;
use PHPUnit\Framework\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Unit tests for RabbitMQService.
 *
 * Tests that do NOT require a live RabbitMQ broker are placed here.
 * Tests that require a running broker should be in an integration test suite.
 */
class RabbitMQServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Constructor / factory
    // -----------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_valid_config(): void
    {
        $service = new RabbitMQService(
            host:            'localhost',
            port:            5672,
            user:            'guest',
            password:        'guest',
            vhost:           '/',
            defaultExchange: 'test_exchange',
        );

        $this->assertInstanceOf(RabbitMQService::class, $service);
    }

    // -----------------------------------------------------------------------
    // publish – connection failure
    // -----------------------------------------------------------------------

    public function test_publish_throws_saga_exception_on_connection_failure(): void
    {
        $service = new RabbitMQService(
            host:     '127.0.0.1',
            port:     1,      // Invalid port – connection should fail.
            user:     'guest',
            password: 'guest',
            vhost:    '/',
        );

        $this->expectException(SagaException::class);

        $service->publish('test_queue', ['key' => 'value']);
    }

    // -----------------------------------------------------------------------
    // close – idempotent when not connected
    // -----------------------------------------------------------------------

    public function test_close_is_idempotent_when_not_connected(): void
    {
        $service = new RabbitMQService(
            host:     'localhost',
            port:     5672,
            user:     'guest',
            password: 'guest',
            vhost:    '/',
        );

        // Calling close() on a service that was never connected must not throw.
        $service->close();
        $service->close(); // Second call also safe.

        $this->assertTrue(true); // Assertion: no exception thrown.
    }

    // -----------------------------------------------------------------------
    // acknowledge / reject – delegate to channel
    // -----------------------------------------------------------------------

    public function test_acknowledge_calls_basic_ack_on_channel(): void
    {
        [$service, $channel] = $this->buildServiceWithMockedChannel();

        $channel->shouldReceive('basic_ack')
                ->once()
                ->with(42, Mockery::any());

        // Use reflection to inject the mocked channel.
        $this->injectChannel($service, $channel);

        $service->acknowledge('42');
    }

    public function test_reject_without_requeue_calls_basic_nack(): void
    {
        [$service, $channel] = $this->buildServiceWithMockedChannel();

        $channel->shouldReceive('basic_nack')
                ->once()
                ->with(7, false, false);

        $this->injectChannel($service, $channel);

        $service->reject('7', false);
    }

    public function test_reject_with_requeue_calls_basic_nack_with_requeue_true(): void
    {
        [$service, $channel] = $this->buildServiceWithMockedChannel();

        $channel->shouldReceive('basic_nack')
                ->once()
                ->with(9, false, true);

        $this->injectChannel($service, $channel);

        $service->reject('9', true);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a RabbitMQService with a Mockery channel mock.
     *
     * @return array{RabbitMQService, AMQPChannel&\Mockery\MockInterface}
     */
    private function buildServiceWithMockedChannel(): array
    {
        $service = new RabbitMQService(
            host:     'localhost',
            port:     5672,
            user:     'guest',
            password: 'guest',
            vhost:    '/',
        );

        $channel = Mockery::mock(AMQPChannel::class);
        $channel->shouldReceive('is_open')->andReturn(true);

        return [$service, $channel];
    }

    /**
     * Inject a mocked AMQPChannel into the service via reflection.
     */
    private function injectChannel(RabbitMQService $service, AMQPChannel $channel): void
    {
        $reflection = new \ReflectionClass($service);
        $property   = $reflection->getProperty('channel');
        $property->setAccessible(true);
        $property->setValue($service, $channel);
    }
}
