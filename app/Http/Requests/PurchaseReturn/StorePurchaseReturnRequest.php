<?php

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
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
            'reference_invoice_id' => ['nullable', 'exists:purchase_invoices,invoice_id'],
            'supplier_id' => ['required', 'exists:suppliers,supplier_id'],
            'return_number' => ['required', 'string', 'unique:purchase_returns,return_number'],
            'return_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,product_id'],
            'items.*.batch_id' => ['required', 'exists:inventory_batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_code' => ['nullable', 'string', 'max:255'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }
}
