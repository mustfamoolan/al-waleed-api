<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Account::query();

        if ($request->has('account_type')) {
            $query->where('account_type', $request->get('account_type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $accounts = $query->orderBy('account_code')->get();
        return $this->successResponse(AccountResource::collection($accounts));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_account_id' => $request->parent_account_id ?? null,
            'opening_balance' => $request->opening_balance ?? 0,
            'current_balance' => $request->opening_balance ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return $this->successResponse(
            new AccountResource($account),
            'Account created successfully',
            201
        );
    }

    public function show(Account $account): JsonResponse
    {
        $account->load(['parentAccount']);
        return $this->successResponse(new AccountResource($account));
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => ['sometimes', 'required', 'string', 'max:255'],
            'account_type' => ['sometimes', 'required', 'in:asset,liability,equity,revenue,expense'],
            'parent_account_id' => ['nullable', 'exists:accounts,account_id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account->update($validated);

        return $this->successResponse(
            new AccountResource($account),
            'Account updated successfully'
        );
    }

    public function destroy(Account $account): JsonResponse
    {
        $account->delete();
        return $this->successResponse(null, 'Account deleted successfully');
    }

    public function transactions(Account $account): JsonResponse
    {
        $transactions = $account->transactions()
            ->orderBy('transaction_date', 'desc')
            ->get();

        return $this->successResponse($transactions);
    }

    public function balance(Account $account): JsonResponse
    {
        $account->updateBalance();

        return $this->successResponse([
            'account_id' => $account->account_id,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'opening_balance' => $account->opening_balance,
            'current_balance' => $account->current_balance,
        ]);
    }
}
