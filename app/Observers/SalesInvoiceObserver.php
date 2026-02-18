<?php

namespace App\Observers;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

class SalesInvoiceObserver
{
    public function updated(SalesInvoice $invoice)
    {
        // Handle Status: PREPARED (Stock Deduction)
        if ($invoice->isDirty('status') && $invoice->status === 'prepared') {
            DB::transaction(function () use ($invoice) {

                // 1. Inventory Transaction (OUT)
                $transaction = InventoryTransaction::create([
                    'trans_date' => now(), // Prepared Date
                    'trans_type' => 'sale', // OUT
                    'warehouse_id' => 1, // Default Warehouse for now
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'created_by' => auth()->id(),
                    'note' => 'Sales Order Prepared #' . $invoice->invoice_no,
                ]);

                foreach ($invoice->lines as $line) {
                    $baseQty = $line->qty * $line->unit_factor;

                    // Get Current Cost from Inventory Balance
                    $balance = InventoryBalance::firstOrNew([
                        'warehouse_id' => 1,
                        'product_id' => $line->product_id
                    ]);

                    $currentAvgCost = $balance->avg_cost_iqd ?? 0;

                    // Update Line with Cost Snapshot
                    $line->cost_iqd_snapshot = $currentAvgCost;
                    $line->saveQuietly();

                    // Create Transaction Line
                    InventoryTransactionLine::create([
                        'inventory_transaction_id' => $transaction->id,
                        'product_id' => $line->product_id,
                        'qty' => -$line->qty, // Negative for OUT? Or logic handles type?
                        // Convention: trans_type='sale' implicitly means OUT. Qty usually stored positive in line, logic handled by type. 
                        // Let's store Positive here and let Balance logic handle deduction.
                        // Checking InventoryController logic? It was manual update. 
                        // Let's deduct from balance here.
                        'unit_id' => $line->unit_id,
                        'unit_factor' => $line->unit_factor,
                        'cost_iqd' => $currentAvgCost,
                    ]);

                    // Update Inventory Balance
                    // Reduce Qty. Cost remains same (Weighted Average doesn't change on valid OUT, only IN)
                    $balance->qty_on_hand -= $baseQty;
                    $balance->save();
                }
            });
        }

        // Handle Status: DELIVERED (Revenue Recognition)
        if ($invoice->isDirty('status') && $invoice->status === 'delivered') {
            DB::transaction(function () use ($invoice) {
                // 1. Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => $invoice->delivered_at ?? now(), // or now()
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'description' => 'فاتورة مبيعات رقم ' . $invoice->invoice_no, // Kept original as the provided replacement was for a different object ($receipt)
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // Get Required Accounts
                $cashAccount = Account::where('account_code', '1101')->first(); // Cash
                $defaultARAccount = Account::where('account_code', '1201')->first(); // Trade Receivables
                $revenueAccount = Account::where('account_code', '4101')->first(); // Sales Revenue

                // Determine Customer's AR Account (prefer specific customer account if exists)
                $customerAccount = null;
                if ($invoice->customer_id && $invoice->customer && $invoice->customer->account_id) {
                    $customerAccount = Account::find($invoice->customer->account_id);
                } else {
                    $customerAccount = $defaultARAccount;
                }

                // Revenue Entry - Split between Cash and AR based on paid/remaining amounts

                // 1. If there's a paid amount, record it in Cash
                if ($invoice->paid_iqd > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $cashAccount->id, // 1101 - Cash
                        'debit_amount' => 0, // Should be debit for cash received
                        'credit_amount' => $invoice->paid_iqd, // Should be credit for cash received
                        'description' => 'Paid amount',
                    ]);
                }

                // 2. If there's a remaining amount, record it in Accounts Receivable
                if ($invoice->remaining_iqd > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $customerAccount->id, // 1201 - AR
                        'partner_type' => 'customer',
                        'partner_id' => $invoice->customer_id,
                        'debit_amount' => $invoice->remaining_iqd,
                        'credit_amount' => 0,
                        'description' => 'Remaining balance',
                    ]);
                }

                // 3. Credit side (Revenue) - always the full amount
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $revenueAccount->id, // 4101 - Sales Revenue
                    'debit_amount' => 0,
                    'credit_amount' => $invoice->total_iqd,
                    'description' => 'إيرادات مبيعات رقم ' . $invoice->invoice_no, // Changed to reflect sales revenue
                ]);

                // OPTIONAL: COGS Entry (Cost of Goods Sold)
                // Dr COGS (5101) / Cr Inventory (1301)
                // Calculate Total Cost
                $totalCost = 0;
                foreach ($invoice->lines as $line) {
                    $baseQty = $line->qty * $line->unit_factor;
                    $totalCost += ($baseQty * $line->cost_iqd_snapshot);
                }

                if ($totalCost > 0) {
                    $cogsAccount = Account::where('account_code', '5101')->first();
                    $inventoryAccount = Account::where('account_code', '1301')->first();

                    if ($cogsAccount && $inventoryAccount) {
                        // Dr COGS
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $cogsAccount->id,
                            'debit_amount' => $totalCost,
                            'credit_amount' => 0,
                        ]);
                        // Cr Inventory Asset
                        JournalEntryLine::create([
                            'journal_entry_id' => $journal->id,
                            'account_id' => $inventoryAccount->id,
                            'debit_amount' => 0,
                            'credit_amount' => $totalCost,
                        ]);
                    }
                }

                $invoice->journal_entry_id = $journal->id;
                $invoice->saveQuietly();
            });
        }
    }
}
