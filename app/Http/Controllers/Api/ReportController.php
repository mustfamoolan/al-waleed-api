<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Account;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function customerStatement(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        return response()->json(
            $this->reportService->getCustomerStatement($request->customer_id, $request->date_from, $request->date_to)
        );
    }

    public function supplierStatement(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        return response()->json(
            $this->reportService->getSupplierStatement($request->supplier_id, $request->date_from, $request->date_to)
        );
    }

    public function supplierAnalytics(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        return response()->json(
            $this->reportService->getSupplierAnalytics($request->supplier_id, $request->date_from, $request->date_to)
        );
    }

    public function supplierPurchases(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        return response()->json(
            $this->reportService->getSupplierPurchases($request->supplier_id, $request->date_from, $request->date_to)
        );
    }

    public function profitSummary(Request $request)
    {
        return response()->json(
            $this->reportService->getProfitSummary($request->date_from, $request->date_to)
        );
    }

    public function cashMovements(Request $request)
    {
        return response()->json(
            $this->reportService->getCashMovements($request->date_from, $request->date_to)
        );
    }

    public function productMovement(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        return response()->json(
            $this->reportService->getProductMovement($request->product_id, $request->warehouse_id, $request->date_from, $request->date_to)
        );
    }

    public function customerPurchases(Request $request)
    {
        if ($request->customer_id) {
            return response()->json($this->reportService->getCustomerPurchases($request->customer_id, $request->date_from, $request->date_to));
        }
        return response()->json($this->reportService->getAllCustomersPurchases($request->date_from, $request->date_to));
    }

    public function debtsSummary(Request $request)
    {
        return response()->json($this->reportService->getDebtsSummary($request->date_as_of));
    }

    public function productProfit(Request $request)
    {
        return response()->json($this->reportService->getProductProfit($request->product_id, $request->date_from, $request->date_to));
    }

    public function topProducts(Request $request)
    {
        return response()->json($this->reportService->getProductPerformance($request->date_from, $request->date_to, 'desc', $request->limit ?? 10));
    }

    public function lowProducts(Request $request)
    {
        return response()->json($this->reportService->getProductPerformance($request->date_from, $request->date_to, 'asc', $request->limit ?? 10));
    }

    public function staffFinancials(Request $request)
    {
        $request->validate(['staff_id' => 'required']);
        return response()->json($this->reportService->getStaffFinancials($request->staff_id, $request->date_from, $request->date_to));
    }

    public function agentPerformance(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'period_month' => 'required'
        ]);
        return response()->json(
            $this->reportService->getAgentPerformance($request->staff_id, $request->period_month)
        );
    }

    public function customerProfit(Request $request)
    {
        $request->validate(['customer_id' => 'required']);
        return response()->json($this->reportService->getCustomerProfit($request->customer_id, $request->date_from, $request->date_to));
    }

    public function customerAnalytics(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $customerId = $request->customer_id;

        // 1. Get all delivered invoices for this customer
        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->with('lines.product')
            ->get();

        $totalPurchases = $invoices->sum('total_iqd');
        $totalPaid = $invoices->sum('paid_iqd');
        $totalRemaining = $invoices->sum('remaining_iqd');

        // 2. Calculate total profit
        $totalProfit = 0;
        foreach ($invoices as $invoice) {
            foreach ($invoice->lines as $line) {
                $baseQty = $line->qty * $line->unit_factor;
                $totalProfit += ($line->line_total_iqd - ($line->cost_iqd_snapshot * $baseQty));
            }
        }

        // 3. Get top products by revenue
        $productStats = DB::table('sales_invoice_lines as sil')
            ->join('sales_invoices as si', 'sil.sales_invoice_id', '=', 'si.id')
            ->join('products as p', 'sil.product_id', '=', 'p.id')
            ->where('si.customer_id', $customerId)
            ->where('si.status', 'delivered')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                DB::raw('SUM(sil.qty * sil.unit_factor) as total_qty'),
                DB::raw('SUM(sil.line_total_iqd) as total_revenue'),
                DB::raw('SUM(sil.line_total_iqd - (sil.cost_iqd_snapshot * sil.qty * sil.unit_factor)) as total_profit'),
                DB::raw('COUNT(DISTINCT si.id) as invoice_count')
            )
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return response()->json([
            'customer_id' => $customerId,
            'total_purchases' => $totalPurchases,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'total_profit' => $totalProfit,
            'invoice_count' => $invoices->count(),
            'top_products' => $productStats
        ]);
    }

    public function topProfitProducts(Request $request)
    {
        return response()->json($this->reportService->getProfitRankProducts($request->date_from, $request->date_to, 'desc', $request->limit ?? 10));
    }

    public function lowProfitProducts(Request $request)
    {
        return response()->json($this->reportService->getProfitRankProducts($request->date_from, $request->date_to, 'asc', $request->limit ?? 10));
    }

    public function inventoryBalances(Request $request)
    {
        return response()->json($this->reportService->getInventoryBalances($request->warehouse_id));
    }

    public function aging(Request $request)
    {
        return response()->json($this->reportService->getAging($request->type ?? 'customer', $request->date_as_of));
    }

    public function staffPurchasesStatement(Request $request)
    {
        return response()->json(
            $this->reportService->getStaffPurchasesStatement($request->date_from, $request->date_to)
        );
    }

    public function generalDebts(Request $request)
    {
        return response()->json(
            $this->reportService->getGeneralDebts($request->date_as_of)
        );
    }
}
