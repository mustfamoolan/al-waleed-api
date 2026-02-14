<?php

namespace App\Observers;

use App\Models\SalesAgent;
use App\Models\Party;

class SalesAgentObserver
{
    public function created(SalesAgent $agent)
    {
        Party::updateOrCreate(
            ['agent_id' => $agent->id],
            [
                'party_type' => 'agent',
                'name' => $agent->name,
                'phone' => $agent->phone,
            ]
        );
    }

    public function updated(SalesAgent $agent)
    {
        Party::updateOrCreate(
            ['agent_id' => $agent->id],
            [
                'party_type' => 'agent',
                'name' => $agent->name,
                'phone' => $agent->phone,
            ]
        );
    }
}
