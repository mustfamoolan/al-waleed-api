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
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
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
            'company_name' => $validated['company_name'],
            'contact_person_name' => $validated['contact_person_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
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
            'company_name' => $validated['company_name'] ?? $supplier->company_name,
            'contact_person_name' => $validated['contact_person_name'] ?? $supplier->contact_person_name,
            'phone_number' => $validated['phone_number'] ?? $supplier->phone_number,
            'email' => $validated['email'] ?? $supplier->email,
            'address' => $validated['address'] ?? $supplier->address,
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
     * Upload profile image for supplier.
     */
    public function uploadImage(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $request->validate([
                'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);

            // حذف الصورة القديمة إذا كانت موجودة
            if ($supplier->profile_image && Storage::disk('public')->exists($supplier->profile_image)) {
                Storage::disk('public')->delete($supplier->profile_image);
            }

            // رفع الصورة الجديدة
            $path = $request->file('profile_image')->store('suppliers', 'public');
            
            // تحديث المسار في قاعدة البيانات
            $supplier->update([
                'profile_image' => $path
            ]);

            return $this->successResponse([
                'profile_image' => $path,
                'profile_image_url' => asset('storage/' . $path)
            ], 'Image uploaded successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Supplier image upload error: ' . $e->getMessage());
            return $this->errorResponse('Failed to upload image', 500);
        }
    }

    /**
     * Get supplier balance.
     */
    public function balance(Supplier $supplier): JsonResponse
    {
        $balance = $supplier->currentBalance();
        
        return $this->successResponse([
            'supplier_id' => $supplier->supplier_id,
            'company_name' => $supplier->company_name,
            'current_balance' => $balance,
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
        $summary = [
            'supplier' => new SupplierResource($supplier),
            'balance' => $supplier->currentBalance(),
            'total_invoices' => $supplier->purchaseInvoices()->count(),
            'total_purchases' => $supplier->totalPurchases(),
            'total_payments' => $supplier->totalPayments(),
            'total_returns' => $supplier->totalReturns(),
            'pending_invoices' => $supplier->purchaseInvoices()
                ->whereIn('status', ['pending', 'partial'])
                ->count(),
        ];

        return $this->successResponse($summary);
    }
}
