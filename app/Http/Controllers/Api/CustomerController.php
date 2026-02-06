<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(Customer::with('account', 'addresses')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'sales_type' => 'required|in:cash,credit',
            'credit_limit' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer created successfully',
            'customer' => $customer->load('account', 'addresses')
        ], 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load('account', 'addresses'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'sales_type' => 'sometimes|required|in:cash,credit',
            'credit_limit' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer updated successfully',
            'customer' => $customer->load('account', 'addresses')
        ]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Customer deleted successfully'
        ]);
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->update(['is_active' => !$customer->is_active]);
        return response()->json([
            'status' => 'success',
            'message' => 'تم تغيير حالة الزبون بنجاح',
            'is_active' => $customer->is_active
        ]);
    }
}
