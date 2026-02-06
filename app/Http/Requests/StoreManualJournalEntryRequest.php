<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'lines' => 'required|array|min:2', // At least 2 lines (Dr & Cr)
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit_amount' => 'required|numeric|min:0',
            'lines.*.credit_amount' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines');
            $totalDebit = collect($lines)->sum('debit_amount');
            $totalCredit = collect($lines)->sum('credit_amount');

            if (abs($totalDebit - $totalCredit) > 0.001) { // Floating point tolerance
                $validator->errors()->add('lines', 'القيد غير متوازن (المدين لا يساوي الدائن).');
            }
        });
    }
}
