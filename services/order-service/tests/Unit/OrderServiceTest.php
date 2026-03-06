<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\OrderNotFoundException;
use App\Exceptions\OrderStateException;
use App\Interfaces\OrderEventPublisherInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use App\Services\OrderService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Unit tests for OrderService business logic.
 */
class OrderServiceTest extends TestCase
{
    private MockInterface $repository;
    private MockInterface $eventPublisher;
    private OrderService  $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository     = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventPublisher = Mockery::mock(OrderEventPublisherInterface::class);

        $this->orderService = new OrderService(
            $this->repository,
            $this->eventPublisher,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_an_order_and_publishes_created_event(): void
    {
        $tenantId = Uuid::uuid4()->toString();
        $sagaId   = Uuid::uuid4()->toString();
        $data     = [
            'customer_id'      => Uuid::uuid4()->toString(),
            'items'            => [['product_id' => 'P1', 'quantity' => 2, 'unit_price' => 10.0]],
            'currency'         => 'USD',
            'shipping_address' => ['city' => 'New York'],
        ];

        $order = $this->makeOrder($tenantId, 'PENDING');

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $this->eventPublisher
            ->shouldReceive('publishOrderCreated')
            ->once()
            ->with($order);

        $result = $this->orderService->createOrder($tenantId, $data, $sagaId);

        $this->assertSame($order->id, $result->id);
    }

    /** @test */
    public function it_cancels_a_pending_order(): void
    {
        $tenantId  = Uuid::uuid4()->toString();
        $orderId   = Uuid::uuid4()->toString();
        $order     = $this->makeOrder($tenantId, 'PENDING', $orderId);
        $cancelled = $this->makeOrder($tenantId, 'CANCELLED', $orderId);

        $this->repository->shouldReceive('findById')->with($orderId)->andReturn($order);
        $this->repository->shouldReceive('updateStatus')
            ->with($orderId, 'CANCELLED', Mockery::on(static fn ($d) => isset($d['cancelled_at'])))
            ->andReturn($cancelled);

        $this->eventPublisher->shouldReceive('publishOrderCancelled')->once();

        $result = $this->orderService->cancelOrder($orderId, $tenantId, 'Customer request');

        $this->assertSame('CANCELLED', $result->status);
    }

    /** @test */
    public function it_throws_when_cancelling_a_confirmed_order(): void
    {
        $this->expectException(OrderStateException::class);

        $tenantId = Uuid::uuid4()->toString();
        $orderId  = Uuid::uuid4()->toString();
        $order    = $this->makeOrder($tenantId, 'CONFIRMED', $orderId);

        $this->repository->shouldReceive('findById')->with($orderId)->andReturn($order);

        $this->orderService->cancelOrder($orderId, $tenantId);
    }

    /** @test */
    public function it_confirms_a_pending_order(): void
    {
        $tenantId  = Uuid::uuid4()->toString();
        $orderId   = Uuid::uuid4()->toString();
        $order     = $this->makeOrder($tenantId, 'PENDING', $orderId);
        $confirmed = $this->makeOrder($tenantId, 'CONFIRMED', $orderId);

        $this->repository->shouldReceive('findById')->with($orderId)->andReturn($order);
        $this->repository->shouldReceive('updateStatus')
            ->with($orderId, 'CONFIRMED', Mockery::on(static fn ($d) => isset($d['confirmed_at'])))
            ->andReturn($confirmed);

        $this->eventPublisher->shouldReceive('publishOrderConfirmed')->once();

        $result = $this->orderService->confirmOrder($orderId, $tenantId);

        $this->assertSame('CONFIRMED', $result->status);
    }

    /** @test */
    public function it_throws_when_order_not_found(): void
    {
        $this->expectException(OrderNotFoundException::class);

        $tenantId = Uuid::uuid4()->toString();
        $orderId  = Uuid::uuid4()->toString();

        $this->repository->shouldReceive('findById')->with($orderId)->andReturn(null);

        $this->orderService->getOrder($orderId, $tenantId);
    }

    /** @test */
    public function it_throws_when_order_belongs_to_different_tenant(): void
    {
        $this->expectException(OrderNotFoundException::class);

        $tenantId      = Uuid::uuid4()->toString();
        $otherTenantId = Uuid::uuid4()->toString();
        $orderId       = Uuid::uuid4()->toString();
        $order         = $this->makeOrder($otherTenantId, 'PENDING', $orderId);

        $this->repository->shouldReceive('findById')->with($orderId)->andReturn($order);

        $this->orderService->getOrder($orderId, $tenantId);
    }

    private function makeOrder(string $tenantId, string $status, ?string $id = null): Order
    {
        $order            = new Order();
        $order->id        = $id ?? Uuid::uuid4()->toString();
        $order->tenant_id = $tenantId;
        $order->status    = $status;
        $order->items     = [];

        return $order;
    }
}
