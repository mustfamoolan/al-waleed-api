<?php

namespace App\Http\Requests\SaleInvoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleInvoiceRequest extends FormRequest
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
        $invoiceId = $this->route('sale_invoice')->invoice_id;

        return [
            'representative_id' => ['sometimes', 'nullable', 'exists:representatives,rep_id'],
            'buyer_type' => ['sometimes', 'required', 'string', 'in:customer,walk_in,employee,representative'],
            'buyer_id' => ['sometimes', 'nullable', 'integer'],
            'buyer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'nullable', 'exists:customers,customer_id'],
            'invoice_number' => ['sometimes', 'required', 'string', 'unique:sale_invoices,invoice_number,' . $invoiceId . ',invoice_id'],
            'invoice_date' => ['sometimes', 'required', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'special_discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'total_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'in:cash,credit'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_type' => ['required', 'in:piece,carton'],
            'items.*.carton_count' => ['nullable', 'numeric', 'min:0', 'required_if:items.*.unit_type,carton'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
