<?php

namespace App\Observers;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceObserver
{
    public function updated(PurchaseInvoice $invoice)
    {
        if ($invoice->isDirty('status') && $invoice->status === 'posted') {
            DB::transaction(function () use ($invoice) {
                // 1. Create Inventory Transaction
                $transaction = InventoryTransaction::create([
                    'trans_date' => $invoice->invoice_date,
                    'trans_type' => 'purchase',
                    'warehouse_id' => $invoice->warehouse_id ?? 1,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice->id,
                    'created_by' => auth()->id(),
                    'note' => 'Generated from Purchase Invoice #' . $invoice->invoice_no,
                ]);

                foreach ($invoice->lines as $line) {
                    $baseQty = $line->qty * $line->unit_factor;
                    $costIqd = $line->is_free ? 0 : ($line->line_total_iqd / ($baseQty ?: 1));

                    InventoryTransactionLine::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $line->product_id,
                        'qty' => $line->qty,
                        'unit_id' => $line->unit_id,
                        'unit_factor' => $line->unit_factor,
                        'cost_iqd' => $costIqd,
                    ]);

                    $balance = InventoryBalance::firstOrNew([
                        'warehouse_id' => $invoice->warehouse_id ?? 1,
                        'product_id' => $line->product_id
                    ]);

                    $oldQty = $balance->qty_on_hand ?? 0;
                    $oldCost = $balance->avg_cost_iqd ?? 0;
                    $newQty = $baseQty;
                    $newCost = $costIqd;

                    if ($line->is_free) {
                        $totalValue = ($oldQty * $oldCost);
                        $totalQty = $oldQty + $newQty;
                        $balance->qty_on_hand = $totalQty;
                        $balance->avg_cost_iqd = $totalQty > 0 ? $totalValue / $totalQty : 0;
                    } else {
                        $totalValue = ($oldQty * $oldCost) + ($newQty * $newCost);
                        $totalQty = $oldQty + $newQty;
                        $balance->qty_on_hand = $totalQty;
                        $balance->avg_cost_iqd = $totalQty > 0 ? $totalValue / $totalQty : $newCost;
                    }
                    $balance->save();

                    // Update Product Master Data
                    $product = $line->product;
                    if ($product) {
                        $product->update([
                            'sale_price_retail' => $line->sale_price_retail,
                            'sale_price_wholesale' => $line->sale_price_wholesale,
                            'units_per_pack' => $line->unit_factor,
                            'purchase_price' => $line->price_foreign,
                        ]);
                    }
                }

                // 2. Create Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => $invoice->invoice_date,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice->id,
                    'description' => 'Purchase Invoice #' . $invoice->invoice_no,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                $inventoryAccount = \App\Models\Account::where('account_code', '1301')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => $invoice->total_iqd,
                    'credit_amount' => 0,
                ]);

                $payableAccount = \App\Models\Account::where('account_code', '2101')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $invoice->supplier->account_id ?? $payableAccount->id,
                    'partner_type' => 'supplier',
                    'partner_id' => $invoice->supplier_id,
                    'debit_amount' => 0,
                    'credit_amount' => $invoice->total_iqd,
                ]);

                if ($invoice->paid_iqd > 0) {
                    $cashAccount = \App\Models\Account::where('account_code', '1101')->first();
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $invoice->supplier->account_id ?? $payableAccount->id,
                        'partner_type' => 'supplier',
                        'partner_id' => $invoice->supplier_id,
                        'debit_amount' => $invoice->paid_iqd,
                        'credit_amount' => 0,
                    ]);
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $cashAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $invoice->paid_iqd,
                    ]);
                }

                $invoice->journal_entry_id = $journal->id;
                $invoice->saveQuietly();
            });
        }
    }
}
