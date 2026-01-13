<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SupplierPayment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\SupplierPayment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierPaymentController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SupplierPayment::with(['supplier', 'invoice']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->get('invoice_id'));
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();
        return $this->successResponse(PaymentResource::collection($payments));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $payment = SupplierPayment::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'payment_number' => $validated['payment_number'],
                'payment_type' => $validated['payment_type'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'cheque_number' => $validated['cheque_number'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Update invoice if linked
            if ($payment->invoice_id) {
                $invoice = PurchaseInvoice::find($payment->invoice_id);
                if ($invoice) {
                    if ($payment->payment_type === 'payment') {
                        $invoice->paid_amount += $payment->amount;
                    } else {
                        $invoice->paid_amount -= $payment->amount;
                    }
                    $invoice->calculateRemaining();
                    $invoice->updateStatus();
                }
            }

            DB::commit();

            return $this->successResponse(
                new PaymentResource($payment->load(['supplier', 'invoice'])),
                'Payment created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create payment', 500);
        }
    }

    public function show(SupplierPayment $supplier_payment): JsonResponse
    {
        $supplier_payment->load(['supplier', 'invoice', 'creator']);
        return $this->successResponse(new PaymentResource($supplier_payment));
    }

    public function destroy(SupplierPayment $supplier_payment): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Update invoice if linked
            if ($supplier_payment->invoice_id) {
                $invoice = PurchaseInvoice::find($supplier_payment->invoice_id);
                if ($invoice) {
                    if ($supplier_payment->payment_type === 'payment') {
                        $invoice->paid_amount -= $supplier_payment->amount;
                    } else {
                        $invoice->paid_amount += $supplier_payment->amount;
                    }
                    $invoice->calculateRemaining();
                    $invoice->updateStatus();
                }
            }

            $supplier_payment->delete();

            DB::commit();

            return $this->successResponse(null, 'Payment deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete payment', 500);
        }
    }
}
