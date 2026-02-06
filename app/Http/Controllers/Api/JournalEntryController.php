<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualJournalEntryRequest;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function index()
    {
        // List entries, newest first
        $entries = JournalEntry::with(['lines.account', 'createdBy'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json($entries);
    }

    public function show(JournalEntry $journalEntry)
    {
        return response()->json($journalEntry->load(['lines.account', 'createdBy', 'approvedBy']));
    }

    public function storeManual(StoreManualJournalEntryRequest $request)
    {
        $entry = DB::transaction(function () use ($request) {
            $entry = JournalEntry::create([
                'entry_date' => $request->entry_date,
                'reference_type' => 'manual',
                'description' => $request->description,
                'status' => 'draft', // Manual entries start as draft by default
                'created_by' => auth()->id(),
            ]);

            foreach ($request->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }

            return $entry;
        });

        return response()->json(['message' => 'تم إنشاء القيد بنجاح', 'entry' => $entry->load('lines')], 201);
    }

    public function post(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return response()->json(['message' => 'لا يمكن ترحيل قيد غير مسودة'], 400);
        }

        if (!$journalEntry->isBalanced()) {
            return response()->json(['message' => 'القيد غير متوازن'], 400);
        }

        $journalEntry->update([
            'status' => 'posted',
            'approved_by' => auth()->id(),
        ]);

        // Effect on account balances could be updated here if we are storing balances in 'accounts' table
        // For now, we rely on aggregating lines for balance calculation or assuming this triggers nothing else for manual entries.

        return response()->json(['message' => 'تم ترحيل القيد بنجاح', 'entry' => $journalEntry]);
    }

    public function cancel(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'posted') {
            return response()->json(['message' => 'يمكن إلغاء القيود المرحلة فقط'], 400);
        }

        DB::transaction(function () use ($journalEntry) {
            $journalEntry->update(['status' => 'canceled']);

            // Create Reverse Entry
            $reverseEntry = JournalEntry::create([
                'entry_date' => now(), // Or original date? Usually current date for reversal
                'reference_type' => 'reversal',
                'reference_id' => $journalEntry->id,
                'description' => 'إلغاء القيد رقم #' . $journalEntry->id,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($journalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reverseEntry->id,
                    'account_id' => $line->account_id,
                    'debit_amount' => $line->credit_amount, // Swap
                    'credit_amount' => $line->debit_amount, // Swap
                ]);
            }
        });

        return response()->json(['message' => 'تم إلغاء القيد وإنشاء قيد عكسي بنجاح']);
    }
}
