<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use App\Http\Resources\CashAccountResource;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    public function index()
    {
        $accounts = CashAccount::with('account')->get();

        // Manual balance calculation logic
        foreach ($accounts as $account) {
            $receipts = \App\Models\Receipt::where('cash_account_id', $account->id)
                ->where('status', 'posted')
                ->sum('amount_iqd');

            $payments = \App\Models\Payment::where('cash_account_id', $account->id)
                ->where('status', 'posted')
                ->sum('amount_iqd');

            $account->current_balance = $receipts - $payments;
        }

        return CashAccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank',
            'account_id' => [
                'required',
                'exists:accounts,id',
                function ($attribute, $value, $fail) {
                    $acc = \App\Models\Account::find($value);
                    if ($acc && strtolower($acc->type) !== 'asset') {
                        $fail('يجب أن يكون الحساب المحاسبي من نوع Asset فقط.');
                    }
                }
            ],
            'currency' => 'nullable|string|size:3',
        ]);

        $account = CashAccount::create($request->all());
        return new CashAccountResource($account);
    }

    public function update(Request $request, CashAccount $cashAccount)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:cash,bank',
            'account_id' => [
                'sometimes',
                'required',
                'exists:accounts,id',
                function ($attribute, $value, $fail) {
                    $acc = \App\Models\Account::find($value);
                    if ($acc && strtolower($acc->type) !== 'asset') {
                        $fail('يجب أن يكون الحساب المحاسبي من نوع Asset فقط.');
                    }
                }
            ],
            'is_active' => 'sometimes|boolean'
        ]);

        $cashAccount->update($request->all());
        return new CashAccountResource($cashAccount);
    }

    public function toggleStatus(CashAccount $cashAccount)
    {
        $cashAccount->update(['is_active' => !$cashAccount->is_active]);
        return response()->json(['message' => 'تم تحديث الحالة بنجاح', 'is_active' => $cashAccount->is_active]);
    }
}
