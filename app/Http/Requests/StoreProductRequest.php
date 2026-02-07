<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required_without:name_ar|string|max:255',
            'name_ar' => 'required_without:name|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'category_id' => 'required|exists:product_categories,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price_retail' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0', // Alias
            'sale_price_wholesale' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0', // Alias
            'base_unit_id' => 'required|exists:units,id',
            'has_pack' => 'boolean',
            'pack_unit_id' => 'required_if:has_pack,true|nullable|exists:units,id',
            'units_per_pack' => 'nullable|numeric|min:1',
            'pieces_per_carton' => 'nullable|numeric|min:1', // Alias
            'piece_weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|max:20',
            'carton_weight' => 'nullable|numeric|min:0',
            'supplier_id' => 'required|exists:suppliers,id', // MANDATORY
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
        ];
    }
}
