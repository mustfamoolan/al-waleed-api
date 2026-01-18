<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleInvoiceResource;
use App\Http\Resources\CustomerBalanceResource;
use App\Http\Resources\CustomerBalanceTransactionResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RepresentativeCustomerController extends BaseController
{
    /**
     * Display a listing of representative's customers.
     */
    public function index(Request $request): JsonResponse
    {
        $representative = $request->user();
        
        $query = $representative->customers()->with(['balance']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('customer_name')->paginate($request->get('per_page', 15));

        return $this->successResponse(CustomerResource::collection($customers));
    }

    /**
     * Display the specified customer.
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        $representative = $request->user();
        
        // Verify customer belongs to this representative
        if (!$representative->customers()->where('customer_id', $customer->customer_id)->exists()) {
            return $this->errorResponse('Customer not found or not assigned to you', 404);
        }

        $customer->load(['balance', 'representatives']);
        return $this->successResponse(new CustomerResource($customer));
    }

    /**
     * Get customer invoices.
     */
    public function invoices(Request $request, Customer $customer): JsonResponse
    {
        $representative = $request->user();
        
        // Verify customer belongs to this representative
        if (!$representative->customers()->where('customer_id', $customer->customer_id)->exists()) {
            return $this->errorResponse('Customer not found or not assigned to you', 404);
        }

        $query = $customer->saleInvoices()
            ->where('representative_id', $representative->rep_id)
            ->with(['items', 'representative']);

        if ($request->has('request_status')) {
            $query->where('request_status', $request->get('request_status'));
        }

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(SaleInvoiceResource::collection($invoices));
    }

    /**
     * Get customer balance.
     */
    public function balance(Request $request, Customer $customer): JsonResponse
    {
        $representative = $request->user();
        
        // Verify customer belongs to this representative
        if (!$representative->customers()->where('customer_id', $customer->customer_id)->exists()) {
            return $this->errorResponse('Customer not found or not assigned to you', 404);
        }

        $balance = $customer->balance;
        if (!$balance) {
            $balance = CustomerBalance::create([
                'customer_id' => $customer->customer_id,
                'current_balance' => 0,
                'total_debt' => 0,
                'total_paid' => 0,
            ]);
        }

        // Get recent transactions
        $transactions = $customer->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Get invoices summary
        $invoices = $customer->saleInvoices()
            ->where('representative_id', $representative->rep_id)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = "pending" OR status = "partial" OR status = "overdue" THEN remaining_amount ELSE 0 END) as total_debt,
                SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as total_paid
            ')
            ->first();

        return $this->successResponse([
            'balance' => new CustomerBalanceResource($balance->load('customer')),
            'recent_transactions' => CustomerBalanceTransactionResource::collection($transactions),
            'summary' => [
                'total_invoices' => $invoices->total_invoices ?? 0,
                'total_debt' => $invoices->total_debt ?? 0,
                'total_paid' => $invoices->total_paid ?? 0,
            ],
        ]);
    }

    /**
     * Update customer location.
     */
    public function updateLocation(Request $request, Customer $customer): JsonResponse
    {
        $representative = $request->user();
        
        // Verify customer belongs to this representative
        if (!$representative->customers()->where('customer_id', $customer->customer_id)->exists()) {
            return $this->errorResponse('Customer not found or not assigned to you', 404);
        }

        $validated = $request->validate([
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'location_lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $customer->update($validated);
            return $this->successResponse(new CustomerResource($customer->load(['balance'])), 'Customer location updated successfully');
        } catch (\Exception $e) {
            Log::error('Customer location update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update customer location', 500);
        }
    }
}
