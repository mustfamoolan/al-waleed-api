<?php

namespace App\Observers;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class PurchaseReturnObserver
{
    public function updated(PurchaseReturn $return)
    {
        if ($return->isDirty('status') && $return->status === 'posted') {
            DB::transaction(function () use ($return) {
                // 1. Inventory Transaction (OUT)
                $transaction = InventoryTransaction::create([
                    'trans_date' => $return->return_date,
                    'trans_type' => 'purchase_return',
                    'warehouse_id' => 1,
                    'reference_type' => 'purchase_return',
                    'reference_id' => $return->id,
                    'created_by' => auth()->id(),
                    'note' => 'Purchase Return #' . $return->return_no,
                ]);

                foreach ($return->lines as $line) {
                    // Note: Logic here simplistically removes qty. 
                    // Cost recovery logic can be complex (FIFO vs Avg). Using current avg or invoice cost.

                    InventoryTransactionLine::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $line->product_id,
                        'qty' => $line->qty, // Qty is positive here, logic interprets based on trans_type
                        'unit_id' => $line->unit_id,
                        'unit_factor' => $line->unit_factor,
                        'cost_iqd' => 0, // Outbound cost usually calculated dynamically or logged
                    ]);

                    // Decrease Balance
                    $balance = InventoryBalance::where('warehouse_id', 1)
                        ->where('product_id', $line->product_id)
                        ->first();

                    if ($balance) {
                        $baseQty = $line->qty * $line->unit_factor;
                        $balance->qty_on_hand -= $baseQty;
                        // Avg cost remains same on output usually, unless specific logic
                        $balance->save();
                    }
                }

                // 2. Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => $return->return_date,
                    'reference_type' => 'purchase_return',
                    'reference_id' => $return->id,
                    'description' => 'Purchase Return #' . $return->return_no,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // Debit: Supplier (2101) - Reducing debt
                $supplierAccount = \App\Models\Account::where('account_code', '2101')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $return->supplier->account_id ?? $supplierAccount->id,
                    'debit_amount' => $return->total_iqd,
                    'credit_amount' => 0,
                ]);

                // Credit: Inventory (1301)
                $inventoryAccount = \App\Models\Account::where('account_code', '1301')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $return->total_iqd,
                ]);

                $return->journal_entry_id = $journal->id;
                $return->saveQuietly();
            });
        }
    }
}
