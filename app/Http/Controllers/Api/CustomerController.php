<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\Representative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends BaseController
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['representatives', 'balance']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Filter by representative
        if ($request->has('representative_id')) {
            $query->whereHas('representatives', function($q) use ($request) {
                $q->where('representative_id', $request->get('representative_id'));
            });
        }

        $customers = $query->orderBy('customer_name')->paginate($request->get('per_page', 15));

        return $this->successResponse(CustomerResource::collection($customers));
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->manager_id,
            ]);

            // Create balance record
            $customer->balance()->create([
                'customer_id' => $customer->customer_id,
                'current_balance' => 0,
                'total_debt' => 0,
                'total_paid' => 0,
            ]);

            DB::commit();

            return $this->successResponse(new CustomerResource($customer->load(['balance', 'representatives', 'creator'])), 'Customer created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create customer', 500);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['representatives', 'balance', 'creator']);
        return $this->successResponse(new CustomerResource($customer));
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();

        try {
            $customer->update($validated);
            return $this->successResponse(new CustomerResource($customer->load(['balance', 'representatives', 'creator'])), 'Customer updated successfully');
        } catch (\Exception $e) {
            Log::error('Customer update error: ' . $e->getMessage());
            return $this->errorResponse('Failed to update customer', 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            // Check if customer has unpaid invoices
            $unpaidInvoices = $customer->saleInvoices()
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->where('remaining_amount', '>', 0)
                ->count();

            if ($unpaidInvoices > 0) {
                return $this->errorResponse('Cannot delete customer with unpaid invoices', 422);
            }

            $customer->delete();
            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            Log::error('Customer deletion error: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete customer', 500);
        }
    }

    /**
     * Get customer balance.
     */
    public function balance(Customer $customer): JsonResponse
    {
        $balance = $customer->balance;
        if (!$balance) {
            $balance = CustomerBalance::create([
                'customer_id' => $customer->customer_id,
                'current_balance' => 0,
                'total_debt' => 0,
                'total_paid' => 0,
            ]);
        }
        return $this->successResponse(new \App\Http\Resources\CustomerBalanceResource($balance->load('customer')));
    }

    /**
     * Get customer transactions.
     */
    public function transactions(Request $request, Customer $customer): JsonResponse
    {
        $query = $customer->transactions();

        if ($request->has('type')) {
            $query->where('transaction_type', $request->get('type'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($transactions);
    }

    /**
     * Get customer invoices.
     */
    public function invoices(Request $request, Customer $customer): JsonResponse
    {
        $query = $customer->saleInvoices()->with(['items', 'representative']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse(\App\Http\Resources\SaleInvoiceResource::collection($invoices));
    }

    /**
     * Get customer representatives.
     */
    public function representatives(Customer $customer): JsonResponse
    {
        $representatives = $customer->representatives;
        return $this->successResponse(\App\Http\Resources\RepresentativeResource::collection($representatives));
    }

    /**
     * Assign representative to customer.
     */
    public function assignRepresentative(Request $request, Customer $customer, Representative $representative): JsonResponse
    {
        try {
            if ($customer->representatives()->where('representative_id', $representative->rep_id)->exists()) {
                return $this->errorResponse('Representative already assigned to this customer', 422);
            }

            $customer->representatives()->attach($representative->rep_id, [
                'assigned_by' => $request->user()->manager_id,
                'assigned_at' => now(),
            ]);

            return $this->successResponse(null, 'Representative assigned successfully');
        } catch (\Exception $e) {
            Log::error('Assign representative error: ' . $e->getMessage());
            return $this->errorResponse('Failed to assign representative', 500);
        }
    }

    /**
     * Remove representative from customer.
     */
    public function removeRepresentative(Customer $customer, Representative $representative): JsonResponse
    {
        try {
            $customer->representatives()->detach($representative->rep_id);
            return $this->successResponse(null, 'Representative removed successfully');
        } catch (\Exception $e) {
            Log::error('Remove representative error: ' . $e->getMessage());
            return $this->errorResponse('Failed to remove representative', 500);
        }
    }
}
