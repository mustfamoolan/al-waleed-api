<?php

namespace App\Http\Requests\Picker;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePickerRequest extends FormRequest
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
        $picker = $this->route('picker');
        $pickerId = $picker instanceof \App\Models\Picker ? $picker->picker_id : $picker;
        
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'unique:pickers,phone_number,' . $pickerId . ',picker_id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'profile_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
