<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseReturn\StorePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends BaseController
{
    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReturn::with(['supplier', 'referenceInvoice', 'details']);

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('return_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('return_date', '<=', $request->get('to_date'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $returns = $query->orderBy('return_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(PurchaseReturnResource::collection($returns));
    }

    /**
     * Store a newly created purchase return.
     */
    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            // Create return
            $purchaseReturn = PurchaseReturn::create([
                'reference_invoice_id' => $validated['reference_invoice_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'return_number' => $validated['return_number'],
                'return_date' => $validated['return_date'],
                'total_amount' => $validated['total_amount'],
                'reason' => $validated['reason'] ?? null,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Create details
            foreach ($validated['items'] as $itemData) {
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'original_item_id' => null, // Can be linked if needed
                    'product_id' => $itemData['product_id'] ?? null,
                    'batch_id' => $itemData['batch_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'product_name' => $itemData['product_name'],
                    'product_code' => $itemData['product_code'] ?? null,
                    'reason' => $itemData['reason'] ?? null,
                ]);
            }

            // Observer will handle inventory and supplier balance updates
            DB::commit();

            return $this->successResponse(
                new PurchaseReturnResource($purchaseReturn->load(['supplier', 'referenceInvoice', 'details'])),
                'Purchase return created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase return creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create purchase return: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified purchase return.
     */
    public function show(PurchaseReturn $purchase_return): JsonResponse
    {
        $purchase_return->load(['supplier', 'referenceInvoice', 'details.batch', 'details.product', 'creator']);
        return $this->successResponse(new PurchaseReturnResource($purchase_return));
    }

    /**
     * Remove the specified purchase return.
     */
    public function destroy(PurchaseReturn $purchase_return): JsonResponse
    {
        // Only allow deleting draft returns
        if ($purchase_return->status !== 'draft') {
            return $this->errorResponse('Only draft returns can be deleted', 422);
        }

        // Observer will handle reversing inventory and supplier balance
        $purchase_return->delete();
        return $this->successResponse(null, 'Purchase return deleted successfully');
    }
}
