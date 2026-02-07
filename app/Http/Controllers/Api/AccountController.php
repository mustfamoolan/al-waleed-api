<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        // Return flat list for simplicity in Desktop consumption if needed
        // The WPF app expects List<Account> and filters by type='liability'
        return AccountResource::collection(Account::all());
    }

    public function show(Account $account)
    {
        return response()->json($account->load('children'));
    }

    public function store(StoreAccountRequest $request)
    {
        $account = Account::create([
            'account_code' => $request->account_code,
            'name' => $request->name,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
            'is_postable' => $request->is_postable ?? false,
            'description' => $request->description,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'تم إنشاء الحساب بنجاح', 'account' => $account], 201);
    }

    public function update(Request $request, Account $account)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_postable' => 'sometimes|boolean',
        ]);

        $account->update($request->only(['name', 'description', 'is_postable']));

        return response()->json(['message' => 'تم تحديث الحساب بنجاح', 'account' => $account]);
    }
}
