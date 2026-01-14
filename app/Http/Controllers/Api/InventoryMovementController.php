<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends BaseController
{
    /**
     * Display a listing of inventory movements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product', 'creator']);

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Filter by movement type
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->get('movement_type'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->get('to_date'));
        }

        $movements = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(InventoryMovementResource::collection($movements));
    }
}
