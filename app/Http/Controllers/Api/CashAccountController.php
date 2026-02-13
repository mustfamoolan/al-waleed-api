<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    /**
     * Display a listing of cash/bank accounts (Assets).
     */
    public function index()
    {
        // Filter for specific account types or codes related to Cash/Bank
        // Usually these are under "Current Assets". 
        // For simplicity, we might look for 'type' column if it exists, or description, or range.
        // Assuming we rely on 'type' or metadata, but Account model in standard GL usually doesn't have 'type'='bank'.
        // We might need to filter by parent account (e.g., 1100).

        $accounts = Account::where(function ($q) {
            $q->where('account_code', 'like', '110%'); // Cash & Banks range
        })->get();

        // Calculate balance for each
        $accounts->transform(function ($account) {
            $debit = $account->journalEntryLines()->sum('debit_amount');
            $credit = $account->journalEntryLines()->sum('credit_amount');
            $account->balance = $debit - $credit;

            // Infer type
            $account->type = str_contains($account->name, 'مصرف') || str_contains($account->name, 'Bank') ? 'bank' : 'safe';

            return $account;
        });

        return response()->json($accounts);
    }

    /**
     * Store a new cash/bank account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:safe,bank',
        ]);

        // Determine parent code
        // 1101 = Main Cash (Safe)
        // 1102 = Bank
        $parentCode = $request->type === 'bank' ? '1102' : '1101';

        // Find parent to get ID
        $parent = Account::where('account_code', $parentCode)->first();
        if (!$parent) {
            // Fallback if defaults don't exist
            $parent = Account::where('account_code', '1100')->first();
        }

        // Generate next code
        $lastChild = Account::where('parent_id', $parent?->id)->orderByDesc('account_code')->first();
        $nextCode = $lastChild ? ($lastChild->account_code + 1) : ($parentCode . '01');

        $account = Account::create([
            'name' => $request->name,
            'account_code' => $nextCode,
            'parent_id' => $parent?->id,
            'type' => 'asset', // General GL type
            'is_postable' => true,
            'is_active' => true,
            'created_by' => auth()->id()
        ]);

        return response()->json($account, 201);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $cashAccount)
    {
        $request->validate([
            'name' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $cashAccount->update([
            'name' => $request->name,
            'is_active' => $request->is_active ?? $cashAccount->is_active
        ]);

        return response()->json($cashAccount);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Account $cashAccount)
    {
        $cashAccount->update(['is_active' => !$cashAccount->is_active]);
        return response()->json(['message' => 'Changed status']);
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $cashAccount)
    {
        // Check for transactions
        if ($cashAccount->journalEntryLines()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف حساب لديه حركات مالية'], 422);
        }

        $cashAccount->delete();
        return response()->json(['message' => 'تم حذف الحساب']);
    }
}
