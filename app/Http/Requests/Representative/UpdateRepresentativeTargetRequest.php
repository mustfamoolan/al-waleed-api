<?php

namespace App\Http\Requests\Representative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentativeTargetRequest extends FormRequest
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
            'target_name' => ['nullable', 'string', 'max:255'],
            'target_quantity' => ['nullable', 'numeric', 'min:0'],
            'bonus_per_unit' => ['nullable', 'numeric', 'min:0'],
            'completion_percentage_required' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,completed,cancelled'],
        ];
    }
}
