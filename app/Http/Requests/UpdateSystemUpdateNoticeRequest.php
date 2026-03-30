<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemUpdateNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'update_is_active' => ['nullable', 'boolean'],
            'update_available_version' => ['nullable', 'string', 'max:255', 'required_if:update_is_active,1'],
            'update_headline' => ['nullable', 'string', 'max:255'],
            'update_summary' => ['nullable', 'string', 'max:2000'],
            'update_instructions' => ['nullable', 'string', 'max:3000'],
            'update_release_notes_url' => ['nullable', 'url', 'max:255'],
            'update_severity' => ['nullable', Rule::in(['info', 'warning', 'danger'])],
            'update_available_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'update_available_version.required_if' => 'Versi terbaru wajib diisi saat notifikasi update diaktifkan.',
            'update_release_notes_url.url' => 'Tautan catatan rilis harus berupa URL yang valid.',
            'update_severity.in' => 'Tingkat urgensi update tidak valid.',
        ];
    }
}
