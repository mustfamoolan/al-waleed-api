<?php

namespace App\Http\Requests\Representative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentativeRequest extends FormRequest
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
        $representative = $this->route('representative');
        $representativeId = $representative instanceof \App\Models\Representative ? $representative->rep_id : $representative;
        
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'unique:representatives,phone_number,' . $representativeId . ',rep_id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'profile_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
