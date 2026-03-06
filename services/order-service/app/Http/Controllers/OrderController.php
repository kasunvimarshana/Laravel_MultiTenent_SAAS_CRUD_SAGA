<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OrderController
 *
 * Handles REST API operations for orders.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {
    }

    /**
     * POST /api/orders
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $order = $this->orderService->createOrder(
            $tenantId,
            $request->validated(),
        );

        return response()->json($order->load('orderItems'), 201);
    }

    /**
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $perPage  = (int) $request->input('per_page', 15);
        $filters  = $request->only(['status', 'customer_id', 'from_date', 'to_date']);

        $orders = $this->orderService->listOrders($tenantId, $filters, $perPage);

        return response()->json($orders);
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $order    = $this->orderService->getOrder($id, $tenantId);

        return response()->json($order->load('orderItems'));
    }

    /**
     * PUT /api/orders/{id}
     */
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $order    = $this->orderService->updateOrder($id, $tenantId, $request->validated());

        return response()->json($order->load('orderItems'));
    }

    /**
     * DELETE /api/orders/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $reason   = $request->input('reason', '');
        $order    = $this->orderService->cancelOrder($id, $tenantId, $reason);

        return response()->json($order);
    }

    /**
     * POST /api/orders/{id}/confirm
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $order    = $this->orderService->confirmOrder($id, $tenantId);

        return response()->json($order);
    }
}
