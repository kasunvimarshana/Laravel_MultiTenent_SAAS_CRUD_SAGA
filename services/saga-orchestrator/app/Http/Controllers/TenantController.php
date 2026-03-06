<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\TenantRepositoryInterface;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * TenantController – CRUD management for tenants.
 *
 * Routes (all under /api/tenants):
 *   GET    /          → index
 *   POST   /          → store
 *   GET    /{id}      → show
 *   PUT    /{id}      → update
 *   DELETE /{id}      → destroy
 */
class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /**
     * List all tenants.
     *
     * GET /api/tenants
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->tenants->all(),
        ]);
    }

    /**
     * Create a new tenant.
     *
     * POST /api/tenants
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255'],
            'domain'        => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'database_name' => ['required', 'string', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
            'settings'      => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tenant = $this->tenants->create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully.',
            'data'    => $tenant,
        ], 201);
    }

    /**
     * Get a specific tenant by ID.
     *
     * GET /api/tenants/{id}
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenants->findById($id);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => "Tenant not found: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $tenant,
        ]);
    }

    /**
     * Update a tenant.
     *
     * PUT /api/tenants/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = $this->tenants->findById($id);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => "Tenant not found: {$id}",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['sometimes', 'string', 'max:255'],
            'domain'        => ['sometimes', 'string', 'max:255', "unique:tenants,domain,{$id}"],
            'database_name' => ['sometimes', 'string', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
            'settings'      => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updated = $this->tenants->update($id, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * Soft-delete a tenant.
     *
     * DELETE /api/tenants/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->tenants->findById($id);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => "Tenant not found: {$id}",
            ], 404);
        }

        $this->tenants->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully.',
        ]);
    }
}
