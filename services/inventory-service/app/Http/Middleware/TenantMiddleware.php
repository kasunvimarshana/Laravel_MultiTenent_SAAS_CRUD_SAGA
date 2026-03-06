<?php
declare(strict_types=1);
namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the X-Tenant-ID header and binds
 * it into the request for downstream use.
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (empty($tenantId)) {
            return response()->json(['error' => 'Missing X-Tenant-ID header.'], 400);
        }

        $tenant = Tenant::find($tenantId);

        if ($tenant === null || !$tenant->is_active) {
            return response()->json(['error' => 'Tenant not found or inactive.'], 403);
        }

        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
