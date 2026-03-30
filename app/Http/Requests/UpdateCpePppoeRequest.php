<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCpePppoeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $radiusAccountId = $this->route('cpeDevice')?->radius_account_id;

        return [
            'username' => [
                'required',
                'string',
                'max:64',
                Rule::unique('radius_accounts', 'username')->ignore($radiusAccountId),
            ],
            'password' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username PPPoE wajib diisi.',
            'username.max' => 'Username PPPoE maksimal 64 karakter.',
            'username.unique' => 'Username PPPoE sudah dipakai oleh akun Radius lain.',
            'password.required' => 'Password PPPoE wajib diisi.',
            'password.max' => 'Password PPPoE maksimal 64 karakter.',
        ];
    }
}
