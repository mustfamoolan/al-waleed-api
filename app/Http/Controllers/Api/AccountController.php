<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    // Get full tree
    public function index()
    {
        $accounts = Account::root()->with('childrenRecursive')->get();

        return response()->json([
            'status' => 'success',
            'data' => $accounts,
        ]);
    }

    // Get flat list (for dropdowns)
    public function list()
    {
        $accounts = Account::all();
        return response()->json([
            'status' => 'success',
            'data' => $accounts,
        ]);
    }

    // Create Account
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|string|unique:accounts,account_code',
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'is_postable' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;

        $account = Account::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة الحساب بنجاح',
            'data' => $account,
        ], 201);
    }
}
