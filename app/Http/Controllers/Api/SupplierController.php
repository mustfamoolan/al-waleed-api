<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SupplierController extends BaseController
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->get();
        return $this->successResponse(SupplierResource::collection($suppliers));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $supplier = Supplier::create([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'current_balance' => $validated['opening_balance'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->successResponse(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return $this->successResponse(new SupplierResource($supplier));
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validated();
        
        $updateData = [
            'name' => $validated['name'] ?? $supplier->name,
            'contact_person' => $validated['contact_person'] ?? $supplier->contact_person,
            'phone' => $validated['phone'] ?? $supplier->phone,
            'email' => $validated['email'] ?? $supplier->email,
            'tax_number' => $validated['tax_number'] ?? $supplier->tax_number,
            'address' => $validated['address'] ?? $supplier->address,
            'opening_balance' => $validated['opening_balance'] ?? $supplier->opening_balance,
            'notes' => $validated['notes'] ?? $supplier->notes,
            'is_active' => $validated['is_active'] ?? $supplier->is_active,
        ];

        $supplier->update($updateData);

        return $this->successResponse(new SupplierResource($supplier), 'Supplier updated successfully');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();
        return $this->successResponse(null, 'Supplier deleted successfully');
    }


    /**
     * Get supplier balance.
     */
    public function balance(Supplier $supplier): JsonResponse
    {
        $supplier->updateBalance();
        
        return $this->successResponse([
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'opening_balance' => $supplier->opening_balance,
            'current_balance' => $supplier->current_balance,
            'total_purchases' => $supplier->totalPurchases(),
            'total_payments' => $supplier->totalPayments(),
            'total_returns' => $supplier->totalReturns(),
        ]);
    }

    /**
     * Get supplier summary.
     */
    public function summary(Supplier $supplier): JsonResponse
    {
        $supplier->updateBalance();
        
        $summary = [
            'supplier' => new SupplierResource($supplier),
            'opening_balance' => $supplier->opening_balance,
            'current_balance' => $supplier->current_balance,
            'total_invoices' => $supplier->purchaseInvoices()->count(),
            'total_purchases' => $supplier->totalPurchases(),
            'total_payments' => $supplier->totalPayments(),
            'total_returns' => $supplier->totalReturns(),
            'pending_invoices' => $supplier->purchaseInvoices()
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->count(),
            'transactions' => $supplier->transactions()
                ->orderBy('transaction_date', 'desc')
                ->limit(10)
                ->get(),
        ];

        return $this->successResponse($summary);
    }

    /**
     * Get supplier transactions.
     */
    public function transactions(Request $request, Supplier $supplier): JsonResponse
    {
        $query = $supplier->transactions();

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->get('transaction_type'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->get('to_date'));
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($transactions);
    }
}
