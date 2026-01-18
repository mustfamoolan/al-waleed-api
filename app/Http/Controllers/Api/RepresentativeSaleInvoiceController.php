<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SaleInvoiceResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepresentativeSaleInvoiceController extends BaseController
{
    /**
     * Display a listing of representative's sale invoices/requests.
     */
    public function index(Request $request): JsonResponse
    {
        $representative = $request->user();
        
        $query = SaleInvoice::where('representative_id', $representative->rep_id)
            ->with(['customer', 'items', 'representative']);

        if ($request->has('request_status')) {
            $query->where('request_status', $request->get('request_status'));
        }

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Store a newly created sale invoice request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,customer_id'],
            'invoice_number' => ['required', 'string', 'unique:sale_invoices,invoice_number'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,credit'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_type' => ['required', 'in:piece,carton'],
            'items.*.carton_count' => ['nullable', 'numeric', 'min:0', 'required_if:items.*.unit_type,carton'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $representative = $request->user();

        try {
            DB::beginTransaction();

            // Verify customer belongs to this representative
            if (!$representative->customers()->where('customer_id', $validated['customer_id'])->exists()) {
                return $this->errorResponse('Customer not assigned to you', 422);
            }

            $customer = Customer::find($validated['customer_id']);

                // Check stock availability (convert to pieces)
                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    if (!$product) {
                        return $this->errorResponse("Product not found", 422);
                    }
                    
                    // Calculate quantity in pieces
                    $quantityInPieces = $itemData['quantity'];
                    if (($itemData['unit_type'] ?? 'piece') === 'carton') {
                        if ($product->pieces_per_carton) {
                            $cartonCount = $itemData['carton_count'] ?? $itemData['quantity'];
                            $quantityInPieces = $cartonCount * $product->pieces_per_carton;
                        } else {
                            return $this->errorResponse("Product: {$product->product_name} does not have pieces_per_carton defined", 422);
                        }
                    }
                    
                    if ($product->current_stock < $quantityInPieces) {
                        return $this->errorResponse("Insufficient stock for product: {$product->product_name}", 422);
                    }
                }

            // Create invoice request
            $invoice = SaleInvoice::create([
                'representative_id' => $representative->rep_id,
                'request_type' => 'representative',
                'request_status' => 'pending_approval',
                'buyer_type' => 'customer',
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'paid_amount' => 0,
                'remaining_amount' => $validated['total_amount'],
                'payment_method' => $validated['payment_method'] ?? 'credit',
                'status' => 'pending',
                'delivery_status' => 'not_prepared',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create items
            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                
                $unitType = $itemData['unit_type'] ?? 'piece';
                $cartonCount = null;
                if ($unitType === 'carton') {
                    $cartonCount = $itemData['carton_count'] ?? $itemData['quantity'];
                }
                
                $itemSubtotal = $itemData['quantity'] * $itemData['unit_price'];
                
                $discountAmount = 0;
                if (isset($itemData['discount_percentage']) && $itemData['discount_percentage'] > 0) {
                    $discountAmount = $itemSubtotal * ($itemData['discount_percentage'] / 100);
                    $itemSubtotal -= $discountAmount;
                }

                $taxAmount = 0;
                if (isset($itemData['tax_percentage']) && $itemData['tax_percentage'] > 0) {
                    $taxAmount = $itemSubtotal * ($itemData['tax_percentage'] / 100);
                    $itemSubtotal += $taxAmount;
                }

                $totalPrice = $itemSubtotal;
                
                // Calculate profit based on quantity in pieces
                $quantityInPieces = $itemData['quantity'];
                if ($unitType === 'carton' && $product->pieces_per_carton) {
                    $quantityInPieces = $cartonCount * $product->pieces_per_carton;
                }
                $profitAmount = ($itemData['unit_price'] - $product->purchase_price) * $quantityInPieces;
                $profitPercentage = $product->purchase_price > 0 
                    ? (($itemData['unit_price'] - $product->purchase_price) / $product->purchase_price) * 100 
                    : 0;

                SaleInvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'product_code' => $product->sku,
                    'quantity' => $itemData['quantity'],
                    'unit_type' => $unitType,
                    'carton_count' => $cartonCount,
                    'unit_price' => $itemData['unit_price'],
                    'purchase_price_at_sale' => $product->purchase_price,
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                    'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                    'total_price' => $totalPrice,
                    'profit_amount' => $profitAmount,
                    'profit_percentage' => $profitPercentage,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product'])),
                'Sale invoice request created successfully. Waiting for manager approval.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale invoice request creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create sale invoice request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified sale invoice.
     */
    public function show(Request $request, SaleInvoice $sale_invoice): JsonResponse
    {
        $representative = $request->user();
        
        if ($sale_invoice->representative_id != $representative->rep_id) {
            return $this->errorResponse('Invoice not found or not yours', 404);
        }

        $sale_invoice->load(['customer', 'representative', 'items.product', 'creator']);
        return $this->successResponse(new SaleInvoiceResource($sale_invoice));
    }

    /**
     * Update the specified sale invoice request (only if pending approval).
     */
    public function update(Request $request, SaleInvoice $sale_invoice): JsonResponse
    {
        $representative = $request->user();
        
        if ($sale_invoice->representative_id != $representative->rep_id) {
            return $this->errorResponse('Invoice not found or not yours', 404);
        }

        if ($sale_invoice->request_status !== 'pending_approval') {
            return $this->errorResponse('Only pending approval invoices can be updated', 422);
        }

        // Similar validation and update logic as store
        // Implementation similar to store but for update
        
        return $this->errorResponse('Update not fully implemented yet. Please cancel and recreate.', 501);
    }

    /**
     * Cancel the specified sale invoice request.
     */
    public function cancel(Request $request, SaleInvoice $sale_invoice): JsonResponse
    {
        $representative = $request->user();
        
        if ($sale_invoice->representative_id != $representative->rep_id) {
            return $this->errorResponse('Invoice not found or not yours', 404);
        }

        if ($sale_invoice->request_status !== 'pending_approval') {
            return $this->errorResponse('Only pending approval invoices can be cancelled', 422);
        }

        try {
            $sale_invoice->request_status = 'rejected';
            $sale_invoice->delivery_status = 'cancelled';
            $sale_invoice->status = 'cancelled';
            $sale_invoice->save();

            return $this->successResponse(null, 'Sale invoice request cancelled successfully');
        } catch (\Exception $e) {
            Log::error('Sale invoice cancellation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to cancel sale invoice request', 500);
        }
    }
}
