<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Models\Account;

class SupplierObserver
{
    public function creating(Supplier $supplier)
    {
        if (!$supplier->account_id) {
            // Get parent account for suppliers (Trade Payables - 2101)
            $parentAccount = Account::where('account_code', '2101')->first();

            if ($parentAccount) {
                // Find next sequence for child account
                $lastChild = Account::where('parent_id', $parentAccount->id)->orderBy('account_code', 'desc')->first();
                $newCode = $lastChild
                    ? (int) $lastChild->account_code + 1
                    : $parentAccount->account_code . '0001';

                $account = Account::create([
                    'name' => 'مورد: ' . $supplier->name,
                    'account_code' => $newCode,
                    'parent_id' => $parentAccount->id,
                    'type' => 'liability',
                    'is_selectable' => true,
                    'opening_balance' => $supplier->opening_balance ?? 0,
                    'currency' => $supplier->currency ?? 'IQD',
                ]);

                $supplier->account_id = $account->id;
            }
        }
    }
}
