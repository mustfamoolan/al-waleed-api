<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Representative\RepresentativeBalanceAdjustRequest;
use App\Http\Requests\Representative\RepresentativeBalanceDepositRequest;
use App\Http\Requests\Representative\RepresentativeBalanceWithdrawRequest;
use App\Http\Resources\RepresentativeBalanceResource;
use App\Http\Resources\RepresentativeBalanceTransactionResource;
use App\Models\Representative;
use App\Models\RepresentativeBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepresentativeBalanceController extends BaseController
{
    /**
     * Get authenticated representative's balance.
     */
    public function myBalance(Request $request): JsonResponse
    {
        $representative = $request->user();
        return $this->show($representative);
    }

    /**
     * Get authenticated representative's transactions.
     */
    public function myTransactions(Request $request): JsonResponse
    {
        $representative = $request->user();
        return $this->transactions($request, $representative);
    }

    /**
     * Display the balance for a representative.
     */
    public function show(Representative $representative): JsonResponse
    {
        $balance = RepresentativeBalance::getOrCreate($representative->rep_id);
        return $this->successResponse(new RepresentativeBalanceResource($balance->load('representative')));
    }

    /**
     * Display balance transactions for a representative.
     */
    public function transactions(Request $request, Representative $representative): JsonResponse
    {
        $query = $representative->transactions()->with(['creator']);

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->get('transaction_type'));
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->get('to_date'));
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(RepresentativeBalanceTransactionResource::collection($transactions));
    }

    /**
     * Withdraw from balance.
     */
    public function withdraw(RepresentativeBalanceWithdrawRequest $request, Representative $representative): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $balance = RepresentativeBalance::getOrCreate($representative->rep_id);

            // Check if sufficient balance
            if ($balance->current_balance < $validated['amount']) {
                return $this->errorResponse('Insufficient balance', 422);
            }

            $transaction = $balance->addTransaction(
                'withdrawal',
                -$validated['amount'],
                $validated['description'] ?? 'سحب من الرصيد',
                null,
                null,
                $manager->manager_id
            );

            DB::commit();

            return $this->successResponse(
                new RepresentativeBalanceTransactionResource($transaction->load(['creator'])),
                'Withdrawal completed successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative balance withdrawal error: ' . $e->getMessage());
            return $this->errorResponse('Failed to withdraw from balance', 500);
        }
    }

    /**
     * Deposit to balance.
     */
    public function deposit(RepresentativeBalanceDepositRequest $request, Representative $representative): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $balance = RepresentativeBalance::getOrCreate($representative->rep_id);

            $transaction = $balance->addTransaction(
                'payment',
                $validated['amount'],
                $validated['description'] ?? 'إيداع إضافي',
                null,
                null,
                $manager->manager_id
            );

            DB::commit();

            return $this->successResponse(
                new RepresentativeBalanceTransactionResource($transaction->load(['creator'])),
                'Deposit completed successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative balance deposit error: ' . $e->getMessage());
            return $this->errorResponse('Failed to deposit to balance', 500);
        }
    }

    /**
     * Adjust balance manually (for managers).
     */
    public function adjust(RepresentativeBalanceAdjustRequest $request, Representative $representative): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $balance = RepresentativeBalance::getOrCreate($representative->rep_id);

            $transaction = $balance->addTransaction(
                'adjustment',
                $validated['amount'],
                $validated['description'] ?? 'تعديل يدوي على الرصيد',
                null,
                null,
                $manager->manager_id
            );

            DB::commit();

            return $this->successResponse(
                new RepresentativeBalanceTransactionResource($transaction->load(['creator'])),
                'Balance adjusted successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Representative balance adjustment error: ' . $e->getMessage());
            return $this->errorResponse('Failed to adjust balance', 500);
        }
    }
}
