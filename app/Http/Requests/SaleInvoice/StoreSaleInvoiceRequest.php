<?php

namespace App\Http\Requests\SaleInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleInvoiceRequest extends FormRequest
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
        $rules = [
            'representative_id' => ['nullable', 'exists:representatives,rep_id'],
            'buyer_type' => ['required', 'string', 'in:customer,walk_in,employee,representative'],
            'buyer_id' => ['nullable', 'integer'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,customer_id'],
            'invoice_number' => ['required', 'string', 'unique:sale_invoices,invoice_number'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'special_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,credit'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_type' => ['required', 'in:piece,carton'],
            'items.*.carton_count' => ['nullable', 'numeric', 'min:0', 'required_if:items.*.unit_type,carton'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];

        // Conditional validation based on buyer_type
        $buyerType = $this->input('buyer_type');

        if ($buyerType === 'customer') {
            $rules['customer_id'][] = 'required';
        } elseif ($buyerType === 'walk_in') {
            $rules['buyer_name'][] = 'required';
        } elseif (in_array($buyerType, ['employee', 'representative'])) {
            $rules['buyer_id'][] = 'required';
            if ($buyerType === 'employee') {
                $rules['buyer_id'][] = 'exists:employees,emp_id';
            } else {
                $rules['buyer_id'][] = 'exists:representatives,rep_id';
            }
        }

        return $rules;
    }
}
