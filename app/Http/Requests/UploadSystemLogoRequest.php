<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSystemLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_logo.required' => 'Logo bisnis wajib dipilih.',
            'business_logo.image' => 'File logo harus berupa gambar.',
            'business_logo.mimes' => 'Logo harus berformat JPG, PNG, atau WEBP.',
            'business_logo.max' => 'Ukuran logo maksimal 2MB.',
        ];
    }
}
