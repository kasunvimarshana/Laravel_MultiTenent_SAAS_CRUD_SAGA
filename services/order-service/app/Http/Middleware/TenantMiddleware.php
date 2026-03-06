<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\TenantNotFoundException;
use App\Interfaces\TenantResolverInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 *
 * Resolves the current tenant from the X-Tenant-ID request header.
 */
class TenantMiddleware
{
    public function __construct(
        private readonly TenantResolverInterface $tenantResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (empty($tenantId)) {
            return new JsonResponse(['error' => 'X-Tenant-ID header is required.'], 400);
        }

        try {
            $tenant = $this->tenantResolver->resolveTenant($tenantId);
            $request->attributes->set('tenant', $tenant);
            $request->attributes->set('tenant_id', $tenant->id);
        } catch (TenantNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        }

        return $next($request);
    }
}
