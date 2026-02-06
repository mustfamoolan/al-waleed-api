<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class PayrollRunObserver
{
    public function updated(PayrollRun $run)
    {
        if ($run->isDirty('status') && $run->status === 'posted') {
            DB::transaction(function () use ($run) {
                // Generate Journal Entry
                $journal = JournalEntry::create([
                    'entry_date' => now(),
                    'reference_type' => 'payroll_run',
                    'reference_id' => $run->id,
                    'description' => 'Payroll Run ' . $run->period_month,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                // 1. Calculate Totals
                $totalSalaryExpense = 0;
                $totalCommissionExpense = 0;
                $totalNetPayable = 0;
                // $totalAdvanceRepayment = 0; // If tracked separately to Credit Advances Account

                foreach ($run->lines as $line) {
                    $totalSalaryExpense += ($line->base_salary_iqd - $line->attendance_deduction_iqd + $line->adjustments_plus_iqd - $line->adjustments_minus_iqd);
                    // Note: Logic simplification. Real logic might separate Base from Bonus from Deduction.
                    // For now: Dr Salary Expense (Net of adj) + Dr Commission Expense 
                    // Let's refine: 
                    // Dr Salary Expense = Base + Allowances
                    // Cr Salaries Payable = Net
                    // Dr/Cr diff = Deductions + Advances + Attendance

                    // Simple Approach requested:
                    // Dr Salary Expense = Base + Allowances
                    // Dr Comm Expense = Comm + Bonus
                    // Cr Salaries Payable = Net + Deductions (wait, deductions reduce expense or payable?)
                    // Deductions reduce Payable. They CREDIT the deduction bucket or REDUCE Expense.

                    // Let's stick to SIMPLE logic:
                    // Dr Expenses (Total Earnings)
                    // Cr Payable (Net Pay)
                    // Cr Deductions/Advances (Recoveries)

                    $earnings = $line->base_salary_iqd + $line->adjustments_plus_iqd;
                    $commissions = $line->commissions_iqd;
                    $deductions = $line->attendance_deduction_iqd + $line->adjustments_minus_iqd;

                    $totalSalaryExpense += $earnings;
                    $totalCommissionExpense += $commissions;
                    $totalNetPayable += $line->net_salary_iqd;

                    // Difference check: ($earnings + $commissions) - $deductions == $net_salary_iqd
                }

                // Dr Salaries Expense (5101)
                $salaryAccount = Account::where('account_code', '5101')->first();
                if ($totalSalaryExpense > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $salaryAccount->id,
                        'debit_amount' => $totalSalaryExpense,
                        'credit_amount' => 0,
                    ]);
                }

                // Dr Commission Expense (5103 or similar)
                $commAccount = Account::where('account_code', '5103')->first(); // Assuming code
                if (!$commAccount)
                    $commAccount = $salaryAccount; // Fallback

                if ($totalCommissionExpense > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $commAccount->id,
                        'debit_amount' => $totalCommissionExpense,
                        'credit_amount' => 0,
                    ]);
                }

                // Cr Salaries Payable (2201)
                $payableAccount = Account::where('account_code', '2201')->first();
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $payableAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalNetPayable,
                ]);

                // Cr Other Deductions? (If we want to balance exactly)
                // Balance check: Dr (Exp + Comm) - Cr (Payable) = Deductions
                $difference = ($totalSalaryExpense + $totalCommissionExpense) - $totalNetPayable;

                if ($difference > 0) {
                    // Credit this to "Other Deductions" or "Advances" (1202) if predominantly advances
                    // Or reduce Salary Expense? No, usually separate.
                    // Let's use 1202 Advances for now assuming most deductions are advance repayments.
                    $advAccount = Account::where('account_code', '1202')->first();
                    if (!$advAccount)
                        $advAccount = $salaryAccount; // Error fallback

                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $advAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $difference,
                    ]);
                }

                $run->journal_entry_id = $journal->id;
                $run->saveQuietly();
            });
        }
    }
}
