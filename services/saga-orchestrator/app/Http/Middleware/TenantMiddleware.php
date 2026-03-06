<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Interfaces\TenantRepositoryInterface;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware – resolves the current tenant from the request.
 *
 * Resolution order:
 *   1. X-Tenant-ID request header (numeric primary key).
 *   2. Subdomain of the host (e.g. "acme" from "acme.example.com").
 *
 * Resolved tenants are bound into the service container so that downstream
 * code can retrieve them via `app(Tenant::class)`.
 */
final class TenantMiddleware
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant could not be resolved. Provide a valid X-Tenant-ID header or subdomain.',
            ], 400);
        }

        if (!$tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is inactive.',
            ], 403);
        }

        // Bind the resolved tenant so it is accessible throughout the request lifecycle.
        App::instance(Tenant::class, $tenant);

        // Add the tenant ID to all subsequent log records.
        \Illuminate\Support\Facades\Log::withContext([
            'tenant_id'     => $tenant->id,
            'tenant_domain' => $tenant->domain,
        ]);

        return $next($request);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Attempt to resolve the tenant, trying the header first then subdomain.
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // 1. Explicit header.
        $tenantId = $request->header('X-Tenant-ID');

        if ($tenantId !== null) {
            return $this->tenants->findById((int) $tenantId);
        }

        // 2. Subdomain.
        $subdomain = $this->extractSubdomain($request->getHost());

        if ($subdomain !== null) {
            return $this->tenants->findActiveTenantByDomain($subdomain);
        }

        return null;
    }

    /**
     * Extract the first subdomain segment from a hostname.
     *
     * Returns null when the host has fewer than three parts (no subdomain).
     */
    private function extractSubdomain(string $host): ?string
    {
        // Strip port if present.
        $host  = strtolower(explode(':', $host)[0]);
        $parts = explode('.', $host);

        if (count($parts) < 3) {
            return null;
        }

        return $parts[0];
    }
}
