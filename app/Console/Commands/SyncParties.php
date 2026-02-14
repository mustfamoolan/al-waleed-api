<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Party;

class SyncParties extends Command
{
    protected $signature = 'app:sync-parties';
    protected $description = 'Sync all customers and staff to the unified parties table';

    public function handle()
    {
        $this->info('Syncing Customers...');
        foreach (Customer::all() as $customer) {
            Party::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'party_type' => 'customer',
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ]
            );
        }

        $this->info('Syncing Staff...');
        foreach (Staff::all() as $staff) {
            Party::updateOrCreate(
                ['staff_id' => $staff->id],
                [
                    'party_type' => $staff->staff_type ?? 'employee',
                    'name' => $staff->name,
                    'phone' => $staff->phone,
                ]
            );
        }

        $this->info('Syncing Agents...');
        foreach (\App\Models\SalesAgent::all() as $agent) {
            Party::updateOrCreate(
                ['agent_id' => $agent->id],
                [
                    'party_type' => 'agent',
                    'name' => $agent->name,
                    'phone' => $agent->phone,
                ]
            );
        }

        $this->info('Sync complete!');
    }
}
