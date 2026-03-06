<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Interfaces\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST controller for product catalogue management.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        return response()->json($this->products->findByTenant($tenantId));
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->products->findById($id);
        if ($product === null) {
            return response()->json(['error' => 'Product not found.'], 404);
        }
        return response()->json($product->load('inventory'));
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product = $this->products->create(array_merge($request->validated(), ['tenant_id' => $tenantId]));
        return response()->json($product, 201);
    }

    public function update(CreateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->products->update($id, $request->validated());
        return response()->json($product);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->products->delete($id);
        return response()->json(['message' => 'Product deleted.']);
    }
}
