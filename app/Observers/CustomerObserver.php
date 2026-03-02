<?php
namespace App\Observers;

use App\Models\Customer;
use App\Models\Party;

class CustomerObserver
{
    public function creating(Customer $customer)
    {
        if (!$customer->account_id) {
            // Get parent account for customers (Trade Receivables - 1201)
            $parentAccount = \App\Models\Account::where('account_code', '1201')->first();

            if ($parentAccount) {
                // Find next sequence for child account
                $lastChild = \App\Models\Account::where('parent_id', $parentAccount->id)->orderBy('account_code', 'desc')->first();
                $newCode = $lastChild
                    ? (int) $lastChild->account_code + 1
                    : $parentAccount->account_code . '0001';

                $account = \App\Models\Account::create([
                    'name' => 'زبون: ' . $customer->name,
                    'account_code' => $newCode,
                    'parent_id' => $parentAccount->id,
                    'type' => 'asset',
                    'is_selectable' => true,
                    'opening_balance' => 0,
                    'currency' => 'IQD',
                ]);

                $customer->account_id = $account->id;
            }
        }
    }

    public function created(Customer $customer)
    {
        Party::create([
            'party_type' => 'customer',
            'name' => $customer->name,
            'phone' => $customer->phone,
            'customer_id' => $customer->id,
        ]);
    }

    public function updated(Customer $customer)
    {
        Party::updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'party_type' => 'customer',
                'name' => $customer->name,
                'phone' => $customer->phone,
            ]
        );
    }

    public function deleted(Customer $customer)
    {
        Party::where('customer_id', $customer->id)->delete();
    }
}
