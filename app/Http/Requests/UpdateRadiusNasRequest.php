<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRadiusNasRequest extends FormRequest
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
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $radiusNas = $this->route('radiusNas');

        return [
            'name' => ['required', 'string', 'max:255'],
            'shortname' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('radius_nas', 'shortname')->ignore($radiusNas),
            ],
            'ip_address' => ['required', 'ip'],
            'secret' => ['required', 'string', 'max:255'],
            'require_message_authenticator' => ['nullable', 'boolean'],
            'auth_port' => ['nullable', 'integer', 'between:1,65535'],
            'acct_port' => ['nullable', 'integer', 'between:1,65535'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama NAS wajib diisi.',
            'shortname.required' => 'Shortname NAS wajib diisi.',
            'shortname.alpha_dash' => 'Shortname hanya boleh berisi huruf, angka, dash, dan underscore.',
            'shortname.unique' => 'Shortname NAS sudah digunakan.',
            'ip_address.required' => 'IP address NAS wajib diisi.',
            'ip_address.ip' => 'IP address NAS harus valid.',
            'secret.required' => 'Secret RADIUS wajib diisi.',
        ];
    }
}
