<?php

namespace App\Http\Requests\Representative;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepresentativeSalaryRequest extends FormRequest
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
        $repId = $this->route('representative')->rep_id ?? null;
        return [
            'month' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}$/',
                'unique:representative_salaries,month,NULL,salary_id,rep_id,' . $repId
            ],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'total_bonuses' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:pending,paid,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
