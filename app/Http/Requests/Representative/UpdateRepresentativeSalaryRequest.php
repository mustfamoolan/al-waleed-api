<?php

namespace App\Http\Requests\Representative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentativeSalaryRequest extends FormRequest
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
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'total_bonuses' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
