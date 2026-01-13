<?php

namespace App\Http\Requests\JournalEntry;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,account_id'],
            'lines.*.debit_amount' => ['required_without:lines.*.credit_amount', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['required_without:lines.*.debit_amount', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $totalDebit += $line['debit_amount'] ?? 0;
                $totalCredit += $line['credit_amount'] ?? 0;
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('lines', 'Total debit must equal total credit.');
            }
        });
    }
}
