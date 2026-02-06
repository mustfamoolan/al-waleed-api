<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagerRequest extends FormRequest
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
        $manager = $this->route('manager');
        $managerId = $manager instanceof \App\Models\Manager ? $manager->manager_id : $manager;
        
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'unique:managers,phone_number,' . $managerId . ',manager_id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'profile_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
