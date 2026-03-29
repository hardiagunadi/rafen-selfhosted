<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaGatewaySettingsRequest extends FormRequest
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
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'default_test_recipient' => ['nullable', 'string', 'max:30'],
            'gateway_url' => ['nullable', 'url', 'max:255'],
            'auth_token' => ['nullable', 'string', 'max:255'],
            'master_key' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'gateway_url.url' => 'URL gateway WhatsApp harus berupa URL yang valid.',
            'business_name.max' => 'Nama bisnis maksimal 255 karakter.',
            'business_phone.max' => 'Nomor bisnis maksimal 30 karakter.',
            'default_test_recipient.max' => 'Nomor tujuan test maksimal 30 karakter.',
            'auth_token.max' => 'Token gateway maksimal 255 karakter.',
            'master_key.max' => 'Master key maksimal 255 karakter.',
        ];
    }
}
