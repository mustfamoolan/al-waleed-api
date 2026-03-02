<?php

namespace App\Observers;

use App\Models\SalesAgent;
use App\Models\Party;

class SalesAgentObserver
{
    public function creating(SalesAgent $agent)
    {
        if (!$agent->account_id) {
            // 1. Create Main Account (Liability - Staff Payables 2102)
            $parentLiability = \App\Models\Account::where('account_code', '2102')->first()
                ?? \App\Models\Account::where('account_code', '2101')->first();

            if ($parentLiability) {
                $lastLiability = \App\Models\Account::where('parent_id', $parentLiability->id)->orderBy('account_code', 'desc')->first();
                $newLiabilityCode = $lastLiability
                    ? (int) $lastLiability->account_code + 1
                    : $parentLiability->account_code . '0001';

                $accLiability = \App\Models\Account::create([
                    'name' => 'مندوب (استحقاق): ' . $agent->name,
                    'account_code' => $newLiabilityCode,
                    'parent_id' => $parentLiability->id,
                    'type' => 'liability',
                    'is_selectable' => true,
                    'opening_balance' => 0,
                    'currency' => 'IQD',
                ]);

                $agent->account_id = $accLiability->id;
            }

            // 2. Create Trust/Cash Account (Asset - Cash in Hand Agents 1102)
            $parentAsset = \App\Models\Account::where('account_code', '1102')->first()
                ?? \App\Models\Account::where('account_code', '1101')->first();

            if ($parentAsset) {
                $lastAsset = \App\Models\Account::where('parent_id', $parentAsset->id)->orderBy('account_code', 'desc')->first();
                $newAssetCode = $lastAsset
                    ? (int) $lastAsset->account_code + 1
                    : $parentAsset->account_code . '0001';

                \App\Models\Account::create([
                    'name' => 'مندوب (عهدة): ' . $agent->name,
                    'account_code' => $newAssetCode,
                    'parent_id' => $parentAsset->id,
                    'type' => 'asset',
                    'is_selectable' => true,
                    'opening_balance' => 0,
                    'currency' => 'IQD',
                ]);
            }
        }
    }

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
