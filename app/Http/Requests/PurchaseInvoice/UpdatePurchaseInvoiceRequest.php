<?php

namespace App\Http\Requests\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseInvoiceRequest extends FormRequest
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
        $invoice = $this->route('purchase_invoice');
        $invoiceId = $invoice instanceof \App\Models\PurchaseInvoice ? $invoice->invoice_id : $invoice;

        return [
            'supplier_id' => ['sometimes', 'required', 'exists:suppliers,supplier_id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'invoice_number' => ['sometimes', 'required', 'string', 'unique:purchase_invoices,invoice_number,' . $invoiceId . ',invoice_id'],
            'invoice_date' => ['sometimes', 'required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'payment_status' => ['nullable', 'in:paid,partial,unpaid'],
            'payment_method' => ['nullable', 'in:cash,bank,deferred'],
            'subtotal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,product_id'],
            'items.*.unit_id' => ['required', 'exists:product_units,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_code' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
