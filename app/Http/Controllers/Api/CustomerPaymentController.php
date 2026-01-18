<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPayment\StoreCustomerPaymentRequest;
use App\Http\Requests\CustomerPayment\UpdateCustomerPaymentRequest;
use App\Http\Resources\CustomerPaymentResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\CustomerPayment;
use App\Models\SaleInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerPaymentController extends BaseController
{
    /**
     * Display a listing of customer payments.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerPayment::with(['customer', 'invoice', 'creator']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->get('invoice_id'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->get('to_date'));
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(CustomerPaymentResource::collection($payments));
    }

    /**
     * Store a newly created customer payment.
     */
    public function store(StoreCustomerPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $manager = $request->user();

            // Verify customer exists
            $customer = Customer::findOrFail($validated['customer_id']);

            // If invoice_id provided, verify it belongs to customer
            if (isset($validated['invoice_id'])) {
                $invoice = SaleInvoice::findOrFail($validated['invoice_id']);
                if ($invoice->customer_id !== $customer->customer_id || $invoice->buyer_type !== 'customer') {
                    return $this->errorResponse('Invoice does not belong to this customer', 422);
                }
            }

            // Create payment
            $payment = CustomerPayment::create([
                'customer_id' => $validated['customer_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $manager->manager_id,
            ]);

            // Update invoice if linked
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                $invoice->paid_amount += $payment->amount;
                $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
                $invoice->updateStatus();
                $invoice->save();
            }

            // Update customer balance
            $balance = CustomerBalance::getOrCreate($customer->customer_id);
            $balance->recordTransaction(
                'payment',
                -$payment->amount, // Negative because it's a payment (reduces debt)
                $payment->invoice_id 
                    ? "دفع فاتورة: {$payment->invoice->invoice_number}" 
                    : "دفع عام",
                'customer_payment',
                $payment->payment_id,
                $manager->manager_id
            );

            // Update customer totals
            $customer->total_paid += $payment->amount;
            $customer->last_payment_date = $payment->payment_date;
            $customer->updateBalance();

            DB::commit();

            return $this->successResponse(
                new CustomerPaymentResource($payment->load(['customer', 'invoice', 'creator'])),
                'Payment recorded successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer payment creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(CustomerPayment $customer_payment): JsonResponse
    {
        $customer_payment->load(['customer', 'invoice', 'creator']);
        return $this->successResponse(new CustomerPaymentResource($customer_payment));
    }

    /**
     * Update the specified payment.
     */
    public function update(UpdateCustomerPaymentRequest $request, CustomerPayment $customer_payment): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $oldAmount = $customer_payment->amount;
            $customer_payment->update($validated);

            // If amount changed, update invoice and balance
            if (isset($validated['amount']) && $validated['amount'] != $oldAmount) {
                $difference = $validated['amount'] - $oldAmount;

                // Update invoice if linked
                if ($customer_payment->invoice_id) {
                    $invoice = $customer_payment->invoice;
                    $invoice->paid_amount += $difference;
                    $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
                    $invoice->updateStatus();
                    $invoice->save();
                }

                // Update customer balance
                $balance = CustomerBalance::getOrCreate($customer_payment->customer_id);
                $balance->recordTransaction(
                    'adjustment',
                    -$difference,
                    "تعديل دفعة: #{$customer_payment->payment_id}",
                    'customer_payment',
                    $customer_payment->payment_id,
                    $request->user()->manager_id
                );

                // Update customer totals
                $customer = $customer_payment->customer;
                $customer->total_paid += $difference;
                $customer->updateBalance();
            }

            DB::commit();

            return $this->successResponse(
                new CustomerPaymentResource($customer_payment->load(['customer', 'invoice', 'creator'])),
                'Payment updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer payment update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update payment', 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(CustomerPayment $customer_payment): JsonResponse
    {
        try {
            DB::beginTransaction();

            $customer = $customer_payment->customer;
            $invoice = $customer_payment->invoice;

            // Reverse invoice payment
            if ($invoice) {
                $invoice->paid_amount -= $customer_payment->amount;
                $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
                $invoice->updateStatus();
                $invoice->save();
            }

            // Reverse balance transaction
            $balance = CustomerBalance::getOrCreate($customer->customer_id);
            $balance->recordTransaction(
                'adjustment',
                $customer_payment->amount, // Positive to reverse the payment
                "حذف دفعة: #{$customer_payment->payment_id}",
                null,
                null,
                request()->user()->manager_id
            );

            // Update customer totals
            $customer->total_paid -= $customer_payment->amount;
            $customer->updateBalance();

            $customer_payment->delete();

            DB::commit();

            return $this->successResponse(null, 'Payment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer payment deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete payment', 500);
        }
    }

    /**
     * Apply payment to a specific invoice.
     */
    public function applyToInvoice(Request $request, CustomerPayment $payment, SaleInvoice $invoice): JsonResponse
    {
        if ($payment->customer_id !== $invoice->customer_id || $invoice->buyer_type !== 'customer') {
            return $this->errorResponse('Payment and invoice must belong to the same customer', 422);
        }

        try {
            DB::beginTransaction();

            $oldInvoiceId = $payment->invoice_id;

            // Remove from old invoice if exists
            if ($oldInvoiceId) {
                $oldInvoice = SaleInvoice::find($oldInvoiceId);
                if ($oldInvoice) {
                    $oldInvoice->paid_amount -= $payment->amount;
                    $oldInvoice->remaining_amount = $oldInvoice->total_amount - $oldInvoice->paid_amount;
                    $oldInvoice->updateStatus();
                    $oldInvoice->save();
                }
            }

            // Apply to new invoice
            $invoice->paid_amount += $payment->amount;
            $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
            $invoice->updateStatus();
            $invoice->save();

            $payment->invoice_id = $invoice->invoice_id;
            $payment->save();

            DB::commit();

            return $this->successResponse(
                new CustomerPaymentResource($payment->load(['customer', 'invoice', 'creator'])),
                'Payment applied to invoice successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Apply payment to invoice error: ' . $e->getMessage());
            return $this->errorResponse('Failed to apply payment to invoice', 500);
        }
    }
}
