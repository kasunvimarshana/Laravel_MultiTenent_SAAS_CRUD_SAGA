<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiAuthMiddleware
 *
 * Validates the Bearer token against the resolved tenant's API key.
 */
class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Bearer token is required.'], 401);
        }

        $token = substr($authHeader, 7);

        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($tenant === null) {
            return new JsonResponse(['error' => 'Tenant not resolved. Ensure TenantMiddleware runs first.'], 500);
        }

        if (!hash_equals($tenant->api_key, $token)) {
            return new JsonResponse(['error' => 'Invalid API token.'], 401);
        }

        return $next($request);
    }
}
