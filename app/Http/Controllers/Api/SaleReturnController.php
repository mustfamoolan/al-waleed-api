<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleReturnResource;
use App\Models\SaleReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleReturnController extends BaseController
{
    /**
     * Display a listing of sale returns.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SaleReturn::with(['invoice', 'customer', 'representative', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        $returns = $query->orderBy('return_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleReturnResource::collection($returns));
    }

    /**
     * Display the specified return.
     */
    public function show(SaleReturn $sale_return): JsonResponse
    {
        $sale_return->load(['invoice', 'customer', 'representative', 'items', 'approver']);
        return $this->successResponse(new SaleReturnResource($sale_return));
    }

    /**
     * Approve return.
     */
    public function approve(Request $request, SaleReturn $sale_return): JsonResponse
    {
        if ($sale_return->status !== 'pending') {
            return $this->errorResponse('Only pending returns can be approved', 422);
        }

        try {
            $manager = $request->user();
            
            if (!$sale_return->approve($manager->manager_id)) {
                return $this->errorResponse('Failed to approve return', 500);
            }

            return $this->successResponse(
                new SaleReturnResource($sale_return->load(['invoice', 'customer', 'items', 'approver'])),
                'Return approved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Return approval error: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve return: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reject return.
     */
    public function reject(Request $request, SaleReturn $sale_return): JsonResponse
    {
        if ($sale_return->status !== 'pending') {
            return $this->errorResponse('Only pending returns can be rejected', 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $manager = $request->user();
            
            if (!$sale_return->reject($manager->manager_id, $validated['reason'])) {
                return $this->errorResponse('Failed to reject return', 500);
            }

            return $this->successResponse(
                new SaleReturnResource($sale_return->load(['invoice', 'customer', 'approver'])),
                'Return rejected successfully'
            );
        } catch (\Exception $e) {
            Log::error('Return rejection error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reject return: ' . $e->getMessage(), 500);
        }
    }
}
