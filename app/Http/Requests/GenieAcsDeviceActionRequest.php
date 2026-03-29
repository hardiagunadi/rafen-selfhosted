<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenieAcsDeviceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'in:igd,device'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID GenieACS wajib diisi.',
            'device_id.max' => 'Device ID GenieACS terlalu panjang.',
            'profile.in' => 'Profil parameter GenieACS tidak valid.',
        ];
    }
}
