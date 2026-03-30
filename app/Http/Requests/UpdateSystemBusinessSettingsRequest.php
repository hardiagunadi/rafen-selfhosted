<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'portal_title' => ['nullable', 'string', 'max:255'],
            'portal_description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_email.email' => 'Email bisnis harus berupa alamat email yang valid.',
            'website.url' => 'Website harus berupa URL yang valid.',
            'business_name.max' => 'Nama bisnis maksimal 255 karakter.',
            'portal_title.max' => 'Judul portal maksimal 255 karakter.',
        ];
    }
}
