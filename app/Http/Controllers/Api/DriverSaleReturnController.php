<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleReturnResource;
use App\Models\SaleInvoice;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverSaleReturnController extends BaseController
{
    /**
     * Display a listing of driver's returns.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();
        
        $query = SaleReturn::where('returned_by', $driver->picker_id)
            ->where('created_by_type', 'driver')
            ->with(['invoice', 'customer', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $returns = $query->orderBy('return_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleReturnResource::collection($returns));
    }

    /**
     * Store a newly created return.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_invoice_id' => ['required', 'exists:sale_invoices,invoice_id'],
            'return_type' => ['required', 'string', 'in:full,partial'],
            'return_date' => ['required', 'date'],
            'return_reason' => ['nullable', 'string'],
            'items' => ['required_if:return_type,partial', 'array'],
            'items.*.sale_invoice_item_id' => ['required', 'exists:sale_invoice_items,item_id'],
            'items.*.quantity_returned' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_type' => ['required', 'in:piece,carton'],
            'items.*.carton_count' => ['nullable', 'numeric', 'min:0', 'required_if:items.*.unit_type,carton'],
            'items.*.reason' => ['nullable', 'string'],
        ]);

        $driver = $request->user();

        try {
            DB::beginTransaction();

            // Verify invoice is delivered by this driver
            $invoice = SaleInvoice::findOrFail($validated['sale_invoice_id']);
            
            if ($invoice->delivered_by != $driver->picker_id) {
                return $this->errorResponse('You can only return invoices you delivered', 403);
            }

            if (!$invoice->canBeReturned()) {
                return $this->errorResponse('Invoice must be delivered before returning', 422);
            }

            // Verify items belong to invoice
            if ($validated['return_type'] === 'partial') {
                foreach ($validated['items'] as $itemData) {
                    $invoiceItem = $invoice->items()->where('item_id', $itemData['sale_invoice_item_id'])->first();
                    if (!$invoiceItem) {
                        return $this->errorResponse('Item does not belong to this invoice', 422);
                    }
                    // Check quantity returned (convert to pieces for comparison)
                    $invoiceQuantityInPieces = $invoiceItem->getQuantityInPieces();
                    $returnQuantityInPieces = $itemData['quantity_returned'];
                    if (($itemData['unit_type'] ?? 'piece') === 'carton') {
                        $product = $invoiceItem->product;
                        if ($product && $product->pieces_per_carton) {
                            $cartonCount = $itemData['carton_count'] ?? $itemData['quantity_returned'];
                            $returnQuantityInPieces = $cartonCount * $product->pieces_per_carton;
                        }
                    }
                    if ($returnQuantityInPieces > $invoiceQuantityInPieces) {
                        return $this->errorResponse('Quantity returned cannot exceed original quantity', 422);
                    }
                }
            }

            // Create return
            $return = SaleReturn::create([
                'sale_invoice_id' => $validated['sale_invoice_id'],
                'customer_id' => $invoice->customer_id,
                'representative_id' => $invoice->representative_id,
                'returned_by' => $driver->picker_id,
                'created_by' => $driver->picker_id,
                'created_by_type' => 'driver',
                'return_type' => $validated['return_type'],
                'return_date' => $validated['return_date'],
                'return_reason' => $validated['return_reason'] ?? null,
                'status' => 'pending',
            ]);

            // Create return items
            if ($validated['return_type'] === 'full') {
                foreach ($invoice->items as $invoiceItem) {
                    SaleReturnItem::create([
                        'return_id' => $return->return_id,
                        'sale_invoice_item_id' => $invoiceItem->item_id,
                        'product_id' => $invoiceItem->product_id,
                        'quantity_returned' => $invoiceItem->quantity,
                        'unit_type' => $invoiceItem->unit_type ?? 'piece',
                        'carton_count' => $invoiceItem->carton_count,
                        'unit_price' => $invoiceItem->unit_price,
                        'total_return_price' => $invoiceItem->total_price,
                        'reason' => $validated['return_reason'] ?? null,
                    ]);
                }
            } else {
                foreach ($validated['items'] as $itemData) {
                    $invoiceItem = $invoice->items()->where('item_id', $itemData['sale_invoice_item_id'])->first();
                    $returnQuantity = $itemData['quantity_returned'];
                    $returnPrice = ($invoiceItem->unit_price * $returnQuantity) - 
                                   (($invoiceItem->discount_percentage / 100) * $invoiceItem->unit_price * $returnQuantity);
                    
                    $unitType = $itemData['unit_type'] ?? 'piece';
                    $cartonCount = null;
                    if ($unitType === 'carton') {
                        $cartonCount = $itemData['carton_count'] ?? $returnQuantity;
                    }
                    
                    SaleReturnItem::create([
                        'return_id' => $return->return_id,
                        'sale_invoice_item_id' => $invoiceItem->item_id,
                        'product_id' => $invoiceItem->product_id,
                        'quantity_returned' => $returnQuantity,
                        'unit_type' => $unitType,
                        'carton_count' => $cartonCount,
                        'unit_price' => $invoiceItem->unit_price,
                        'total_return_price' => $returnPrice,
                        'reason' => $itemData['reason'] ?? null,
                    ]);
                }
            }

            $return->calculateTotalReturnAmount();

            DB::commit();

            return $this->successResponse(
                new SaleReturnResource($return->load(['invoice', 'customer', 'items'])),
                'Return created successfully. Waiting for manager approval.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create return: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified return.
     */
    public function show(SaleReturn $return): JsonResponse
    {
        $driver = request()->user();
        
        if ($return->returned_by != $driver->picker_id) {
            return $this->errorResponse('Return not found or not yours', 404);
        }

        $return->load(['invoice', 'customer', 'items.invoiceItem', 'approver']);
        return $this->successResponse(new SaleReturnResource($return));
    }
}
