<?php

declare(strict_types=1);

return [
    'statuses' => [
        'pending'   => 'PENDING',
        'confirmed' => 'CONFIRMED',
        'cancelled' => 'CANCELLED',
        'failed'    => 'FAILED',
    ],

    'pagination' => [
        'per_page'     => (int) env('ORDER_PAGINATION_PER_PAGE', 15),
        'max_per_page' => 100,
    ],

    'queues' => [
        'order_commands' => env('ORDER_COMMANDS_QUEUE', 'order.commands'),
        'order_events'   => env('ORDER_EVENTS_QUEUE',   'order.events'),
    ],
];
