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
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'currency' => 'required|string|max:10',
            'exchange_rate' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'numeric',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier created successfully',
            'supplier' => $supplier->load('account')
        ], 201);
    }

    public function show(Supplier $supplier)
    {
        return new SupplierResource($supplier->load('account'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'currency' => 'sometimes|required|string|max:10',
            'exchange_rate' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'numeric',
        ]);

        $supplier->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier updated successfully',
            'supplier' => $supplier->load('account')
        ]);
    }

    public function destroy(Supplier $supplier)
    {
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
