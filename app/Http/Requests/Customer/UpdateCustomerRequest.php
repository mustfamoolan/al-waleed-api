<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer')->customer_id;

        return [
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20', 'unique:customers,phone_number,' . $customerId . ',customer_id'],
            'address' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string', 'in:active,inactive'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
