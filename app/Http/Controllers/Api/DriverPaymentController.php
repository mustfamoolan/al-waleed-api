<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DriverPaymentResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleInvoiceResource;
use App\Http\Resources\CustomerBalanceResource;
use App\Http\Resources\CustomerBalanceTransactionResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\DriverPayment;
use App\Models\SaleInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverPaymentController extends BaseController
{
    /**
     * Display a listing of driver's payments (Manager view).
     */
    public function index(Request $request): JsonResponse
    {
        $query = DriverPayment::with(['customer', 'invoice', 'driver', 'approver']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->get('driver_id'));
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(DriverPaymentResource::collection($payments));
    }

    /**
     * Store a newly created payment (Driver view).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_invoice_id' => ['required', 'exists:sale_invoices,invoice_id'],
            'customer_id' => ['required', 'exists:customers,customer_id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,cheque,other'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $driver = $request->user();

        try {
            DB::beginTransaction();

            // Verify invoice is delivered by this driver
            $invoice = SaleInvoice::findOrFail($validated['sale_invoice_id']);
            
            if ($invoice->delivered_by != $driver->picker_id) {
                return $this->errorResponse('You can only record payments for invoices you delivered', 403);
            }

            if (!$invoice->canBeReturned()) {
                return $this->errorResponse('Invoice must be delivered before recording payment', 422);
            }

            // Verify customer matches invoice
            if ($invoice->customer_id != $validated['customer_id']) {
                return $this->errorResponse('Customer does not match invoice', 422);
            }

            // Create payment (pending approval)
            $payment = DriverPayment::create([
                'sale_invoice_id' => $validated['sale_invoice_id'],
                'customer_id' => $validated['customer_id'],
                'driver_id' => $driver->picker_id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            DB::commit();

            return $this->successResponse(
                new DriverPaymentResource($payment->load(['customer', 'invoice', 'driver'])),
                'Payment recorded successfully. Waiting for manager approval.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Driver payment creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(DriverPayment $driver_payment): JsonResponse
    {
        $driver_payment->load(['customer', 'invoice', 'driver', 'approver']);
        return $this->successResponse(new DriverPaymentResource($driver_payment));
    }

    /**
     * Approve payment (Manager only).
     */
    public function approve(Request $request, DriverPayment $driver_payment): JsonResponse
    {
        if ($driver_payment->status !== 'pending') {
            return $this->errorResponse('Only pending payments can be approved', 422);
        }

        try {
            $manager = $request->user();
            
            if (!$driver_payment->approve($manager->manager_id)) {
                return $this->errorResponse('Failed to approve payment', 500);
            }

            return $this->successResponse(
                new DriverPaymentResource($driver_payment->load(['customer', 'invoice', 'driver', 'approver'])),
                'Payment approved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Payment approval error: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reject payment (Manager only).
     */
    public function reject(Request $request, DriverPayment $driver_payment): JsonResponse
    {
        if ($driver_payment->status !== 'pending') {
            return $this->errorResponse('Only pending payments can be rejected', 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $manager = $request->user();
            
            if (!$driver_payment->reject($manager->manager_id, $validated['reason'])) {
                return $this->errorResponse('Failed to reject payment', 500);
            }

            return $this->successResponse(
                new DriverPaymentResource($driver_payment->load(['customer', 'invoice', 'driver', 'approver'])),
                'Payment rejected successfully'
            );
        } catch (\Exception $e) {
            Log::error('Payment rejection error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reject payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get customer balance (for driver view).
     */
    public function customerBalance(Customer $customer): JsonResponse
    {
        $driver = request()->user();
        
        $balance = $customer->balance;
        if (!$balance) {
            $balance = CustomerBalance::create([
                'customer_id' => $customer->customer_id,
                'current_balance' => 0,
                'total_debt' => 0,
                'total_paid' => 0,
            ]);
        }

        // Get invoices delivered by this driver
        $invoices = SaleInvoice::where('customer_id', $customer->customer_id)
            ->where('delivered_by', $driver->picker_id)
            ->with(['items'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Get recent transactions
        $transactions = $customer->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return $this->successResponse([
            'customer' => new CustomerResource($customer),
            'balance' => new CustomerBalanceResource($balance->load('customer')),
            'invoices' => SaleInvoiceResource::collection($invoices),
            'recent_transactions' => CustomerBalanceTransactionResource::collection($transactions),
        ]);
    }
}
