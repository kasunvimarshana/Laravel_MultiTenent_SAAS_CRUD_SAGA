<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Requests\RestockRequest;
use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST controller for inventory stock level management.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly InventoryEventPublisherInterface $publisher,
    ) {}

    /** GET /api/inventory */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        return response()->json($this->inventoryRepository->findByTenant($tenantId));
    }

    /** GET /api/inventory/{productId} */
    public function show(string $productId): JsonResponse
    {
        $inventory = $this->inventoryRepository->findByProduct($productId);
        if ($inventory === null) {
            return response()->json(['error' => 'Inventory not found.'], 404);
        }
        return response()->json($inventory->load(['product', 'warehouse']));
    }

    /** POST /api/inventory/{productId}/restock */
    public function restock(RestockRequest $request, string $productId): JsonResponse
    {
        $data = $request->validated();
        $inventory = $this->inventoryService->restockProduct(
            $productId,
            $data['warehouse_id'],
            (int) $data['quantity'],
            $data['reason'],
        );

        $this->publisher->publishStockUpdated($inventory, $data['reason']);

        if ($inventory->quantity_available < ($inventory->product->minimum_stock_level ?? 0)) {
            $this->publisher->publishLowStockAlert($inventory);
        }

        return response()->json($inventory);
    }

    /** GET /api/inventory/low-stock */
    public function lowStock(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $products = $this->productRepository->findLowStock($tenantId);
        return response()->json($products);
    }
}
