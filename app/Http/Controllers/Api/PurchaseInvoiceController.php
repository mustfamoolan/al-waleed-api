<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
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
        $query = PurchaseInvoice::with(['supplier', 'items']);

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
                      $q->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();
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

            // Create invoice
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'paid_amount' => 0,
                'remaining_amount' => $validated['total_amount'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Create items
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

                PurchaseInvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_name' => $itemData['product_name'],
                    'product_code' => $itemData['product_code'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                    'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                    'total_price' => $itemTotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($invoice->load(['supplier', 'items'])),
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
        $purchase_invoice->load(['supplier', 'items', 'creator']);
        return $this->successResponse(new PurchaseInvoiceResource($purchase_invoice));
    }

    /**
     * Update the specified purchase invoice.
     */
    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchase_invoice): JsonResponse
    {
        // Only allow updating draft invoices
        if ($purchase_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be updated', 422);
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Update invoice
            $updateData = [];
            if (isset($validated['supplier_id'])) $updateData['supplier_id'] = $validated['supplier_id'];
            if (isset($validated['invoice_number'])) $updateData['invoice_number'] = $validated['invoice_number'];
            if (isset($validated['invoice_date'])) $updateData['invoice_date'] = $validated['invoice_date'];
            if (isset($validated['due_date'])) $updateData['due_date'] = $validated['due_date'];
            if (isset($validated['subtotal'])) $updateData['subtotal'] = $validated['subtotal'];
            if (isset($validated['tax_amount'])) $updateData['tax_amount'] = $validated['tax_amount'];
            if (isset($validated['discount_amount'])) $updateData['discount_amount'] = $validated['discount_amount'];
            if (isset($validated['total_amount'])) {
                $updateData['total_amount'] = $validated['total_amount'];
                $updateData['remaining_amount'] = $validated['total_amount'] - $purchase_invoice->paid_amount;
            }
            if (isset($validated['notes'])) $updateData['notes'] = $validated['notes'];

            $purchase_invoice->update($updateData);

            // Update items if provided
            if (isset($validated['items'])) {
                // Delete old items
                $purchase_invoice->items()->delete();

                // Create new items
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

                    PurchaseInvoiceItem::create([
                        'invoice_id' => $purchase_invoice->invoice_id,
                        'product_name' => $itemData['product_name'],
                        'product_code' => $itemData['product_code'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'discount_percentage' => $itemData['discount_percentage'] ?? 0,
                        'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                        'total_price' => $itemTotal,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($purchase_invoice->load(['supplier', 'items'])),
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
        // Only allow deleting draft invoices
        if ($purchase_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be deleted', 422);
        }

        $purchase_invoice->delete();
        return $this->successResponse(null, 'Purchase invoice deleted successfully');
    }

    /**
     * Duplicate a purchase invoice.
     */
    public function duplicate(PurchaseInvoice $purchase_invoice): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newInvoice = $purchase_invoice->replicate();
            $newInvoice->invoice_number = 'PUR-' . date('Y') . '-' . str_pad(PurchaseInvoice::max('invoice_id') + 1, 4, '0', STR_PAD_LEFT);
            $newInvoice->invoice_date = now();
            $newInvoice->status = 'draft';
            $newInvoice->paid_amount = 0;
            $newInvoice->remaining_amount = $newInvoice->total_amount;
            $newInvoice->created_by = request()->user()->manager_id;
            $newInvoice->save();

            // Duplicate items
            foreach ($purchase_invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->invoice_id;
                $newItem->save();
            }

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($newInvoice->load(['supplier', 'items'])),
                'Purchase invoice duplicated successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase invoice duplication error: ' . $e->getMessage());
            return $this->errorResponse('Failed to duplicate purchase invoice', 500);
        }
    }

    /**
     * Post a purchase invoice (change status from draft to pending).
     */
    public function post(PurchaseInvoice $purchase_invoice): JsonResponse
    {
        if ($purchase_invoice->status !== 'draft') {
            return $this->errorResponse('Only draft invoices can be posted', 422);
        }

        try {
            DB::beginTransaction();

            $manager = request()->user();

            // Process each item and update inventory
            foreach ($purchase_invoice->items as $item) {
                $product = null;

                // Try to find product by SKU first
                if ($item->product_code) {
                    $product = Product::where('sku', $item->product_code)->first();
                }

                // If not found by SKU, try by name
                if (!$product && $item->product_name) {
                    $product = Product::where('product_name', $item->product_name)
                        ->where('supplier_id', $purchase_invoice->supplier_id)
                        ->first();
                }

                if ($product) {
                    // Update product stock
                    $stockBefore = $product->current_stock;
                    $product->updateStock($item->quantity, 'purchase');
                    $stockAfter = $product->current_stock;

                    // Update product purchase price and date
                    $product->purchase_price = $item->unit_price;
                    $product->last_purchase_date = $purchase_invoice->invoice_date;
                    $product->save();

                    // Create inventory movement
                    $movement = InventoryMovement::create([
                        'product_id' => $product->product_id,
                        'movement_type' => 'purchase',
                        'reference_type' => 'purchase_invoice',
                        'reference_id' => $purchase_invoice->invoice_id,
                        'quantity' => $item->quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_price' => $item->unit_price,
                        'notes' => "From invoice: {$purchase_invoice->invoice_number}",
                        'created_by' => $manager->manager_id,
                    ]);

                    // Link item to product and movement
                    $item->product_id = $product->product_id;
                    $item->inventory_movement_id = $movement->movement_id;
                    $item->save();
                }
            }

            $purchase_invoice->status = 'pending';
            $purchase_invoice->save();
            $purchase_invoice->updateStatus();

            // TODO: Create journal entry here when accounting system is ready

            DB::commit();

            return $this->successResponse(
                new PurchaseInvoiceResource($purchase_invoice->load(['supplier', 'items'])),
                'Purchase invoice posted successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase invoice post error: ' . $e->getMessage());
            return $this->errorResponse('Failed to post purchase invoice', 500);
        }
    }
}
