<?php

namespace App\Observers;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

class SalesReturnObserver
{
    public function updated(SalesReturn $return)
    {
        if ($return->isDirty('status') && $return->status === 'posted') {
            DB::transaction(function () use ($return) {
                // 1. Inventory Transaction (IN - Return)
                $transaction = InventoryTransaction::create([
                    'trans_date' => $return->return_date,
                    'trans_type' => 'sale_return',
                    'warehouse_id' => 1,
                    'reference_type' => 'sales_return',
                    'reference_id' => $return->id,
                    'created_by' => auth()->id(),
                    'note' => 'Sales Return #' . $return->return_no,
                ]);

                $totalCost = 0;

                foreach ($return->lines as $line) {
                    $baseQty = $line->qty * $line->unit_factor;

                    // Cost? Use snapshot if available from original invoice line?
                    // For now, assume stored in return line (passed from controller)
                    // If not, fall back to current average (less accurate)
                    $costIqd = $line->cost_iqd_snapshot > 0 ? $line->cost_iqd_snapshot : 0;
                    // If we assume user passed it or copied it from invoice.

                    $totalCost += ($baseQty * $costIqd);

                    InventoryTransactionLine::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $line->product_id,
                        'qty' => $line->qty,
                        'unit_id' => $line->unit_id,
                        'unit_factor' => $line->unit_factor,
                        'cost_iqd' => $costIqd,
                    ]);

                    // Update Inventory Balance
                    // Increase Qty. Value increases by (Qty * Cost).
                    // Weighted Average MIGHT change if return cost != current average.
                    $balance = InventoryBalance::firstOrNew([
                        'warehouse_id' => 1,
                        'product_id' => $line->product_id
                    ]);

                    $oldQty = $balance->qty_on_hand ?? 0;
                    $oldCost = $balance->avg_cost_iqd ?? 0; // Current avg

                    $newQty = $baseQty;
                    $returnValue = $newQty * $costIqd;

                    $totalQty = $oldQty + $newQty;
                    $totalValue = ($oldQty * $oldCost) + $returnValue;

                    $balance->qty_on_hand = $totalQty;
                    $balance->avg_cost_iqd = $totalQty > 0 ? $totalValue / $totalQty : $costIqd;
                    $balance->save();
                }

                // 2. Journal Entry (Reverse Sales)
                $journal = JournalEntry::create([
                    'entry_date' => $return->return_date,
                    'reference_type' => 'sales_return',
                    'reference_id' => $return->id,
                    'description' => 'مرتجع مبيعات رقم ' . $return->return_no,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // Dr Sales Revenue (Reverse Revenue)
                $revenueAccount = Account::where('account_code', '4101')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => $return->total_iqd, // Debit Revenue to decrease
                    'credit_amount' => 0,
                ]);

                // Cr Cash or AR
                // If original was Cash -> Credit Cash
                // If original was Credit -> Credit Customer AR
                // Logic: Need to know original invoice payment type?
                // For simplicity, if customer_id exists, credit AR. Else Credit Cash. 
                // Or check linked invoice.
                $invoice = $return->invoice;
                $creditAccountId = null;

                if ($invoice && $invoice->payment_type === 'credit') {
                    $customerAccount = Account::where('account_code', '1201')->first();
                    $creditAccountId = $invoice->customer->account_id ?? $customerAccount->id;
                } else {
                    $cashAccount = Account::where('account_code', '1101')->first();
                    $creditAccountId = $cashAccount->id;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $creditAccountId,
                    'partner_type' => ($invoice && $invoice->payment_type === 'credit') ? 'customer' : null,
                    'partner_id' => $invoice?->customer_id,
                    'debit_amount' => 0,
                    'credit_amount' => $return->total_iqd,
                ]);

                // Reverse COGS (if we did COGS entry)
                // Dr Inventory / Cr COGS
                if ($totalCost > 0) {
                    $cogsAccount = Account::where('account_code', '5101')->first();
                    $inventoryAccount = Account::where('account_code', '1301')->first();

                    if ($cogsAccount && $inventoryAccount) {
                        // Dr Inventory Asset (Increase)
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $inventoryAccount->id,
                            'debit_amount' => $totalCost, // Debit Asset to increase
                            'credit_amount' => 0,
                        ]);
                        // Cr COGS (Decrease Expense)
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $cogsAccount->id,
                            'debit_amount' => 0,
                            'credit_amount' => $totalCost,
                        ]);
                    }
                }

                $return->journal_entry_id = $journal->id;
                $return->saveQuietly();
            });
        }
    }
}
