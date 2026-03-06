<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiAuthMiddleware – authenticates inbound API requests via a Bearer token.
 *
 * The Bearer token is compared (constant-time) against the hashed api_token
 * stored on the resolved Tenant model.  Requests without a matching token
 * are rejected with HTTP 401.
 *
 * This middleware should be applied AFTER TenantMiddleware so that the Tenant
 * is already resolved and bound in the service container.
 */
final class ApiAuthMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Bearer token is required.',
            ], 401);
        }

        // Retrieve the previously resolved tenant (bound by TenantMiddleware).
        /** @var Tenant|null $tenant */
        $tenant = app()->bound(Tenant::class) ? app(Tenant::class) : null;

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Tenant not resolved.',
            ], 401);
        }

        if (!$this->tokenIsValid($token, $tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Invalid API token.',
            ], 401);
        }

        return $next($request);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Extract the raw Bearer token from the Authorization header.
     */
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authHeader, 7));

        return $token !== '' ? $token : null;
    }

    /**
     * Validate the provided token against the tenant's stored hash.
     *
     * Falls back to a plain-text comparison when the stored value is not a
     * bcrypt/argon hash (useful during development with raw tokens).
     */
    private function tokenIsValid(string $token, Tenant $tenant): bool
    {
        $stored = $tenant->api_token;

        if ($stored === null || $stored === '') {
            return false;
        }

        // Constant-time check: prefer hash-based comparison.
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon')) {
            return Hash::check($token, $stored);
        }

        // Plain-text fallback (development only).
        return hash_equals($stored, $token);
    }
}
