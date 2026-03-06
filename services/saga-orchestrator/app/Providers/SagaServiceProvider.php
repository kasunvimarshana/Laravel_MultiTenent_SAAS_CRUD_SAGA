<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaOrchestratorInterface;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Interfaces\TenantRepositoryInterface;
use App\Repositories\SagaTransactionRepository;
use App\Repositories\TenantRepository;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use App\Services\RabbitMQService;
use App\Services\SagaOrchestrator;
use Illuminate\Support\ServiceProvider;

/**
 * SagaServiceProvider – binds saga-specific interfaces to their concrete
 * implementations and registers the SAGA workflows.
 */
final class SagaServiceProvider extends ServiceProvider
{
    /**
     * Register application bindings.
     */
    public function register(): void
    {
        // -----------------------------------------------------------------------
        // Message broker
        // -----------------------------------------------------------------------
        $this->app->singleton(MessageBrokerInterface::class, function (): RabbitMQService {
            return new RabbitMQService(
                host:            config('rabbitmq.host', 'rabbitmq'),
                port:            (int) config('rabbitmq.port', 5672),
                user:            config('rabbitmq.user', 'guest'),
                password:        config('rabbitmq.password', 'guest'),
                vhost:           config('rabbitmq.vhost', '/'),
                defaultExchange: config('rabbitmq.exchange', 'saga_exchange'),
            );
        });

        // -----------------------------------------------------------------------
        // Repositories
        // -----------------------------------------------------------------------
        $this->app->bind(
            SagaTransactionRepositoryInterface::class,
            SagaTransactionRepository::class,
        );

        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class,
        );

        // -----------------------------------------------------------------------
        // Orchestrator
        // -----------------------------------------------------------------------
        $this->app->singleton(SagaOrchestratorInterface::class, function ($app): SagaOrchestrator {
            /** @var SagaOrchestrator $orchestrator */
            $orchestrator = new SagaOrchestrator(
                repository: $app->make(SagaTransactionRepositoryInterface::class),
                broker:     $app->make(MessageBrokerInterface::class),
            );

            // Register the Order SAGA workflow with its ordered steps.
            $orchestrator->registerSteps('order_saga', [
                $app->make(CreateOrderStep::class),
                $app->make(ReserveInventoryStep::class),
                $app->make(ProcessPaymentStep::class),
                $app->make(SendNotificationStep::class),
            ]);

            return $orchestrator;
        });
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        //
    }
}
