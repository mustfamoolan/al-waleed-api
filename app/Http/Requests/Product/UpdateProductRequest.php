<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');
        $productId = $product instanceof \App\Models\Product ? $product->product_id : $product;

        return [
            'product_name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId, 'product_id')],
            'category_id' => ['nullable', 'exists:categories,category_id'],
            'supplier_id' => ['nullable', 'exists:suppliers,supplier_id'],
            'unit_type' => ['sometimes', 'required', 'in:piece,carton'],
            'pieces_per_carton' => ['nullable', 'integer', 'min:1', 'required_if:unit_type,carton'],
            'piece_weight' => ['nullable', 'numeric', 'min:0'],
            'weight_unit' => ['nullable', 'in:kg,gram,liter,ml,piece'],
            'purchase_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'last_purchase_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
