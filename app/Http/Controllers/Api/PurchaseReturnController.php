<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseReturn\StorePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturnInvoice;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReturnInvoice::with(['supplier', 'originalInvoice', 'items']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        $returns = $query->orderBy('return_date', 'desc')->get();
        return $this->successResponse(PurchaseReturnResource::collection($returns));
    }

    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $returnInvoice = PurchaseReturnInvoice::create([
                'original_invoice_id' => $validated['original_invoice_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'return_invoice_number' => $validated['return_invoice_number'],
                'return_date' => $validated['return_date'],
                'total_amount' => $validated['total_amount'],
                'reason' => $validated['reason'] ?? null,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchaseReturnItem::create([
                    'return_invoice_id' => $returnInvoice->return_invoice_id,
                    'original_item_id' => $itemData['original_item_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'product_code' => $itemData['product_code'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'reason' => $itemData['reason'] ?? null,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseReturnResource($returnInvoice->load(['supplier', 'originalInvoice', 'items'])),
                'Purchase return created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase return creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create purchase return', 500);
        }
    }

    public function show(PurchaseReturnInvoice $purchase_return): JsonResponse
    {
        $purchase_return->load(['supplier', 'originalInvoice', 'items', 'creator']);
        return $this->successResponse(new PurchaseReturnResource($purchase_return));
    }

    public function destroy(PurchaseReturnInvoice $purchase_return): JsonResponse
    {
        if ($purchase_return->status !== 'draft') {
            return $this->errorResponse('Only draft returns can be deleted', 422);
        }

        $purchase_return->delete();
        return $this->successResponse(null, 'Purchase return deleted successfully');
    }

    public function post(PurchaseReturnInvoice $purchase_return): JsonResponse
    {
        if ($purchase_return->status !== 'draft') {
            return $this->errorResponse('Only draft returns can be posted', 422);
        }

        $purchase_return->status = 'completed';
        $purchase_return->save();

        // TODO: Create journal entry here when accounting system is ready

        return $this->successResponse(
            new PurchaseReturnResource($purchase_return->load(['supplier', 'originalInvoice', 'items'])),
            'Purchase return posted successfully'
        );
    }
}
