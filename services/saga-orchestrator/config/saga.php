<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SAGA Workflow Settings
    |--------------------------------------------------------------------------
    */

    'timeouts' => [
        'create_order'       => env('SAGA_TIMEOUT_CREATE_ORDER',       30),
        'reserve_inventory'  => env('SAGA_TIMEOUT_RESERVE_INVENTORY',  30),
        'process_payment'    => env('SAGA_TIMEOUT_PROCESS_PAYMENT',    60),
        'send_notification'  => env('SAGA_TIMEOUT_SEND_NOTIFICATION',  15),
        'default'            => env('SAGA_TIMEOUT_DEFAULT',            30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts'   => (int) env('SAGA_RETRY_MAX_ATTEMPTS', 3),
        'delay_seconds'  => (int) env('SAGA_RETRY_DELAY_SECONDS', 5),
        'backoff_factor' => (float) env('SAGA_RETRY_BACKOFF_FACTOR', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Queue Names
    |--------------------------------------------------------------------------
    |
    | Each microservice has a dedicated command queue.  Replies from services
    | flow back to the saga_events queue consumed by the orchestrator.
    |
    */

    'queues' => [
        // Outbound command queues (orchestrator → services)
        'order_commands'        => env('QUEUE_ORDER_COMMANDS',        'order_commands'),
        'inventory_commands'    => env('QUEUE_INVENTORY_COMMANDS',    'inventory_commands'),
        'payment_commands'      => env('QUEUE_PAYMENT_COMMANDS',      'payment_commands'),
        'notification_commands' => env('QUEUE_NOTIFICATION_COMMANDS', 'notification_commands'),

        // Inbound event queue (services → orchestrator)
        'saga_events'           => env('QUEUE_SAGA_EVENTS',           'saga_events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Downstream Service Base URLs
    |
    | Used when the orchestrator needs to make synchronous HTTP calls (e.g.
    | health-checks) in addition to async message-based communication.
    |--------------------------------------------------------------------------
    */

    'services' => [
        'order'        => env('SERVICE_ORDER_URL',        'http://order-service:8001'),
        'inventory'    => env('SERVICE_INVENTORY_URL',    'http://inventory-service:8002'),
        'payment'      => env('SERVICE_PAYMENT_URL',      'http://payment-service:8003'),
        'notification' => env('SERVICE_NOTIFICATION_URL', 'http://notification-service:8004'),
    ],

];
