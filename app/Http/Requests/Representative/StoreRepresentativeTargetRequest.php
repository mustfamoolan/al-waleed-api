<?php

namespace App\Http\Requests\Representative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepresentativeTargetRequest extends FormRequest
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
            'target_type' => ['required', 'string', 'in:category,supplier,product,mixed'],
            'target_month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'target_name' => ['required', 'string', 'max:255'],
            'category_id' => [
                Rule::requiredIf($this->input('target_type') === 'category'),
                'nullable',
                'exists:categories,category_id',
            ],
            'supplier_id' => [
                Rule::requiredIf($this->input('target_type') === 'supplier'),
                'nullable',
                'exists:suppliers,supplier_id',
            ],
            'product_id' => [
                Rule::requiredIf($this->input('target_type') === 'product'),
                'nullable',
                'exists:products,product_id',
            ],
            'target_quantity' => [
                Rule::requiredIf($this->input('target_type') !== 'mixed'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'bonus_per_unit' => [
                Rule::requiredIf($this->input('target_type') !== 'mixed'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'completion_percentage_required' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => [
                Rule::requiredIf($this->input('target_type') === 'mixed'),
                'nullable',
                'array',
                'min:1',
            ],
            'items.*.item_type' => ['required_with:items', 'string', 'in:product,category,supplier'],
            'items.*.item_id' => ['required_with:items', 'numeric'],
            'items.*.target_quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.bonus_per_unit' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
