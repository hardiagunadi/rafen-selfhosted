<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemIsolirSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isolir_page_title' => ['nullable', 'string', 'max:255'],
            'isolir_page_body' => ['nullable', 'string', 'max:2000'],
            'isolir_page_contact' => ['nullable', 'string', 'max:255'],
            'isolir_page_bg_color' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'isolir_page_accent_color' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'isolir_page_bg_color.regex' => 'Warna background harus berupa kode hex yang valid.',
            'isolir_page_accent_color.regex' => 'Warna aksen harus berupa kode hex yang valid.',
        ];
    }
}
