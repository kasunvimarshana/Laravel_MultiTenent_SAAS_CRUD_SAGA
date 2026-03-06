<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection
    |--------------------------------------------------------------------------
    */

    'host'     => env('RABBITMQ_HOST',     'rabbitmq'),
    'port'     => (int) env('RABBITMQ_PORT',     5672),
    'user'     => env('RABBITMQ_USER',     'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST',    '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Configuration
    |--------------------------------------------------------------------------
    |
    | The orchestrator uses a single direct exchange.  Each queue is bound to
    | this exchange with a routing key equal to the queue name.
    |
    */

    'exchange' => env('RABBITMQ_EXCHANGE', 'saga_exchange'),

    /*
    |--------------------------------------------------------------------------
    | Consumer Settings
    |--------------------------------------------------------------------------
    */

    'consumer' => [
        'prefetch_count' => (int) env('RABBITMQ_PREFETCH_COUNT', 1),
        'timeout'        => (int) env('RABBITMQ_CONSUMER_TIMEOUT', 0),  // 0 = block indefinitely
    ],

    /*
    |--------------------------------------------------------------------------
    | Publisher Settings
    |--------------------------------------------------------------------------
    */

    'publisher' => [
        'persistent' => true,
    ],

];
