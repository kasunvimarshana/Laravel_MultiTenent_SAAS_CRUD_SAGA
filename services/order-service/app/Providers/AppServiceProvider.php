<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\TenantResolverInterface;
use App\Services\RabbitMQService;
use App\Services\TenantResolver;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Binds core infrastructure interfaces to their concrete implementations.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageBrokerInterface::class, function (): RabbitMQService {
            return new RabbitMQService(
                host:     (string) config('rabbitmq.host', 'localhost'),
                port:     (int)    config('rabbitmq.port', 5672),
                user:     (string) config('rabbitmq.user', 'guest'),
                password: (string) config('rabbitmq.password', 'guest'),
                vhost:    (string) config('rabbitmq.vhost', '/'),
            );
        });

        $this->app->singleton(TenantResolverInterface::class, TenantResolver::class);
    }

    public function boot(): void
    {
        //
    }
}
