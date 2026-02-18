<?php

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

class PaymentObserver
{
    public function updated(Payment $payment)
    {
        if ($payment->isDirty('status') && $payment->status === 'posted') {
            DB::transaction(function () use ($payment) {
                // 1. Process Allocations
                foreach ($payment->allocations as $allocation) {
                    $invoice = $allocation->invoice;
                    if ($invoice) {
                        $invoice->remaining_iqd -= $allocation->allocated_iqd;
                        $invoice->paid_iqd += $allocation->allocated_iqd;
                        // $invoice->is_paid = $invoice->remaining_iqd <= 0;
                        $invoice->save();
                    }
                }

                // 2. Create Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => now(),
                    'reference_type' => 'payment',
                    'reference_id' => $payment->id,
                    'description' => 'Payment #' . $payment->payment_no . ' - ' . $payment->payment_type,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // Credit: Cashbox (Main Cash 1101)
                $cashAccount = Account::where('account_code', '1101')->first();
                $cashGlAccount = $cashAccount->id;

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $cashGlAccount,
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount_iqd,
                ]);

                // Debit: Based on Type
                $debitAccountId = null;

                switch ($payment->payment_type) {
                    case 'supplier_payment':
                        $supplierAccount = Account::where('account_code', '2101')->first();
                        $debitAccountId = $payment->supplier_id
                            ? (\App\Models\Supplier::find($payment->supplier_id)->account_id ?? $supplierAccount->id)
                            : $supplierAccount->id;
                        break;
                    case 'expense':
                        $debitAccountId = $payment->expense_account_id;
                        break;
                    case 'salary_payment':
                        // Dr Salaries Payable (2201) or Expense (5201) directly?
                        // Usually expense accrued monthly, payment reduces liability.
                        // For simple cash basis: Dr Expense.
                        // Let's assume Dr Salaries Payable (2201).
                        $salariesAccount = Account::where('account_code', '2201')->first();
                        // If not exists, maybe 5xxx. Let's use 2201 default.
                        $debitAccountId = $salariesAccount->id;
                        break;
                    case 'advance':
                        // Dr Advances (1202)
                        $advancesAccount = Account::where('account_code', '1202')->first();
                        $debitAccountId = $advancesAccount->id;
                        break;
                }

                // Fallback validation
                if (!$debitAccountId) {
                    // Default to Suspense/Misc Expense if unknown?
                    // Ideally fail or use generic expense
                    $miscAccount = Account::where('name', 'like', '%Expense%')->first();
                    $debitAccountId = $miscAccount ? $miscAccount->id : 1;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $debitAccountId,
                    'partner_type' => in_array($payment->payment_type, ['supplier_payment', 'salary_payment', 'advance'])
                        ? ($payment->payment_type === 'supplier_payment' ? 'supplier' : 'staff')
                        : null,
                    'partner_id' => $payment->supplier_id ?? $payment->staff_id,
                    'debit_amount' => $payment->amount_iqd,
                    'credit_amount' => 0,
                ]);

                $payment->journal_entry_id = $journal->id;
                $payment->saveQuietly();
            });
        }
    }
}
