<?php

namespace App\Http\Requests\CustomerPayment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_date' => ['sometimes', 'required', 'date'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'in:cash,bank_transfer,cheque,other'],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
