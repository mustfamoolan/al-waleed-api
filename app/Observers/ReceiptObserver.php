<?php

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

class ReceiptObserver
{
    public function updated(Receipt $receipt)
    {
        if ($receipt->isDirty('status') && $receipt->status === 'posted') {
            DB::transaction(function () use ($receipt) {
                // 1. Process Allocations (Update Invoice Remaining Amounts)
                foreach ($receipt->allocations as $allocation) {
                    $invoice = $allocation->invoice;
                    if ($invoice) {
                        $invoice->remaining_iqd -= $allocation->allocated_iqd;
                        $invoice->paid_iqd += $allocation->allocated_iqd;
                        if ($invoice->remaining_iqd <= 0) {
                            // $invoice->is_paid = true; // If column exists, typically we use remaining > 0 check
                            // If is_paid exists in DB, update it.
                            $invoice->is_paid = true;
                        }
                        $invoice->save();
                    }
                }

                // 2. Create Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => now(),
                    'reference_type' => 'receipt',
                    'reference_id' => $receipt->id,
                    'description' => 'وصل قبض رقم ' . $receipt->receipt_no . ($receipt->receipt_type === 'customer_payment' ? ' - دفع زبون' : ' - دخل عام'),
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // Debit: Cashbox (Main Cash 1101)
                $cashAccount = Account::where('account_code', '1101')->first();
                $cashGlAccount = $cashAccount->id;

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $cashGlAccount,
                    'debit_amount' => $receipt->amount_iqd,
                    'credit_amount' => 0,
                ]);

                // Credit: Depends on Receipt Type
                $creditAccountId = null;

                if ($receipt->receipt_type === 'customer_payment') {
                    // Credit Customer AR (1201 or Specific)
                    $customerAccount = Account::where('account_code', '1201')->first();
                    $creditAccountId = $receipt->customer ? ($receipt->customer->account_id ?? $customerAccount->id) : $customerAccount->id;
                } else {
                    // General Income -> Need an account?
                    // Ideally user picks Income Account for General Receipt?
                    // Or default to 'Other Income'
                    // For now, let's assume 'Other Revenue' or use 4101 General Sales if not specified.
                    // Better design: add 'income_account_id' to receipt for general_income.
                    // Fallback to 4101 for now.
                    $revenueAccount = Account::where('account_code', '4101')->first();
                    $creditAccountId = $revenueAccount->id;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $creditAccountId,
                    'partner_type' => $receipt->receipt_type === 'customer_payment' ? 'customer' : null,
                    'partner_id' => $receipt->customer_id,
                    'debit_amount' => 0,
                    'credit_amount' => $receipt->amount_iqd,
                ]);

                $receipt->journal_entry_id = $journal->id;
                $receipt->saveQuietly();
            });
        }
    }
}
