<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesAgent;
use App\Models\SalesInvoice;
use App\Models\Receipt;
use App\Models\Customer;
use Illuminate\Http\Request;

class SalesAgentDashboardController extends Controller
{
    /**
     * Get comprehensive data for a specific agent.
     */
    public function show(Request $request, SalesAgent $agent)
    {
        // Load relationships
        $agent->load(['user', 'account']);

        // 1. Customers and their balances
        $customers = Customer::where('agent_id', $agent->id)
            ->get()
            ->map(function ($customer) {
                // Calculate balance (simplified for now, usually handled by account relationship if fully integrated)
                $debit = SalesInvoice::where('customer_id', $customer->id)->where('status', 'delivered')->sum('total_iqd');
                $credit = Receipt::where('customer_id', $customer->id)->where('status', 'posted')->sum('amount_iqd');

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'balance' => $debit - $credit,
                    'is_active' => $customer->is_active,
                ];
            });

        // 2. Recent Sales Invoices
        $recentSales = SalesInvoice::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // 3. Recent Receipts
        $recentReceipts = Receipt::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // 4. Targets Performance (Simplified call to existing report logic if possible or manual)
        // We'll return the raw data and let the frontend handle some calculation or add summary here.
        $targets = $agent->targets()->with('items')->get();

        return response()->json([
            'agent' => $agent,
            'stats' => [
                'total_customers' => $customers->count(),
                'total_active_debt' => $customers->sum('balance'),
                'total_sales_month' => SalesInvoice::where('agent_id', $agent->id)
                    ->whereMonth('created_at', now()->month)
                    ->where('status', 'delivered')
                    ->sum('total_iqd'),
                'total_collected_month' => Receipt::where('agent_id', $agent->id)
                    ->whereMonth('created_at', now()->month)
                    ->where('status', 'posted')
                    ->sum('amount_iqd'),
            ],
            'customers' => $customers,
            'sales_history' => $recentSales,
            'receipt_history' => $recentReceipts,
            'targets' => $targets,
        ]);
    }
}
