<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],

        'redis' => [
            'driver'          => 'redis',
            'connection'      => 'default',
            'lock_connection' => 'default',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'order_service_cache'),
];
