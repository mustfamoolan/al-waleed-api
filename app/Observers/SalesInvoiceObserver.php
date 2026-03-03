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
    public function saving(SalesInvoice $invoice)
    {
        $invoice->total_weight_kg = $invoice->calculateTotalWeight();
    }

    public function updated(SalesInvoice $invoice)
    {
        // Handle Status: PREPARED (Stock Deduction)
        if ($invoice->isDirty('status') && $invoice->status === 'prepared') {
            DB::transaction(function () use ($invoice) {

                // Resolve default warehouse (prefer invoice's, fallback to first active)
                $warehouseId = $invoice->warehouse_id
                    ?? \App\Models\Warehouse::where('is_active', true)->value('id')
                    ?? \App\Models\Warehouse::value('id')
                    ?? 1;

                // 1. Inventory Transaction (OUT)
                $transaction = InventoryTransaction::create([
                    'trans_date' => now(), // Prepared Date
                    'trans_type' => 'sale', // OUT
                    'warehouse_id' => $warehouseId,
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'created_by' => auth()->id(),
                    'note' => 'Sales Order Prepared #' . $invoice->invoice_no,
                ]);

                foreach ($invoice->lines as $line) {
                    $baseQty = $line->qty * $line->unit_factor;

                    // Get Current Cost from Inventory Balance
                    $balance = InventoryBalance::firstOrNew([
                        'warehouse_id' => $warehouseId,
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

                // 1. Credit side (Revenue) - always the full amount
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $revenueAccount->id, // 4101 - Sales Revenue
                    'debit_amount' => 0,
                    'credit_amount' => $invoice->total_iqd,
                    'description' => 'إيرادات مبيعات رقم ' . $invoice->invoice_no,
                ]);

                // 2. Debit side (Customer AR) - always the full amount (Accrual Basis)
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $customerAccount->id, // 1201 - AR
                    'partner_type' => 'customer',
                    'partner_id' => $invoice->customer_id,
                    'debit_amount' => $invoice->total_iqd,
                    'credit_amount' => 0,
                    'description' => 'قيمة الفاتورة',
                ]);

                // 3. Handle Immediate Payment (If any)
                if ($invoice->paid_iqd > 0) {
                    // Dr Cash (Receive Money)
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $cashAccount->id, // 1101 - Cash
                        'debit_amount' => $invoice->paid_iqd,
                        'credit_amount' => 0,
                        'description' => 'دفعة نقدية فورية',
                    ]);

                    // Cr Customer AR (Reduce Debt)
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $customerAccount->id, // 1201 - AR
                        'partner_type' => 'customer',
                        'partner_id' => $invoice->customer_id,
                        'debit_amount' => 0,
                        'credit_amount' => $invoice->paid_iqd,
                        'description' => 'تسديد فوري',
                    ]);
                }

                // 4. Update Customer Statistics
                if ($invoice->customer) {
                    $invoice->customer->increment('total_debt', $invoice->total_iqd);
                    if ($invoice->paid_iqd > 0) {
                        $invoice->customer->increment('total_paid', $invoice->paid_iqd);
                    }
                }

                // 5. Calculate and Post Agent Commission
                if ($invoice->agent_id && $invoice->agent && $invoice->agent->commission_rate > 0) {
                    $commissionAmount = ($invoice->total_iqd * $invoice->agent->commission_rate) / 100;

                    if ($commissionAmount > 0) {
                        $commissionAccount = Account::where('account_code', '5103')->first(); // Commission Expense
                        $agentAccount = Account::find($invoice->agent->account_id);

                        if ($commissionAccount && $agentAccount) {
                            // Dr Commission Expense
                            JournalEntryLine::create([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $commissionAccount->id,
                                'debit_amount' => $commissionAmount,
                                'credit_amount' => 0,
                                'description' => 'عمولة مبيعات فاتورة رقم ' . $invoice->invoice_no,
                            ]);

                            // Cr Agent Account (Payable)
                            JournalEntryLine::create([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $agentAccount->id,
                                'partner_type' => 'agent',
                                'partner_id' => $invoice->agent_id,
                                'debit_amount' => 0,
                                'credit_amount' => $commissionAmount,
                                'description' => 'استحقاق عمولة مبيعات',
                            ]);
                        }
                    }
                }

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
