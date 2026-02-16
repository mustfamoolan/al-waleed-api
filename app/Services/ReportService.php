<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // A. Customer Statement
    public function getCustomerStatement($customerId, $from = null, $to = null)
    {
        // 1. Invoices
        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->where('status', 'delivered') // confirmed sales
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get()->map(function ($inv) {
                return [
                    'date' => $inv->created_at->format('Y-m-d'),
                    'type' => 'invoice',
                    'ref' => $inv->invoice_no,
                    'debit' => $inv->total_iqd, // Receivable increases
                    'credit' => 0,
                    'notes' => 'Sales Invoice'
                ];
            });

        // 2. Receipts
        $receipts = Receipt::where('customer_id', $customerId)
            ->where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get()->map(function ($rec) {
                return [
                    'date' => $rec->created_at->format('Y-m-d'),
                    'type' => 'receipt',
                    'ref' => $rec->receipt_no,
                    'debit' => 0,
                    'credit' => $rec->amount_iqd, // Receivable decreases
                    'notes' => 'Payment Received'
                ];
            });

        // Merge & Sort
        $transactions = $invoices->merge($receipts)->sortBy('date')->values();

        // Calculate Running Balance? (Simplified)
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $balance = $totalDebit - $totalCredit;

        return [
            'transactions' => $transactions,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $balance
        ];
    }

    // B. Supplier Statement (Similar Logic)
    public function getSupplierStatement($supplierId, $from = null, $to = null)
    {
        // PurchaseInvoices (Credit / Liability increases)
        $bills = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get()->map(function ($bill) {
                return [
                    'date' => $bill->created_at->format('Y-m-d'),
                    'type' => 'bill',
                    'ref' => $bill->invoice_no,
                    'debit' => 0,
                    'credit' => $bill->total_iqd, // Payable increases
                ];
            });

        // Payments (Debit / Liability decreases)
        $payments = Payment::where('supplier_id', $supplierId)
            ->where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get()->map(function ($pay) {
                return [
                    'date' => $pay->created_at->format('Y-m-d'),
                    'type' => 'payment',
                    'ref' => $pay->payment_no,
                    'debit' => $pay->amount_iqd,
                    'credit' => 0
                ];
            });

        $txn = $bills->merge($payments)->sortBy('date')->values();

        return [
            'transactions' => $txn,
            'total_purchased' => $txn->sum('credit'),
            'total_paid' => $txn->sum('debit'),
            'closing_balance' => $txn->sum('credit') - $txn->sum('debit')
        ];
    }

    // C. Supplier Analytics (New)
    public function getSupplierAnalytics($supplierId, $from = null, $to = null)
    {
        // 1. Top/Low Purchased Products (from this supplier)
        $productsQuery = DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_lines.purchase_invoice_id')
            ->join('products', 'products.id', '=', 'purchase_invoice_lines.product_id')
            ->where('purchase_invoices.supplier_id', $supplierId)
            ->where('purchase_invoices.status', 'posted')
            ->when($from, fn($q) => $q->whereDate('purchase_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('purchase_invoices.created_at', '<=', $to))
            ->select(
                'products.name',
                DB::raw('SUM(purchase_invoice_lines.qty) as total_qty'),
                DB::raw('SUM(purchase_invoice_lines.line_total_iqd) as total_amount')
            )
            ->groupBy('products.name', 'products.id');

        $topProducts = (clone $productsQuery)->orderByDesc('total_qty')->limit(5)->get();
        $lowProducts = (clone $productsQuery)->orderBy('total_qty')->limit(5)->get();

        // 2. Sales & Profit from products LINKED to this supplier
        // Optimization: Find product IDs purchased from this supplier first
        $productIds = DB::table('purchase_invoices')
            ->join('purchase_invoice_lines', 'purchase_invoices.id', '=', 'purchase_invoice_lines.purchase_invoice_id')
            ->where('purchase_invoices.supplier_id', $supplierId)
            ->distinct()
            ->pluck('purchase_invoice_lines.product_id');

        $salesStats = DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->whereIn('sales_invoice_lines.product_id', $productIds)
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select(
                DB::raw('SUM(sales_invoice_lines.line_total_iqd) as revenue'),
                DB::raw('SUM(sales_invoice_lines.line_total_iqd - (sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot)) as profit')
            )
            ->first();

        return [
            'top_products' => $topProducts,
            'low_products' => $lowProducts,
            'generated_revenue' => $salesStats->revenue ?? 0,
            'generated_profit' => $salesStats->profit ?? 0
        ];
    }

    // D. Supplier Detailed Purchases (Products purchased from this supplier)
    public function getSupplierPurchases($supplierId, $from = null, $to = null)
    {
        return DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_lines.purchase_invoice_id')
            ->join('products', 'products.id', '=', 'purchase_invoice_lines.product_id')
            ->where('purchase_invoices.supplier_id', $supplierId)
            ->where('purchase_invoices.status', 'posted')
            ->when($from, fn($q) => $q->whereDate('purchase_invoices.invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('purchase_invoices.invoice_date', '<=', $to))
            ->select(
                'purchase_invoices.invoice_date as date',
                'purchase_invoices.invoice_no as ref',
                'products.name as product_name',
                'purchase_invoice_lines.qty',
                'purchase_invoice_lines.price_foreign as price',
                'purchase_invoice_lines.line_total_iqd as total'
            )
            ->orderByDesc('purchase_invoices.invoice_date')
            ->orderByDesc('purchase_invoices.id')
            ->get();
    }

    // F. Profit Summary
    public function getProfitSummary($from = null, $to = null)
    {
        // 1. Sales Revenue & Cost (Gross Profit)
        // Ensure status delivered
        $salesLines = DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select(
                DB::raw('SUM(sales_invoice_lines.line_total_iqd) as revenue'),
                DB::raw('SUM(sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot) as cogs')
            )->first();

        $revenue = $salesLines->revenue ?? 0;
        $cogs = $salesLines->cogs ?? 0;
        $grossProfit = $revenue - $cogs;

        // 2. Expenses (Payments type=expense or payroll)
        $expenses = Payment::where('status', 'posted')
            ->where('payment_type', 'expense')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount_iqd');

        // 3. Payroll Expenses
        // Assuming Payroll runs are posted expenses
        $payroll = PayrollRun::where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->with('lines')
            ->get()
            ->sum(function ($run) {
                // Total Cost to company = Base + Allowances + Commissions (Deductions reduce payout but not expense usually, unless returned)
                // Simplified: Total Net Payout is "Cash Out", but Expense is Gross.
                // Let's sum net_salary_iqd + adjustments_minus (which were deducted) to get back to Gross? 
                // Actually payroll logic implemented:
                // Expense = Earnings + Commissions.
                // Let's re-calculate total expense from lines.
                $totalExp = 0;
                foreach ($run->lines as $line) {
                    $totalExp += ($line->base_salary_iqd + $line->adjustments_plus_iqd + $line->commissions_iqd);
                }
                return $totalExp;
            });

        $totalExpenses = $expenses + $payroll;
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses_operational' => $expenses,
            'expenses_payroll' => $payroll,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit
        ];
    }

    // G. Cash Movement
    public function getCashMovements($from = null, $to = null)
    {
        $in = Receipt::where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount_iqd');

        $out = Payment::where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount_iqd');

        return ['inflow' => $in, 'outflow' => $out, 'net_change' => $in - $out];
    }

    // H. Inventory Movement
    public function getProductMovement($productId, $warehouseId = null, $from = null, $to = null)
    {
        $query = InventoryTransaction::join('inventory_transaction_lines', 'inventory_transactions.id', '=', 'inventory_transaction_lines.inventory_transaction_id')
            ->leftJoin('users', 'inventory_transactions.created_by', '=', 'users.id')
            ->leftJoin('warehouses', 'inventory_transactions.warehouse_id', '=', 'warehouses.id')
            ->where('inventory_transaction_lines.product_id', $productId);

        if ($warehouseId)
            $query->where('inventory_transactions.warehouse_id', $warehouseId);
        if ($from)
            $query->whereDate('inventory_transactions.trans_date', '>=', $from);
        if ($to)
            $query->whereDate('inventory_transactions.trans_date', '<=', $to);

        return $query->select(
            'inventory_transactions.id',
            'inventory_transactions.trans_date as date',
            'inventory_transactions.trans_type',
            'inventory_transactions.reference_type',
            'inventory_transactions.reference_id',
            'inventory_transactions.note as header_note',
            'inventory_transaction_lines.qty',
            'inventory_transaction_lines.unit_factor',
            'inventory_transaction_lines.note as line_note',
            'users.name as created_by_name',
            'warehouses.name as warehouse_name'
        )
            ->orderBy('inventory_transactions.trans_date', 'asc')
            ->orderBy('inventory_transactions.id', 'asc')
            ->get();
    }

    // J. Agent Performance
    public function getAgentPerformance($agentStaffId, $periodMonth)
    {
        // Sales Total
        $user = Staff::find($agentStaffId)->user_id;
        $start = $periodMonth . '-01';
        $end = date("Y-m-t", strtotime($start));

        $sales = 0;
        if ($user) {
            $sales = SalesInvoice::where('source_user_id', $user)
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->sum('total_iqd');
        }

        // Commission info
        $comm = \App\Models\AgentCommissionSummary::where('staff_id', $agentStaffId)->where('period_month', $periodMonth)->first();

        return [
            'period' => $periodMonth,
            'total_sales_iqd' => $sales,
            'commission_earned' => $comm ? $comm->commission_iqd : 0,
            'targets_bonus' => $comm ? $comm->targets_bonus_iqd : 0,
            'total_income' => $comm ? $comm->total_due_iqd : 0
        ];
    }

    // 3. Customer Purchases
    public function getCustomerPurchases($customerId, $from = null, $to = null)
    {
        return DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_lines.product_id')
            ->where('sales_invoices.customer_id', $customerId)
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select(
                'products.name as product_name',
                DB::raw('SUM(sales_invoice_lines.qty) as total_qty'),
                DB::raw('SUM(sales_invoice_lines.line_total_iqd) as total_spent'),
                DB::raw('COUNT(sales_invoices.id) as times_purchased')
            )
            ->groupBy('products.name')
            ->orderByDesc('total_spent')
            ->get();
    }

    // 4. Staff Financials
    public function getStaffFinancials($staffId, $from = null, $to = null)
    {
        // Sales to Employee
        $sales = SalesInvoice::where('status', 'delivered')
            // Assuming we use party_id link or source_user logic. 
            // If unified staff has party_id:
            ->whereHas('party', function ($q) use ($staffId) {
                // Determine party_id from staff_id? Staff model has user_id, user has generic party?
                // For now, simpler check:
                $staff = Staff::find($staffId);
                if ($staff && $staff->user_id) {
                    $q->where('source_user_id', $staff->user_id); // If they bought it? Or just party link.
                }
            })
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get();

        // Payments (Advances)
        $payments = Payment::where('staff_id', $staffId)
            ->where('status', 'posted')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get();

        // Adjustments
        $adjustments = \App\Models\PayrollAdjustment::where('staff_id', $staffId)
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->get();

        return [
            'sales' => $sales,
            'payments' => $payments,
            'adjustments' => $adjustments
        ];
    }

    // 5. Debts Summary
    public function getDebtsSummary($dateAsOf = null)
    {
        $date = $dateAsOf ?? now();

        // Customers (Receivables)
        $customers = Customer::all()->map(function ($c) use ($date) {
            // Simplified balance calculation. In real app, optimize.
            $debit = SalesInvoice::where('customer_id', $c->id)->where('status', 'delivered')->whereDate('created_at', '<=', $date)->sum('total_iqd');
            $credit = Receipt::where('customer_id', $c->id)->where('status', 'posted')->whereDate('created_at', '<=', $date)->sum('amount_iqd');
            $balance = $debit - $credit;
            return ['id' => $c->id, 'name' => $c->name, 'balance' => $balance];
        })->filter(fn($c) => $c['balance'] > 0)->sortByDesc('balance')->values();

        // Suppliers (Payables)
        $suppliers = Supplier::all()->map(function ($s) use ($date) {
            $credit = PurchaseInvoice::where('supplier_id', $s->id)->where('status', 'posted')->whereDate('created_at', '<=', $date)->sum('total_iqd');
            $debit = Payment::where('supplier_id', $s->id)->where('status', 'posted')->whereDate('created_at', '<=', $date)->sum('amount_iqd');
            $balance = $credit - $debit;
            return ['id' => $s->id, 'name' => $s->name, 'balance' => $balance];
        })->filter(fn($s) => $s['balance'] > 0)->sortByDesc('balance')->values();

        return [
            'total_receivables' => $customers->sum('balance'),
            'total_payables' => $suppliers->sum('balance'),
            'top_debtors' => $customers->take(20),
            'top_creditors' => $suppliers->take(20)
        ];
    }

    // 9. Product Profit
    public function getProductProfit($productId = null, $from = null, $to = null)
    {
        $query = DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_lines.product_id')
            ->where('sales_invoices.status', 'delivered');

        if ($productId)
            $query->where('products.id', $productId);
        if ($from)
            $query->whereDate('sales_invoices.created_at', '>=', $from);
        if ($to)
            $query->whereDate('sales_invoices.created_at', '<=', $to);

        return $query->select(
            'products.name',
            DB::raw('SUM(sales_invoice_lines.qty) as sold_qty'),
            DB::raw('SUM(sales_invoice_lines.line_total_iqd) as revenue'),
            DB::raw('SUM(sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot) as cost'),
            DB::raw('SUM(sales_invoice_lines.line_total_iqd - (sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot)) as profit'),
            DB::raw('(SUM(sales_invoice_lines.line_total_iqd - (sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot)) / NULLIF(SUM(sales_invoice_lines.line_total_iqd),0)) * 100 as margin_percent')
        )
            ->groupBy('products.name', 'products.id')
            ->orderByDesc('profit')
            ->get();
    }

    // 10. Customer Profit
    public function getCustomerProfit($customerId, $from = null, $to = null)
    {
        return DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->where('sales_invoices.customer_id', $customerId)
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select(
                DB::raw('COUNT(DISTINCT sales_invoices.id) as invoices_count'),
                DB::raw('SUM(sales_invoice_lines.line_total_iqd) as total_revenue'),
                DB::raw('SUM(sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot) as total_cost'),
                DB::raw('SUM(sales_invoice_lines.line_total_iqd - (sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot)) as total_profit')
            )
            ->first();
    }

    // 12. Top/Low Products (Quantity)
    public function getProductPerformance($from = null, $to = null, $order = 'desc', $limit = 10)
    {
        return DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_lines.product_id')
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select('products.name', DB::raw('SUM(sales_invoice_lines.qty) as total_qty'), DB::raw('SUM(sales_invoice_lines.line_total_iqd) as revenue'))
            ->groupBy('products.name', 'products.id')
            ->orderBy('total_qty', $order)
            ->limit($limit)
            ->get();
    }

    // 13. Top/Low Profit Products
    public function getProfitRankProducts($from = null, $to = null, $order = 'desc', $limit = 10)
    {
        return DB::table('sales_invoice_lines')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_lines.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_lines.product_id')
            ->where('sales_invoices.status', 'delivered')
            ->when($from, fn($q) => $q->whereDate('sales_invoices.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sales_invoices.created_at', '<=', $to))
            ->select(
                'products.name',
                DB::raw('SUM(sales_invoice_lines.line_total_iqd - (sales_invoice_lines.qty * sales_invoice_lines.cost_iqd_snapshot)) as total_profit')
            )
            ->groupBy('products.name', 'products.id')
            ->orderBy('total_profit', $order)
            ->limit($limit)
            ->get();
    }

    // 14. Inventory Balances
    public function getInventoryBalances($warehouseId = null)
    {
        $query = \App\Models\InventoryBalance::with('product');
        if ($warehouseId)
            $query->where('warehouse_id', $warehouseId);
        // Could filter by category via product relationship if needed
        return $query->get()->map(function ($bal) {
            return [
                'product_id' => $bal->product_id,
                'warehouse_id' => $bal->warehouse_id,
                'qty_on_hand' => $bal->qty_on_hand,
                'avg_cost_iqd' => $bal->avg_cost_iqd,
                'product' => $bal->product, // Full product object
            ];
        });
    }

    // 15. Aging Report
    public function getAging($type = 'customer', $dateAsOf = null)
    {
        $date = $dateAsOf ?? now();
        // This requires complex logic matching unallocated payments to invoices.
        // For now, simpler approach: return invoices that have remaining_iqd > 0 and are due.

        if ($type === 'customer') {
            return SalesInvoice::where('status', 'delivered')
                ->where('payment_type', 'credit')
                ->where('remaining_iqd', '>', 0)
                ->whereDate('due_date', '<', $date)
                ->with('customer')
                ->get()
                ->map(function ($inv) use ($date) {
                    $daysOverdue = \Carbon\Carbon::parse($inv->due_date)->diffInDays(\Carbon\Carbon::parse($date));
                    return [
                        'partner' => $inv->customer->name ?? 'Unknown',
                        'invoice_no' => $inv->invoice_no,
                        'remaining' => $inv->remaining_iqd,
                        'due_date' => $inv->due_date,
                        'days_overdue' => $daysOverdue
                    ];
                });
        } else {
            return PurchaseInvoice::where('status', 'posted')
                ->where('payment_type', 'credit')
                ->where('remaining_iqd', '>', 0)
                ->whereDate('due_date', '<', $date)
                ->with('supplier')
                ->get()
                ->map(function ($inv) use ($date) {
                    $daysOverdue = \Carbon\Carbon::parse($inv->due_date)->diffInDays(\Carbon\Carbon::parse($date));
                    return [
                        'partner' => $inv->supplier->name ?? 'Unknown',
                        'invoice_no' => $inv->invoice_no,
                        'remaining' => $inv->remaining_iqd,
                        'due_date' => $inv->due_date,
                        'days_overdue' => $daysOverdue
                    ];
                });
        }
    }
}

