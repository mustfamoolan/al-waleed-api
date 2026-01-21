<?php

namespace App\Observers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryBatch;
use App\Models\SupplierTransaction;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceObserver
{
    /**
     * Handle the PurchaseInvoice "created" event.
     */
    public function created(PurchaseInvoice $purchaseInvoice): void
    {
        // When invoice is created, it's in draft status, no action needed
    }

    /**
     * Handle the PurchaseInvoice "updated" event.
     */
    public function updated(PurchaseInvoice $purchaseInvoice): void
    {
        // Check if invoice status changed to approved/posted
        // In the new system, we'll use payment_status or a separate 'status' field
        // For now, we'll trigger on first update after creation when details are added
        
        // This will be handled by a separate method called from controller
    }

    /**
     * Process invoice approval - called from controller
     */
    public function approve(PurchaseInvoice $purchaseInvoice): void
    {
        DB::beginTransaction();
        try {
            // Process each detail item
            foreach ($purchaseInvoice->details()->get() as $detail) {
                // Validate expiry date for food items
                if (!$detail->expiry_date) {
                    throw new \Exception("Expiry date is required for product: {$detail->product_name}");
                }

                // Get the unit
                $unit = $detail->unit;
                if (!$unit) {
                    throw new \Exception("Unit not found for product: {$detail->product_name}");
                }

                // Convert quantity to base unit
                $baseQuantity = $unit->convertToBaseUnit($detail->quantity);

                // Calculate cost per base unit
                $costPerBaseUnit = $detail->getCostPerBaseUnit();

                // Create inventory batch
                $batch = InventoryBatch::create([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $purchaseInvoice->warehouse_id,
                    'batch_number' => $detail->batch_number ?? $this->generateBatchNumber($purchaseInvoice, $detail),
                    'production_date' => null, // Can be added later if needed
                    'expiry_date' => $detail->expiry_date,
                    'cost_price' => $costPerBaseUnit,
                    'quantity_initial' => $baseQuantity,
                    'quantity_current' => $baseQuantity,
                    'purchase_invoice_detail_id' => $detail->id,
                    'status' => 'active',
                ]);

                Log::info("Created inventory batch {$batch->id} for product {$detail->product_id}");
            }

            // Update supplier balance
            $supplier = $purchaseInvoice->supplier;
            $previousBalance = $supplier->current_balance ?? 0;
            $newBalance = $previousBalance + $purchaseInvoice->total_amount;

            $supplier->current_balance = $newBalance;
            $supplier->save();

            // Create supplier transaction (Credit - we owe the supplier)
            SupplierTransaction::create([
                'supplier_id' => $supplier->supplier_id,
                'transaction_type' => 'purchase_invoice',
                'reference_id' => $purchaseInvoice->invoice_id,
                'debit' => 0,
                'credit' => $purchaseInvoice->total_amount,
                'balance_after' => $newBalance,
                'transaction_date' => $purchaseInvoice->invoice_date,
            ]);

            DB::commit();
            Log::info("Purchase invoice {$purchaseInvoice->invoice_id} approved successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error approving purchase invoice: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate batch number if not provided
     */
    private function generateBatchNumber(PurchaseInvoice $invoice, $detail): string
    {
        return 'BATCH-' . $invoice->invoice_number . '-' . $detail->product_id . '-' . time();
    }
}
