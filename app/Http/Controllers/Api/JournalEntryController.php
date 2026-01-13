<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\JournalEntry\StoreJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\AccountTransaction;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalEntryController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::with(['lines.account', 'creator']);

        if ($request->has('is_posted')) {
            $query->where('is_posted', $request->boolean('is_posted'));
        }

        $entries = $query->orderBy('entry_date', 'desc')->get();
        return $this->successResponse(JournalEntryResource::collection($entries));
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $manager = $request->user();

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($validated['lines'] as $line) {
                $totalDebit += $line['debit_amount'] ?? 0;
                $totalCredit += $line['credit_amount'] ?? 0;
            }

            $entryNumber = 'JE-' . date('Y') . '-' . str_pad(JournalEntry::max('entry_id') + 1, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_posted' => false,
                'created_by' => $manager->manager_id,
            ]);

            foreach ($validated['lines'] as $lineData) {
                JournalEntryLine::create([
                    'entry_id' => $entry->entry_id,
                    'account_id' => $lineData['account_id'],
                    'debit_amount' => $lineData['debit_amount'] ?? 0,
                    'credit_amount' => $lineData['credit_amount'] ?? 0,
                    'description' => $lineData['description'] ?? null,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new JournalEntryResource($entry->load(['lines.account'])),
                'Journal entry created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal entry creation error: ' . $e->getMessage());
            return $this->errorResponse('Failed to create journal entry', 500);
        }
    }

    public function show(JournalEntry $journal_entry): JsonResponse
    {
        $journal_entry->load(['lines.account', 'creator']);
        return $this->successResponse(new JournalEntryResource($journal_entry));
    }

    public function post(JournalEntry $journal_entry): JsonResponse
    {
        if ($journal_entry->is_posted) {
            return $this->errorResponse('Journal entry is already posted', 422);
        }

        try {
            DB::beginTransaction();

            $journal_entry->load('lines');

            foreach ($journal_entry->lines as $line) {
                $account = Account::find($line->account_id);
                if (!$account) continue;

                $previousBalance = $account->current_balance;
                $newBalance = $previousBalance + $line->debit_amount - $line->credit_amount;

                AccountTransaction::create([
                    'account_id' => $account->account_id,
                    'entry_id' => $journal_entry->entry_id,
                    'transaction_date' => $journal_entry->entry_date,
                    'debit_amount' => $line->debit_amount,
                    'credit_amount' => $line->credit_amount,
                    'balance_after' => $newBalance,
                    'description' => $line->description ?? $journal_entry->description,
                ]);

                $account->current_balance = $newBalance;
                $account->save();
            }

            $journal_entry->is_posted = true;
            $journal_entry->posted_at = now();
            $journal_entry->save();

            DB::commit();

            return $this->successResponse(
                new JournalEntryResource($journal_entry->load(['lines.account'])),
                'Journal entry posted successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal entry posting error: ' . $e->getMessage());
            return $this->errorResponse('Failed to post journal entry', 500);
        }
    }

    public function lines(JournalEntry $journal_entry): JsonResponse
    {
        $lines = $journal_entry->lines()->with('account')->get();
        return $this->successResponse(JournalEntryLineResource::collection($lines));
    }
}
