<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return CustomerResource::collection(Customer::with('account', 'addresses')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required_without:customer_name|string|max:255',
            'customer_name' => 'required_without:name|string|max:255',
            'phone' => 'required_without:phone_number|string|max:20',
            'phone_number' => 'required_without:phone|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'sales_type' => 'required|in:cash,credit',
            'credit_limit' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'agent_id' => 'nullable|exists:sales_agents,id',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['customer_name']) && !isset($validated['name'])) {
            $validated['name'] = $validated['customer_name'];
        }
        if (isset($validated['phone_number']) && !isset($validated['phone'])) {
            $validated['phone'] = $validated['phone_number'];
        }

        $customer = Customer::create($validated);

        return new CustomerResource($customer->load('account', 'addresses'));
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer->load('account', 'addresses'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'sales_type' => 'sometimes|required|in:cash,credit',
            'credit_limit' => 'numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'agent_id' => 'nullable|exists:sales_agents,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        if (isset($data['customer_name']) && !isset($data['name'])) {
            $data['name'] = $data['customer_name'];
        }
        if (isset($data['phone_number']) && !isset($data['phone'])) {
            $data['phone'] = $data['phone_number'];
        }

        $customer->update($data);

        return new CustomerResource($customer->load('account', 'addresses'));
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
