<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
        $employee = $this->route('employee');
        $employeeId = $employee instanceof \App\Models\Employee ? $employee->emp_id : $employee;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'unique:employees,phone_number,' . $employeeId . ',emp_id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'job_role' => ['sometimes', 'required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
