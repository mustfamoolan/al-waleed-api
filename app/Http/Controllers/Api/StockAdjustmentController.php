<?php

namespace App\Http\Controllers\Api;

use App\Models\StockAdjustment;
use App\Models\InventoryBatch;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends BaseController
{
    /**
     * Display a listing of stock adjustments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['warehouse', 'product', 'batch', 'creator']);

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by reason
        if ($request->has('reason')) {
            $query->where('reason', $request->get('reason'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('adjustment_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('adjustment_date', '<=', $request->get('to_date'));
        }

        $adjustments = $query->orderBy('adjustment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($adjustments);
    }

    /**
     * Store a newly created stock adjustment.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'product_id' => 'required|exists:products,product_id',
                'batch_id' => 'nullable|exists:inventory_batches,id',
                'adjustment_date' => 'required|date',
                'type' => 'required|in:addition,subtraction',
                'reason' => 'required|in:damaged,expired,inventory_count,gift',
                'quantity' => 'required|numeric|min:0.001',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $manager = $request->user();
            $validated['created_by'] = $manager->manager_id;

            // If batch_id is provided, adjust the batch
            if (isset($validated['batch_id'])) {
                $batch = InventoryBatch::findOrFail($validated['batch_id']);

                if ($validated['type'] === 'subtraction') {
                    if ($batch->quantity_current < $validated['quantity']) {
                        return $this->errorResponse('Insufficient quantity in batch', 422);
                    }
                    $batch->deductQuantity($validated['quantity']);
                } else {
                    $batch->addQuantity($validated['quantity']);
                }
            }

            $adjustment = StockAdjustment::create($validated);

            DB::commit();

            return $this->successResponse(
                $adjustment->load(['warehouse', 'product', 'batch', 'creator']),
                'Stock adjustment created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock adjustment error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create stock adjustment', 500);
        }
    }

    /**
     * Display the specified stock adjustment.
     */
    public function show(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->load(['warehouse', 'product', 'batch', 'creator']);
        return $this->successResponse($stockAdjustment);
    }

    /**
     * Remove the specified stock adjustment.
     */
    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Reverse the adjustment
            if ($stockAdjustment->batch) {
                $batch = $stockAdjustment->batch;
                if ($stockAdjustment->type === 'subtraction') {
                    $batch->addQuantity($stockAdjustment->quantity);
                } else {
                    $batch->deductQuantity($stockAdjustment->quantity);
                }
            }

            $stockAdjustment->delete();

            DB::commit();

            return $this->successResponse(null, 'Stock adjustment deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock adjustment deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete stock adjustment', 500);
        }
    }
}
