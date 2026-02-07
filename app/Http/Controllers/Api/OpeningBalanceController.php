<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OpeningBalanceController extends Controller
{
    /**
     * Check if opening balances have already been posted.
     */
    public function status()
    {
        $hasOpeningEntry = JournalEntry::where('reference_type', 'opening_balance')->exists();
        return response()->json([
            'is_initialized' => $hasOpeningEntry
        ]);
    }

    /**
     * Delete existing opening balances (for re-initialization).
     */
    public function destroy()
    {
        $entry = JournalEntry::where('reference_type', 'opening_balance')->first();

        if (!$entry) {
            return response()->json(['message' => 'لا يوجد رصيد افتتاحي لحذفه.'], 404);
        }

        // Delete related inventory transactions
        $invTrans = InventoryTransaction::where('reference_type', 'opening_balance')
            ->where('reference_id', $entry->id)
            ->first();

        if ($invTrans) {
            InventoryTransactionLine::where('inventory_transaction_id', $invTrans->id)->delete();
            $invTrans->delete();
        }

        // Delete journal entry lines
        JournalEntryLine::where('journal_entry_id', $entry->id)->delete();

        // Delete journal entry
        $entry->delete();

        return response()->json(['message' => 'تم حذف الرصيد الافتتاحي بنجاح.']);
    }

    /**
     * Handle the submission of opening balances.
     */
    public function store(Request $request)
    {
        // Prevent multiple initializations
        if (JournalEntry::where('reference_type', 'opening_balance')->exists()) {
            return response()->json(['message' => 'الرصيد الافتتاحي مسجل مسبقاً ولا يمكن تكراره.'], 400);
        }

        $request->validate([
            'date' => 'required|date',
            'opening_cash' => 'nullable|numeric|min:0',
            'supplier_balances' => 'nullable|array', // [{supplier_id, amount}]
            'customer_balances' => 'nullable|array', // [{customer_id, amount}]
            'inventory_items' => 'nullable|array', // [{product_id, warehouse_id, quantity, unit_cost}]
            'capital' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            $user_id = Auth::id();
            $date = $request->date;

            // 1. Create a single Journal Entry for everything
            $entry = JournalEntry::create([
                'entry_date' => $date,
                'reference_type' => 'opening_balance',
                'description' => 'القيد الافتتاحي للنظام',
                'status' => 'posted',
                'created_by' => $user_id,
                'approved_by' => $user_id,
            ]);

            // Track Inventory total to add to Journal Entry
            $inventory_total = 0;

            // 2. Prepare Inventory Transaction if items exist
            if (!empty($request->inventory_items)) {
                $invTrans = InventoryTransaction::create([
                    'trans_date' => $date,
                    'trans_type' => 'opening_balance',
                    'warehouse_id' => $request->inventory_items[0]['warehouse_id'], // Primary warehouse
                    'reference_type' => 'opening_balance',
                    'reference_id' => $entry->id,
                    'created_by' => $user_id,
                    'note' => 'الأرصدة الافتتاحية للمخزون',
                ]);

                foreach ($request->inventory_items as $item) {
                    $line_total = $item['quantity'] * $item['unit_cost'];
                    $inventory_total += $line_total;

                    InventoryTransactionLine::create([
                        'inventory_transaction_id' => $invTrans->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $line_total,
                    ]);
                }

                // Add Inventory line to GL (Debit Assets)
                // Use a default 'Inventory' GL account. 
                // In a real scenario, this would be mapped per product category.
                // For MVP, we'll look for an Asset account named 'Inventory'.
                $invAccount = Account::where('name', 'LIKE', '%مخزن%')
                    ->orWhere('name', 'LIKE', '%Inventory%')
                    ->first();

                if ($invAccount) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $invAccount->id,
                        'debit_amount' => $inventory_total,
                        'credit_amount' => 0,
                    ]);
                }
            }

            // 3. Process Cash Balance (Hardcoded to 1101)
            $opening_cash = $request->opening_cash ?? 0;
            if ($opening_cash > 0) {
                $cashAccount = Account::where('account_code', '1101')->first();
                if ($cashAccount) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $cashAccount->id,
                        'debit_amount' => $opening_cash,
                        'credit_amount' => 0,
                    ]);
                }
            }

            // 4. Process Customer Balances (Debit Assets)
            foreach ($request->customer_balances as $cust) {
                // Here we need the GL account associated with this customer
                $acc = \App\Models\Account::where('reference_type', 'customer')
                    ->where('reference_id', $cust['customer_id'])
                    ->first();
                if ($acc) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $acc->id,
                        'debit_amount' => $cust['amount'],
                        'credit_amount' => 0,
                    ]);
                }
            }

            // 5. Process Supplier Balances (Credit Liabilities)
            foreach ($request->supplier_balances as $supp) {
                $acc = \App\Models\Account::where('reference_type', 'supplier')
                    ->where('reference_id', $supp['supplier_id'])
                    ->first();
                if ($acc) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $acc->id,
                        'debit_amount' => 0,
                        'credit_amount' => $supp['amount'],
                    ]);
                }
            }

            // 6. Capital (3101)
            $capitalAccount = Account::where('account_code', '3101')->first();
            if ($capitalAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $capitalAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $request->capital,
                ]);
            }

            // Final Validation: Entry must be balanced
            if (!$entry->isBalanced()) {
                DB::rollBack();
                return response()->json(['message' => 'القيد المحاسبي غير متوازن. يرجى مراجعة المبالغ ورأس المال.'], 422);
            }

            return response()->json([
                'message' => 'تم حفظ الأرصدة الافتتاحية بنجاح',
                'entry_id' => $entry->id
            ]);
        });
    }
}
