<?php

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_invoice_id' => ['nullable', 'exists:purchase_invoices,invoice_id'],
            'supplier_id' => ['required', 'exists:suppliers,supplier_id'],
            'return_invoice_number' => ['required', 'string', 'unique:purchase_return_invoices,return_invoice_number'],
            'return_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.original_item_id' => ['nullable', 'exists:purchase_invoice_items,item_id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_code' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }
}
