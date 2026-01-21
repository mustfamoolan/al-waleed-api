<?php

namespace App\Observers;

use App\Models\PurchaseReturn;
use App\Models\InventoryBatch;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnObserver
{
    /**
     * Handle the PurchaseReturn "created" event.
     */
    public function created(PurchaseReturn $purchaseReturn): void
    {
        DB::beginTransaction();
        try {
            // Process each return detail
            foreach ($purchaseReturn->details()->get() as $detail) {
                // Validate batch exists and has enough quantity
                $batch = $detail->batch;
                if (!$batch) {
                    throw new \Exception("Batch not found for return detail: {$detail->id}");
                }

                if ($batch->quantity_current < $detail->quantity) {
                    throw new \Exception("Insufficient quantity in batch {$batch->id}. Available: {$batch->quantity_current}, Requested: {$detail->quantity}");
                }

                // Deduct quantity from batch
                $batch->deductQuantity($detail->quantity);

                Log::info("Deducted {$detail->quantity} from batch {$batch->id}");
            }

            // Update supplier balance (decrease debt)
            $supplier = $purchaseReturn->supplier;
            $previousBalance = $supplier->current_balance ?? 0;
            $newBalance = $previousBalance - $purchaseReturn->total_amount;

            $supplier->current_balance = $newBalance;
            $supplier->save();

            // Create supplier transaction (Debit - we return money/credit to supplier)
            SupplierTransaction::create([
                'supplier_id' => $supplier->supplier_id,
                'transaction_type' => 'purchase_return',
                'reference_id' => $purchaseReturn->id,
                'debit' => $purchaseReturn->total_amount,
                'credit' => 0,
                'balance_after' => $newBalance,
                'transaction_date' => $purchaseReturn->return_date,
            ]);

            DB::commit();
            Log::info("Purchase return {$purchaseReturn->id} processed successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing purchase return: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle the PurchaseReturn "updated" event.
     */
    public function updated(PurchaseReturn $purchaseReturn): void
    {
        // Handle updates if needed
    }

    /**
     * Handle the PurchaseReturn "deleted" event.
     */
    public function deleted(PurchaseReturn $purchaseReturn): void
    {
        // Reverse the transactions if return is deleted
        DB::beginTransaction();
        try {
            // Reverse inventory batches
            foreach ($purchaseReturn->details()->get() as $detail) {
                if ($detail->batch) {
                    $detail->batch->addQuantity($detail->quantity);
                }
            }

            // Reverse supplier transaction
            $supplier = $purchaseReturn->supplier;
            $previousBalance = $supplier->current_balance ?? 0;
            $newBalance = $previousBalance + $purchaseReturn->total_amount;

            $supplier->current_balance = $newBalance;
            $supplier->save();

            // Delete the transaction
            SupplierTransaction::where('transaction_type', 'purchase_return')
                ->where('reference_id', $purchaseReturn->id)
                ->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error reversing purchase return: " . $e->getMessage());
            throw $e;
        }
    }
}
