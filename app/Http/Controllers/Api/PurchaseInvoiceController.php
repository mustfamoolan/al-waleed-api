<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Warehouse;
use App\Observers\PurchaseInvoiceObserver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceController extends BaseController
{
    /**
     * Display a listing of purchase invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseInvoice::with(['supplier', 'warehouse', 'details']);

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
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
                  ->orWhereHas('supplier', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(PurchaseInvoiceResource::collection($invoices));
    }

    /**
     * Store a newly created purchase invoice.
     */
    public function store(StorePurchaseInvoiceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            // Get default warehouse if not provided
            $warehouseId = $validated['warehouse_id'] ?? Warehouse::where('name', 'المستودع الرئيسي')->value('id');

            if (!$warehouseId) {
                return $this->errorResponse('No warehouse found. Please run WarehouseSeeder first.', 422);
            }

            // Create invoice
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $warehouseId,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'payment_status' => $validated['payment_status'] ?? 'unpaid',
                'payment_method' => $validated['payment_method'] ?? 'deferred',
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Create details
            foreach ($validated['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];
                
                // Apply discount
                if (isset($itemData['discount_percentage']) && $itemData['discount_percentage'] > 0) {
                    $discount = $itemTotal * ($itemData['discount_percentage'] / 100);
                    $itemTotal -= $discount;
                }

                // Apply tax
                if (isset($itemData['tax_percentage']) && $itemData['tax_percentage'] > 0) {
                    $tax = $itemTotal * ($itemData['tax_percentage'] / 100);
                    $itemTotal += $tax;
                }

                PurchaseInvoiceDetail::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'unit_id' => $itemData['unit_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_row' => $itemTotal,
                    'expiry_date' => $itemData['expiry_date'],
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'product_code' => $itemData['product_code'] ?? null,
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                    'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($invoice->load(['supplier', 'warehouse', 'details'])),
                'Purchase invoice created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase invoice creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create purchase invoice', 500);
        }
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(PurchaseInvoice $purchase_invoice): JsonResponse
    {
        $purchase_invoice->load(['supplier', 'warehouse', 'details.product', 'details.unit', 'creator']);
        return $this->successResponse(new PurchaseInvoiceResource($purchase_invoice));
    }

    /**
     * Update the specified purchase invoice.
     */
    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchase_invoice): JsonResponse
    {
        // Only allow updating unpaid invoices
        if ($purchase_invoice->payment_status === 'paid') {
            return $this->errorResponse('Cannot update paid invoices', 422);
        }

        // Check if invoice has batches (already approved)
        if ($purchase_invoice->details()->whereHas('inventoryBatch')->exists()) {
            return $this->errorResponse('Cannot update invoice that has been approved and has inventory batches', 422);
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Update invoice
            $updateData = [];
            if (isset($validated['supplier_id'])) $updateData['supplier_id'] = $validated['supplier_id'];
            if (isset($validated['warehouse_id'])) $updateData['warehouse_id'] = $validated['warehouse_id'];
            if (isset($validated['invoice_number'])) $updateData['invoice_number'] = $validated['invoice_number'];
            if (isset($validated['invoice_date'])) $updateData['invoice_date'] = $validated['invoice_date'];
            if (isset($validated['due_date'])) $updateData['due_date'] = $validated['due_date'];
            if (isset($validated['payment_status'])) $updateData['payment_status'] = $validated['payment_status'];
            if (isset($validated['payment_method'])) $updateData['payment_method'] = $validated['payment_method'];
            if (isset($validated['subtotal'])) $updateData['subtotal'] = $validated['subtotal'];
            if (isset($validated['tax_amount'])) $updateData['tax_amount'] = $validated['tax_amount'];
            if (isset($validated['discount_amount'])) $updateData['discount_amount'] = $validated['discount_amount'];
            if (isset($validated['total_amount'])) $updateData['total_amount'] = $validated['total_amount'];
            if (isset($validated['notes'])) $updateData['notes'] = $validated['notes'];

            $purchase_invoice->update($updateData);

            // Update details if provided
            if (isset($validated['items'])) {
                // Delete old details
                $purchase_invoice->details()->delete();

                // Create new details
                foreach ($validated['items'] as $itemData) {
                    $itemTotal = $itemData['quantity'] * $itemData['unit_price'];
                    
                    if (isset($itemData['discount_percentage']) && $itemData['discount_percentage'] > 0) {
                        $discount = $itemTotal * ($itemData['discount_percentage'] / 100);
                        $itemTotal -= $discount;
                    }

                    if (isset($itemData['tax_percentage']) && $itemData['tax_percentage'] > 0) {
                        $tax = $itemTotal * ($itemData['tax_percentage'] / 100);
                        $itemTotal += $tax;
                    }

                    PurchaseInvoiceDetail::create([
                        'invoice_id' => $purchase_invoice->invoice_id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'unit_id' => $itemData['unit_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total_row' => $itemTotal,
                        'expiry_date' => $itemData['expiry_date'],
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'product_name' => $itemData['product_name'],
                        'product_code' => $itemData['product_code'] ?? null,
                        'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                        'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($purchase_invoice->load(['supplier', 'warehouse', 'details'])),
                'Purchase invoice updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase invoice update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update purchase invoice', 500);
        }
    }

    /**
     * Remove the specified purchase invoice.
     */
    public function destroy(PurchaseInvoice $purchase_invoice): JsonResponse
    {
        // Only allow deleting unpaid invoices
        if ($purchase_invoice->payment_status === 'paid') {
            return $this->errorResponse('Cannot delete paid invoices', 422);
        }

        // Check if invoice has batches
        if ($purchase_invoice->details()->whereHas('inventoryBatch')->exists()) {
            return $this->errorResponse('Cannot delete invoice that has inventory batches', 422);
        }

        $purchase_invoice->delete();
        return $this->successResponse(null, 'Purchase invoice deleted successfully');
    }

    /**
     * Approve a purchase invoice (create inventory batches and update supplier balance).
     */
    public function approve(PurchaseInvoice $purchase_invoice): JsonResponse
    {
        if ($purchase_invoice->payment_status === 'paid') {
            return $this->errorResponse('Invoice is already processed', 422);
        }

        // Check if already approved
        if ($purchase_invoice->details()->whereHas('inventoryBatch')->exists()) {
            return $this->errorResponse('Invoice is already approved', 422);
        }

        try {
            $observer = new PurchaseInvoiceObserver();
            $observer->approve($purchase_invoice);

            return $this->successResponse(
                new PurchaseInvoiceResource($purchase_invoice->load(['supplier', 'warehouse', 'details'])),
                'Purchase invoice approved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Purchase invoice approval error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
