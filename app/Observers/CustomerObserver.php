<?php
namespace App\Observers;

use App\Models\Customer;
use App\Models\Party;

class CustomerObserver
{
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
