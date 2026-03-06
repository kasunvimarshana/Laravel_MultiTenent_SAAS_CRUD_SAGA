<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\OrderEventPublisherInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\OrderServiceInterface;
use App\Repositories\OrderRepository;
use App\Services\OrderEventPublisher;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;

/**
 * OrderServiceProvider
 *
 * Registers the order domain's repositories, services, and event publishers.
 */
class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        $this->app->singleton(OrderEventPublisherInterface::class, function (): OrderEventPublisher {
            return new OrderEventPublisher(
                broker:   $this->app->make(MessageBrokerInterface::class),
                exchange: (string) config('rabbitmq.exchange', 'saas.events'),
            );
        });

        $this->app->bind(OrderServiceInterface::class, function (): OrderService {
            return new OrderService(
                repository:     $this->app->make(OrderRepositoryInterface::class),
                eventPublisher: $this->app->make(OrderEventPublisherInterface::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
