<?php

namespace App\Http\Requests\Picker;

use Illuminate\Foundation\Http\FormRequest;

class StorePickerRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'unique:pickers,phone_number'],
            'password' => ['required', 'string', 'min:6'],
            'profile_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
