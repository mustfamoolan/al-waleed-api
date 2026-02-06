<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

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

    public function profitSummary(Request $request)
    {
        return response()->json(
            $this->reportService->getProfitSummary($request->date_from, $request->date_to)
        );
    }

    public function cashMovements(Request $request)
    {
        $request->validate(['cash_account_id' => 'required|exists:cash_accounts,id']);
        return response()->json(
            $this->reportService->getCashMovements($request->cash_account_id, $request->date_from, $request->date_to)
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
        $request->validate(['customer_id' => 'required']);
        return response()->json($this->reportService->getCustomerPurchases($request->customer_id, $request->date_from, $request->date_to));
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
}
