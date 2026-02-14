<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Resources\SupplierResource;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return SupplierResource::collection(Supplier::with('account')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable',
            'currency' => 'nullable',
            'exchange_rate' => 'nullable',
            'account_id' => 'nullable',
            'opening_balance' => 'nullable',
            'notes' => 'nullable',
        ]);

        $supplier = Supplier::create($validated);

        return new SupplierResource($supplier->load('account'));
    }

    public function show(Supplier $supplier)
    {
        return new SupplierResource($supplier->load('account'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'currency' => 'sometimes|required|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
        ]);

        $supplier->update($validated);

        return new SupplierResource($supplier->load('account'));
    }

    public function destroy(Supplier $supplier)
    {
        // Check for transactions
        $hasInvoices = \App\Models\PurchaseInvoice::where('supplier_id', $supplier->id)->exists();
        $hasPayments = \App\Models\Payment::where('supplier_id', $supplier->id)->exists();

        if ($hasInvoices || $hasPayments) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن حذف المورد لوجود حركات مالية مرتبطة به. يفضل تعطيل المورد بدلاً من حذفه.'
            ], 400);
        }

        $supplier->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Supplier deleted successfully'
        ]);
    }

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->update(['is_active' => !$supplier->is_active]);
        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير حالة المورد بنجاح',
            'is_active' => $supplier->is_active
        ]);
    }
}
