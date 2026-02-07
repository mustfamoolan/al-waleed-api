<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoiceRequest;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        // Pagination + filters can be added later
        $invoices = PurchaseInvoice::with(['supplier', 'creator'])
            ->orderBy('id', 'desc')
            ->paginate(20);
        return response()->json($invoices);
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        return response()->json($purchaseInvoice->load(['lines.product', 'lines.unit', 'supplier', 'creator', 'approvedBy', 'journalEntry']));
    }

    public function store(StorePurchaseInvoiceRequest $request)
    {
        $invoice = DB::transaction(function () use ($request) {
            $currency = $request->currency;
            $exchangeRate = $request->exchange_rate;
            $driverFee = $request->driver_fee ?? 0;
            $workerFee = $request->worker_fee ?? 0;
            $totalFees = $driverFee + $workerFee;

            $subtotalForeign = 0;
            $totalCartons = 0;

            // 1. Calculate Subtotal and Total Cartons
            foreach ($request->lines as $lineData) {
                $qty = $lineData['qty'];
                $price = $lineData['price_foreign'];
                $isFree = $lineData['is_free'] ?? false;
                $unitFactor = $lineData['unit_factor'] ?? 1;

                if (!$isFree) {
                    $subtotalForeign += ($qty * $price);
                }

                // Count cartons for cost distribution
                // If unit_factor > 1, it's a carton. If unit_factor = 1, it's a piece
                if ($unitFactor > 1) {
                    $totalCartons += $qty; // qty is in cartons
                } else {
                    // If selling by piece, we need units_per_pack from product
                    // For now, assume if unit_factor = 1, we don't count it as carton
                    // This logic may need refinement based on your business rules
                }
            }

            $discountForeign = $request->discount_foreign ?? 0;
            $totalForeign = max(0, $subtotalForeign - $discountForeign);
            $totalIqd = $totalForeign * $exchangeRate;

            // Calculate cost per carton
            $costPerCarton = $totalCartons > 0 ? $totalFees / $totalCartons : 0;

            $invoice = PurchaseInvoice::create([
                'invoice_no' => 'PI-' . time(),
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id' => $request->supplier_id,
                'invoice_date' => $request->invoice_date,
                'warehouse_id' => $request->warehouse_id,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'driver_fee' => $driverFee,
                'worker_fee' => $workerFee,
                'total_fees' => $totalFees,
                'subtotal_foreign' => $subtotalForeign,
                'discount_foreign' => $discountForeign,
                'total_foreign' => $totalForeign,
                'total_iqd' => $totalIqd,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->lines as $lineData) {
                $qty = $lineData['qty'];
                $price = $lineData['price_foreign'];
                $isFree = $lineData['is_free'] ?? false;
                $unitFactor = $lineData['unit_factor'] ?? 1;

                $lineTotalForeign = $isFree ? 0 : ($qty * $price);
                $lineTotalIqd = $lineTotalForeign * $exchangeRate;

                // Calculate cost per unit
                $costPerUnit = 0;
                if ($unitFactor > 1 && $totalCartons > 0) {
                    // This is a carton purchase
                    // Cost per unit = (cost per carton) / (units per carton)
                    $costPerUnit = $costPerCarton / $unitFactor;
                }

                // Price after cost (in IQD)
                $priceIqd = $price * $exchangeRate;
                $priceAfterCost = $priceIqd + $costPerUnit;

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $lineData['product_id'],
                    'qty' => $qty,
                    'unit_id' => $lineData['unit_id'],
                    'unit_factor' => $unitFactor,
                    'price_foreign' => $price,
                    'line_total_foreign' => $lineTotalForeign,
                    'line_total_iqd' => $lineTotalIqd,
                    'cost_per_unit' => $costPerUnit,
                    'price_after_cost' => $priceAfterCost,
                    'is_free' => $isFree,
                ]);
            }

            return $invoice;
        });

        return response()->json(['message' => 'تم إنشاء مسودة الفاتورة', 'invoice' => $invoice->load('lines')], 201);
    }

    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice)
    {
        // Only draft can be updated (middleware/request check handles authorization)

        $invoice = DB::transaction(function () use ($request, $purchaseInvoice) {
            $currency = $request->currency;
            $exchangeRate = $request->exchange_rate;
            $driverFee = $request->driver_fee ?? 0;
            $workerFee = $request->worker_fee ?? 0;
            $totalFees = $driverFee + $workerFee;

            $subtotalForeign = 0;
            $totalCartons = 0;

            // Delete old lines
            $purchaseInvoice->lines()->delete();

            // Calculate subtotal and total cartons
            foreach ($request->lines as $lineData) {
                $qty = $lineData['qty'];
                $price = $lineData['price_foreign'];
                $isFree = $lineData['is_free'] ?? false;
                $unitFactor = $lineData['unit_factor'] ?? 1;

                if (!$isFree) {
                    $subtotalForeign += ($qty * $price);
                }

                if ($unitFactor > 1) {
                    $totalCartons += $qty;
                }
            }

            $discountForeign = $request->discount_foreign ?? 0;
            $totalForeign = max(0, $subtotalForeign - $discountForeign);
            $totalIqd = $totalForeign * $exchangeRate;

            $costPerCarton = $totalCartons > 0 ? $totalFees / $totalCartons : 0;

            foreach ($request->lines as $lineData) {
                $qty = $lineData['qty'];
                $price = $lineData['price_foreign'];
                $isFree = $lineData['is_free'] ?? false;
                $unitFactor = $lineData['unit_factor'] ?? 1;

                $lineTotalForeign = $isFree ? 0 : ($qty * $price);
                $lineTotalIqd = $lineTotalForeign * $exchangeRate;

                // Calculate cost per unit
                $costPerUnit = 0;
                if ($unitFactor > 1 && $totalCartons > 0) {
                    $costPerUnit = $costPerCarton / $unitFactor;
                }

                $priceIqd = $price * $exchangeRate;
                $priceAfterCost = $priceIqd + $costPerUnit;

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'product_id' => $lineData['product_id'],
                    'qty' => $qty,
                    'unit_id' => $lineData['unit_id'],
                    'unit_factor' => $unitFactor,
                    'price_foreign' => $price,
                    'line_total_foreign' => $lineTotalForeign,
                    'line_total_iqd' => $lineTotalIqd,
                    'cost_per_unit' => $costPerUnit,
                    'price_after_cost' => $priceAfterCost,
                    'is_free' => $isFree,
                ]);
            }

            $purchaseInvoice->update([
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id' => $request->supplier_id,
                'invoice_date' => $request->invoice_date,
                'warehouse_id' => $request->warehouse_id,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'driver_fee' => $driverFee,
                'worker_fee' => $workerFee,
                'total_fees' => $totalFees,
                'subtotal_foreign' => $subtotalForeign,
                'discount_foreign' => $discountForeign,
                'total_foreign' => $totalForeign,
                'total_iqd' => $totalIqd,
                'notes' => $request->notes,
            ]);

            return $purchaseInvoice;
        });

        return response()->json(['message' => 'تم تحديث الفاتورة', 'invoice' => $invoice->load('lines')]);
    }

    public function approve(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return response()->json(['message' => 'الفاتورة ليست مسودة'], 400);
        }

        $purchaseInvoice->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'تمت الموافقة على الفاتورة', 'invoice' => $purchaseInvoice]);
    }

    public function post(PurchaseInvoice $purchaseInvoice)
    {
        // Allow posting from approved OR draft directly if policy allows. 
        // Assuming strict flow Draft -> Approved -> Posted, or Draft -> Posted. Let's allow both.
        if (!in_array($purchaseInvoice->status, ['draft', 'approved'])) {
            return response()->json(['message' => 'حالة الفاتورة لا تسمح بالترحيل'], 400);
        }

        // The Observer will handle the actual logic when status changes to 'posted'
        $purchaseInvoice->update([
            'status' => 'posted',
            'approved_by' => $purchaseInvoice->approved_by ?? auth()->id(), // ensure approved_by is set
        ]);

        // Reload to get journal entry ID if observer worked
        $purchaseInvoice->refresh();

        return response()->json([
            'message' => 'تم ترحيل الفاتورة بنجاح',
            'invoice' => $purchaseInvoice,
            'journal_entry_id' => $purchaseInvoice->journal_entry_id
        ]);
    }
}
