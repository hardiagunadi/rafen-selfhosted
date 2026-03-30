<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendWaBlastRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:ppp,hotspot,all'],
            'status_akun' => ['nullable', 'string', 'in:enable,disable,isolir'],
            'status_bayar' => ['nullable', 'string', 'in:sudah_bayar,belum_bayar'],
            'ppp_profile_id' => ['nullable', 'integer', 'exists:ppp_profiles,id'],
            'hotspot_profile_id' => ['nullable', 'integer', 'exists:hotspot_profiles,id'],
            'recipient_keys' => ['nullable', 'array'],
            'recipient_keys.*' => ['string'],
            'message' => ['required', 'string', 'min:5', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe penerima wajib dipilih.',
            'message.required' => 'Pesan blast wajib diisi.',
            'message.min' => 'Pesan blast minimal 5 karakter.',
        ];
    }
}
