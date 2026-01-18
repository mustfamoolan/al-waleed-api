<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleInvoice\StoreSaleInvoiceRequest;
use App\Http\Requests\SaleInvoice\UpdateSaleInvoiceRequest;
use App\Http\Resources\SaleInvoiceResource;
use App\Http\Resources\SaleInvoiceItemResource;
use App\Http\Resources\CustomerPaymentResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Representative;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleInvoiceController extends BaseController
{
    /**
     * Display a listing of sale invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SaleInvoice::with(['customer', 'representative', 'items']);

        // Filter by buyer type
        if ($request->has('buyer_type')) {
            $query->where('buyer_type', $request->get('buyer_type'));
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        // Filter by representative (seller)
        if ($request->has('representative_id')) {
            $query->where('representative_id', $request->get('representative_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('invoice_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('invoice_date', '<=', $request->get('to_date'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Store a newly created sale invoice.
     */
    public function store(StoreSaleInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $manager = $request->user();

            // Validate buyer type specific fields
            if ($validated['buyer_type'] === 'customer') {
                if (!$validated['customer_id']) {
                    return $this->errorResponse('customer_id is required when buyer_type is customer', 422);
                }
            } elseif ($validated['buyer_type'] === 'walk_in') {
                if (!$validated['buyer_name']) {
                    return $this->errorResponse('buyer_name is required when buyer_type is walk_in', 422);
                }
                $validated['payment_method'] = 'cash'; // Force cash for walk_in
            } elseif ($validated['buyer_type'] === 'employee') {
                if (!$validated['buyer_id'] || !Employee::find($validated['buyer_id'])) {
                    return $this->errorResponse('valid buyer_id (employee) is required', 422);
                }
                $validated['payment_method'] = 'cash'; // Force cash for employees
            } elseif ($validated['buyer_type'] === 'representative') {
                if (!$validated['buyer_id'] || !Representative::find($validated['buyer_id'])) {
                    return $this->errorResponse('valid buyer_id (representative) is required', 422);
                }
                $validated['payment_method'] = 'cash'; // Force cash for representatives
            }

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

            // Calculate special discount
            $specialDiscountAmount = 0;
            if (in_array($validated['buyer_type'], ['employee', 'representative'])) {
                $specialDiscountPercentage = $validated['special_discount_percentage'] ?? 0;
                $specialDiscountAmount = $validated['subtotal'] * ($specialDiscountPercentage / 100);
            }

            $totalAmount = $validated['subtotal'] 
                - ($validated['discount_amount'] ?? 0) 
                - $specialDiscountAmount 
                + ($validated['tax_amount'] ?? 0);

            // Create invoice
            $invoice = SaleInvoice::create([
                'representative_id' => $validated['representative_id'] ?? null,
                'buyer_type' => $validated['buyer_type'],
                'buyer_id' => $validated['buyer_id'] ?? null,
                'buyer_name' => $validated['buyer_name'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'special_discount_percentage' => $validated['special_discount_percentage'] ?? 0,
                'special_discount_amount' => $specialDiscountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
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
                
                // Apply discount
                $discountAmount = 0;
                if (isset($itemData['discount_percentage']) && $itemData['discount_percentage'] > 0) {
                    $discountAmount = $itemSubtotal * ($itemData['discount_percentage'] / 100);
                    $itemSubtotal -= $discountAmount;
                }

                // Apply tax
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
                new SaleInvoiceResource($invoice->load(['customer', 'representative', 'items.product', 'creator'])),
                'Sale invoice created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale invoice creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create sale invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified sale invoice.
     */
    public function show(SaleInvoice $sale_invoice): JsonResponse
    {
        $sale_invoice->load(['customer', 'representative', 'items.product', 'creator', 'payments']);
        return $this->successResponse(new SaleInvoiceResource($sale_invoice));
    }

    /**
     * Update the specified sale invoice.
     */
    public function update(UpdateSaleInvoiceRequest $request, SaleInvoice $sale_invoice): JsonResponse
    {
        if ($sale_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be updated', 422);
        }

        // Similar validation and update logic as store
        // ... (implement similar to store but for update)
        
        return $this->errorResponse('Update not fully implemented yet', 501);
    }

    /**
     * Remove the specified sale invoice.
     */
    public function destroy(SaleInvoice $sale_invoice): JsonResponse
    {
        if ($sale_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be deleted', 422);
        }

        try {
            $sale_invoice->delete();
            return $this->successResponse(null, 'Sale invoice deleted successfully');
        } catch (\Exception $e) {
            Log::error('Sale invoice deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete sale invoice', 500);
        }
    }

    /**
     * Post (confirm) a sale invoice.
     */
    public function post(SaleInvoice $sale_invoice): JsonResponse
    {
        if ($sale_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be posted', 422);
        }

        try {
            DB::beginTransaction();

            $manager = request()->user();

            // Update inventory for each item
            foreach ($sale_invoice->items as $item) {
                $product = Product::find($item->product_id);
                
                if (!$product) {
                    throw new \Exception("Product not found: {$item->product_id}");
                }

                // Calculate quantity in pieces and check stock
                $quantityInPieces = $item->getQuantityInPieces();
                if ($product->current_stock < $quantityInPieces) {
                    throw new \Exception("Insufficient stock for product: {$product->product_name}");
                }

                // Update stock (convert to pieces)
                $stockBefore = $product->current_stock;
                $product->updateStock(-$quantityInPieces, 'sale');
                $stockAfter = $product->current_stock;

                // Update last sale date
                $product->updateLastSaleDate($sale_invoice->invoice_date);

                // Create inventory movement
                InventoryMovement::create([
                    'product_id' => $product->product_id,
                    'movement_type' => 'sale',
                    'reference_type' => 'sale_invoice',
                    'reference_id' => $sale_invoice->invoice_id,
                    'quantity' => -$quantityInPieces,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_price' => $item->unit_price,
                    'notes' => "From sale invoice: {$sale_invoice->invoice_number}",
                    'created_by' => $manager->manager_id,
                ]);
            }

            // Update invoice status
            $sale_invoice->status = 'pending';
            $sale_invoice->updateStatus();

            // Update customer balance if credit and customer
            if ($sale_invoice->buyer_type === 'customer' && $sale_invoice->payment_method === 'credit') {
                $balance = CustomerBalance::getOrCreate($sale_invoice->customer_id);
                $balance->recordTransaction(
                    'invoice',
                    $sale_invoice->remaining_amount,
                    "فاتورة بيع: {$sale_invoice->invoice_number}",
                    'sale_invoice',
                    $sale_invoice->invoice_id,
                    $manager->manager_id
                );

                // Update customer totals
                $customer = $sale_invoice->customer;
                if ($customer) {
                    $customer->total_debt += $sale_invoice->remaining_amount;
                    $customer->save();
                }
            }

            DB::commit();

            return $this->successResponse(
                new SaleInvoiceResource($sale_invoice->load(['customer', 'representative', 'items.product', 'creator'])),
                'Sale invoice posted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale invoice post error: ' . $e->getMessage());
            return $this->errorResponse('Failed to post sale invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Duplicate a sale invoice.
     */
    public function duplicate(SaleInvoice $sale_invoice): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newInvoice = $sale_invoice->replicate();
            $newInvoice->invoice_number = 'SAL-' . date('Y') . '-' . str_pad(SaleInvoice::max('invoice_id') + 1, 4, '0', STR_PAD_LEFT);
            $newInvoice->invoice_date = now();
            $newInvoice->status = 'draft';
            $newInvoice->paid_amount = 0;
            $newInvoice->remaining_amount = $newInvoice->total_amount;
            $newInvoice->created_by = request()->user()->manager_id;
            $newInvoice->save();

            // Duplicate items
            foreach ($sale_invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->invoice_id;
                $newItem->save();
            }

            DB::commit();

            return $this->successResponse(
                new SaleInvoiceResource($newInvoice->load(['customer', 'representative', 'items', 'creator'])),
                'Sale invoice duplicated successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale invoice duplication error: ' . $e->getMessage());
            return $this->errorResponse('Failed to duplicate sale invoice', 500);
        }
    }

    /**
     * Get payments for a sale invoice.
     */
    public function payments(SaleInvoice $sale_invoice): JsonResponse
    {
        $payments = $sale_invoice->payments()->with(['customer', 'creator'])->orderBy('payment_date', 'desc')->get();
        return $this->successResponse(CustomerPaymentResource::collection($payments));
    }

    /**
     * Get pending approval invoices.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $query = SaleInvoice::where('request_status', 'pending_approval')
            ->with(['customer', 'representative', 'creator']);

        if ($request->has('request_type')) {
            $query->where('request_type', $request->get('request_type'));
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Approve invoice request.
     */
    public function approveRequest(Request $request, SaleInvoice $sale_invoice): JsonResponse
    {
        if ($sale_invoice->request_status !== 'pending_approval') {
            return $this->errorResponse('Only pending approval invoices can be approved', 422);
        }

        try {
            $manager = $request->user();
            
            $sale_invoice->request_status = 'approved';
            $sale_invoice->approved_by = $manager->manager_id;
            $sale_invoice->approved_at = now();
            $sale_invoice->save();

            return $this->successResponse(
                new SaleInvoiceResource($sale_invoice->load(['customer', 'representative', 'approver'])),
                'Invoice request approved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Invoice approval error: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve invoice request', 500);
        }
    }

    /**
     * Reject invoice request.
     */
    public function rejectRequest(Request $request, SaleInvoice $sale_invoice): JsonResponse
    {
        if ($sale_invoice->request_status !== 'pending_approval') {
            return $this->errorResponse('Only pending approval invoices can be rejected', 422);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $manager = $request->user();
            
            $sale_invoice->request_status = 'rejected';
            $sale_invoice->approved_by = $manager->manager_id;
            $sale_invoice->approved_at = now();
            $sale_invoice->rejection_reason = $validated['rejection_reason'];
            $sale_invoice->delivery_status = 'cancelled';
            $sale_invoice->status = 'cancelled';
            $sale_invoice->save();

            return $this->successResponse(
                new SaleInvoiceResource($sale_invoice->load(['customer', 'representative', 'approver'])),
                'Invoice request rejected successfully'
            );
        } catch (\Exception $e) {
            Log::error('Invoice rejection error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reject invoice request', 500);
        }
    }

    /**
     * Get representative sales report.
     */
    public function representativeSalesReport(Request $request, Representative $representative): JsonResponse
    {
        $query = SaleInvoice::where('representative_id', $representative->rep_id)
            ->where('delivery_status', 'delivered');

        // Date filters
        if ($request->has('month')) {
            $month = $request->get('month'); // Format: Y-m
            $query->whereYear('invoice_date', substr($month, 0, 4))
                  ->whereMonth('invoice_date', substr($month, 5, 2));
        } elseif ($request->has('from_date')) {
            $query->where('invoice_date', '>=', $request->get('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->where('invoice_date', '<=', $request->get('to_date'));
        }

        // Total sales
        $totalSales = (clone $query)->selectRaw('
            COUNT(*) as total_invoices,
            SUM(total_amount) as total_amount,
            SUM(CASE WHEN payment_method = "cash" THEN total_amount ELSE 0 END) as cash_sales,
            SUM(CASE WHEN payment_method = "credit" THEN total_amount ELSE 0 END) as credit_sales
        ')->first();

        // Customers data
        $customersData = (clone $query)->selectRaw('
            customer_id,
            COUNT(*) as invoice_count,
            SUM(total_amount) as total_purchases
        ')
        ->whereNotNull('customer_id')
        ->groupBy('customer_id')
        ->orderBy('total_purchases', 'desc')
        ->limit(10)
        ->get()
        ->load('customer');

        $topCustomers = $customersData->map(function ($item) {
            return [
                'customer_id' => $item->customer_id,
                'customer_name' => $item->customer->customer_name ?? 'Unknown',
                'total_purchases' => $item->total_purchases,
                'invoice_count' => $item->invoice_count,
            ];
        });

        // Today sales
        $todaySales = (clone $query)->whereDate('invoice_date', today())
            ->selectRaw('COUNT(*) as invoices_count, SUM(total_amount) as total_amount')
            ->first();

        // Customer debts
        $customerDebts = SaleInvoice::where('representative_id', $representative->rep_id)
            ->where('buyer_type', 'customer')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->selectRaw('SUM(remaining_amount) as total_debt')
            ->first();

        // Delivery status breakdown
        $deliveryStatus = SaleInvoice::where('representative_id', $representative->rep_id)
            ->selectRaw('
                delivery_status,
                COUNT(*) as count
            ')
            ->groupBy('delivery_status')
            ->get()
            ->pluck('count', 'delivery_status');

        return $this->successResponse([
            'representative' => [
                'rep_id' => $representative->rep_id,
                'full_name' => $representative->full_name,
            ],
            'period' => [
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'month' => $request->get('month'),
            ],
            'total_sales' => [
                'total_invoices' => $totalSales->total_invoices ?? 0,
                'total_amount' => $totalSales->total_amount ?? 0,
                'cash_sales' => $totalSales->cash_sales ?? 0,
                'credit_sales' => $totalSales->credit_sales ?? 0,
            ],
            'customers' => [
                'total_customers' => $customersData->count(),
                'total_debt' => $customerDebts->total_debt ?? 0,
                'top_customers' => $topCustomers,
            ],
            'today_sales' => [
                'invoices_count' => $todaySales->invoices_count ?? 0,
                'total_amount' => $todaySales->total_amount ?? 0,
            ],
            'delivery_status' => [
                'delivered' => $deliveryStatus['delivered'] ?? 0,
                'in_delivery' => $deliveryStatus['in_delivery'] ?? 0,
                'prepared' => $deliveryStatus['prepared'] ?? 0,
                'preparing' => $deliveryStatus['preparing'] ?? 0,
                'not_prepared' => $deliveryStatus['not_prepared'] ?? 0,
                'cancelled' => $deliveryStatus['cancelled'] ?? 0,
            ],
        ]);
    }
}
