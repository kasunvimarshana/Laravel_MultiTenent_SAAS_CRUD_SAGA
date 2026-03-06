<?php

declare(strict_types=1);

return [
    'host'     => env('RABBITMQ_HOST', 'localhost'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),

    'exchange' => env('RABBITMQ_EXCHANGE', 'saas.events'),

    'queues' => [
        'order_commands' => env('ORDER_COMMANDS_QUEUE', 'order.commands'),
        'order_events'   => env('ORDER_EVENTS_QUEUE',   'order.events'),
    ],
];
