<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    public function supplierProfit(Request $request, Supplier $supplier): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = PurchaseInvoice::where('supplier_id', $supplier->supplier_id)
            ->where('status', '!=', 'cancelled');

        if ($fromDate) {
            $query->where('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('invoice_date', '<=', $toDate);
        }

        $totalPurchases = $query->sum('total_amount');
        $totalReturns = PurchaseReturnInvoice::where('supplier_id', $supplier->supplier_id)
            ->where('status', '!=', 'cancelled')
            ->when($fromDate, fn($q) => $q->where('return_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('return_date', '<=', $toDate))
            ->sum('total_amount');

        // TODO: Calculate profit when sales data is available
        $profit = 0; // Placeholder

        return $this->successResponse([
            'supplier_id' => $supplier->supplier_id,
            'company_name' => $supplier->company_name,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_purchases' => $totalPurchases,
            'total_returns' => $totalReturns,
            'net_purchases' => $totalPurchases - $totalReturns,
            'profit' => $profit,
        ]);
    }

    public function purchasesSummary(Request $request, Supplier $supplier): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = PurchaseInvoice::where('supplier_id', $supplier->supplier_id)
            ->where('status', '!=', 'cancelled');

        if ($fromDate) {
            $query->where('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('invoice_date', '<=', $toDate);
        }

        $invoices = $query->get();

        return $this->successResponse([
            'supplier_id' => $supplier->supplier_id,
            'company_name' => $supplier->company_name,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'paid_amount' => $invoices->sum('paid_amount'),
            'remaining_amount' => $invoices->sum('remaining_amount'),
            'invoices' => $invoices,
        ]);
    }

    public function priceComparison(Request $request, Supplier $supplier): JsonResponse
    {
        $productName = $request->get('product_name');
        $productCode = $request->get('product_code');

        if (!$productName && !$productCode) {
            return $this->errorResponse('Product name or code is required', 422);
        }

        $query = \App\Models\PurchaseInvoiceItem::whereHas('invoice', function($q) use ($supplier) {
            $q->where('supplier_id', $supplier->supplier_id)
              ->where('status', '!=', 'cancelled');
        });

        if ($productName) {
            $query->where('product_name', 'like', "%{$productName}%");
        }
        if ($productCode) {
            $query->where('product_code', $productCode);
        }

        $items = $query->with('invoice')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'invoice_number' => $item->invoice->invoice_number,
                    'invoice_date' => $item->invoice->invoice_date,
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_code,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ];
            });

        return $this->successResponse([
            'supplier_id' => $supplier->supplier_id,
            'company_name' => $supplier->company_name,
            'product_name' => $productName,
            'product_code' => $productCode,
            'comparisons' => $items,
        ]);
    }

    public function financialSummary(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $purchasesQuery = PurchaseInvoice::where('status', '!=', 'cancelled');
        $returnsQuery = PurchaseReturnInvoice::where('status', '!=', 'cancelled');

        if ($fromDate) {
            $purchasesQuery->where('invoice_date', '>=', $fromDate);
            $returnsQuery->where('return_date', '>=', $fromDate);
        }
        if ($toDate) {
            $purchasesQuery->where('invoice_date', '<=', $toDate);
            $returnsQuery->where('return_date', '<=', $toDate);
        }

        return $this->successResponse([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_purchases' => $purchasesQuery->sum('total_amount'),
            'total_returns' => $returnsQuery->sum('total_amount'),
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('is_active', true)->count(),
        ]);
    }

    public function suppliersReport(Request $request): JsonResponse
    {
        $suppliers = Supplier::withCount([
            'purchaseInvoices as total_invoices',
            'purchaseInvoices as total_purchases' => function($q) {
                $q->select(DB::raw('COALESCE(SUM(total_amount), 0)'))
                  ->where('status', '!=', 'cancelled');
            },
            'payments as total_payments' => function($q) {
                $q->select(DB::raw('COALESCE(SUM(amount), 0)'))
                  ->where('payment_type', 'payment');
            },
        ])->get();

        return $this->successResponse($suppliers);
    }
}
