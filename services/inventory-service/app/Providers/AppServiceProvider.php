<?php
declare(strict_types=1);
namespace App\Providers;

use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\ReservationRepositoryInterface;
use App\Repositories\InventoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ReservationRepository;
use App\Services\InventoryEventPublisher;
use App\Services\InventoryService;
use App\Services\RabbitMQService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);

        // Message broker (singleton – one connection per process)
        $this->app->singleton(MessageBrokerInterface::class, function () {
            return new RabbitMQService(
                host:     config('rabbitmq.host', 'rabbitmq'),
                port:     (int) config('rabbitmq.port', 5672),
                user:     config('rabbitmq.user', 'guest'),
                password: config('rabbitmq.password', 'guest'),
                vhost:    config('rabbitmq.vhost', '/'),
            );
        });

        // Domain services
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->bind(InventoryEventPublisherInterface::class, InventoryEventPublisher::class);
    }

    public function boot(): void {}
}
