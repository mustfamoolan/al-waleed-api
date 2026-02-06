<?php

namespace App\Services;

use App\Models\AgentCommissionSummary;
use App\Models\AgentTarget;
use App\Models\AgentTargetResult;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Illuminate\Support\Facades\DB;

class TargetService
{
    public function calculate(string $periodMonth, int $staffId = null)
    {
        // Filter Invoices: Status = 'delivered' AND month matches
        // Also Filter where driver_staff_id, prepared_by_staff_id matches IF we are calculating for them.
        // Usually targets are for SALES AGENTS. 
        // Need to know link between Invoice and Agent.
        // Invoice 'source_user_id' can be linked to Agent Staff?
        // Or if 'party_id' is the Agent (in case of van sales).
        // Let's assume Invoice `source_user_id` -> user -> staff(agent). 
        // AND Invoice `source_type` = 'agent'.

        $start = $periodMonth . '-01';
        $end = date("Y-m-t", strtotime($start));

        $query = AgentTarget::where('period_month', $periodMonth)->where('is_active', true);
        if ($staffId)
            $query->where('staff_id', $staffId);

        $targets = $query->with('items')->get();

        $summaryData = []; // staff_id -> total_bonus

        foreach ($targets as $target) {
            $staff = $target->staff_id; // Need to find invoices for this staff

            // Get Invoices for this Agent
            // Assuming source_user_id is the key. Find User ID for this Staff.
            $staffModel = \App\Models\Staff::find($staff);
            $userId = $staffModel->user_id;

            if (!$userId)
                continue;

            $invoices = SalesInvoice::where('source_user_id', $userId)
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->pluck('id');

            if ($invoices->isEmpty())
                continue;

            $achievedQty = 0;

            // Logic per Type
            if ($target->target_type === 'product') {
                // Single product in items? Or just logic? Model structure implies items.
                $productIds = $target->items->pluck('product_id')->toArray();
                $achievedQty = SalesInvoiceLine::whereIn('sales_invoice_id', $invoices)
                    ->whereIn('product_id', $productIds)
                    ->sum('qty');
            } elseif ($target->target_type === 'category') {
                $categoryIds = $target->items->pluck('category_id')->toArray();
                // Join needed
                $achievedQty = SalesInvoiceLine::whereIn('sales_invoice_id', $invoices)
                    ->whereHas('product', function ($q) use ($categoryIds) {
                        $q->whereIn('product_category_id', $categoryIds);
                    })->sum('qty');
            } elseif ($target->target_type === 'supplier') {
                $supplierIds = $target->items->pluck('supplier_id')->toArray();
                // Join needed
                $achievedQty = SalesInvoiceLine::whereIn('sales_invoice_id', $invoices)
                    ->whereHas('product', function ($q) use ($supplierIds) {
                        $q->whereHas('suppliers', function ($sq) use ($supplierIds) { // product_suppliers
                            $sq->whereIn('supplier_id', $supplierIds);
                        });
                    })->sum('qty');
            } elseif ($target->target_type === 'mixed_products') {
                $productIds = $target->items->pluck('product_id')->toArray();
                $achievedQty = SalesInvoiceLine::whereIn('sales_invoice_id', $invoices)
                    ->whereIn('product_id', $productIds)
                    ->sum('qty');
            }

            // Calc Result
            $percent = $target->target_qty > 0 ? ($achievedQty / $target->target_qty) * 100 : 0;
            $bonus = 0;
            if ($percent >= $target->min_achievement_percent) {
                // Reward per unit achieved? OR Reward for hitting target?
                // User said: bonus = achieved_qty * reward_per_unit_iqd
                $bonus = $achievedQty * $target->reward_per_unit_iqd;
            }

            // Store Result
            AgentTargetResult::create([
                'agent_target_id' => $target->id,
                'achieved_qty' => $achievedQty,
                'achievement_percent' => $percent,
                'bonus_iqd' => $bonus,
                'calculated_at' => now(),
            ]);

            if (!isset($summaryData[$staff]))
                $summaryData[$staff] = 0;
            $summaryData[$staff] += $bonus;
        }

        // Create/Update Summaries
        foreach ($summaryData as $staffId => $bonus) {
            AgentCommissionSummary::updateOrCreate(
                ['staff_id' => $staffId, 'period_month' => $periodMonth],
                ['targets_bonus_iqd' => $bonus, 'status' => 'calculated']
            );
        }
    }
}
