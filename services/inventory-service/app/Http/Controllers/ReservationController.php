<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\CreateReservationRequest;
use App\Interfaces\InventoryEventPublisherInterface;
use App\Interfaces\InventoryServiceInterface;
use App\Interfaces\ReservationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST controller for inventory reservation lifecycle.
 */
class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservations,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly InventoryEventPublisherInterface $publisher,
    ) {}

    /** GET /api/reservations */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        return response()->json($this->reservations->findByTenant($tenantId));
    }

    /** GET /api/reservations/{id} */
    public function show(string $id): JsonResponse
    {
        $reservation = $this->reservations->findById($id);
        if ($reservation === null) {
            return response()->json(['error' => 'Reservation not found.'], 404);
        }
        return response()->json($reservation);
    }

    /** POST /api/reservations */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $data = $request->validated();

        try {
            $reservation = $this->inventoryService->reserveItems(
                $data['saga_id'],
                $data['order_id'],
                $tenantId,
                $data['items'],
            );
            $this->publisher->publishInventoryReserved($reservation);
            return response()->json($reservation, 201);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** DELETE /api/reservations/{id} */
    public function destroy(string $id): JsonResponse
    {
        $reservation = $this->inventoryService->releaseReservation($id);
        $this->publisher->publishInventoryReleased($reservation);
        return response()->json(['message' => 'Reservation released.']);
    }
}
