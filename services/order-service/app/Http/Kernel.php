<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * HTTP Kernel — configures global and route middleware.
 */
class Kernel extends HttpKernel
{
    /** @var array<int, class-string|string> */
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
    ];

    /** @var array<string, array<int, class-string|string>> */
    protected $middlewareGroups = [
        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /** @var array<string, class-string|string> */
    protected $middlewareAliases = [
        'tenant'   => \App\Http\Middleware\TenantMiddleware::class,
        'api.auth' => \App\Http\Middleware\ApiAuthMiddleware::class,
    ];
}
