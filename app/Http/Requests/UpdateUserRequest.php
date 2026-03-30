<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30', 'required_if:role,teknisi'],
            'role' => ['sometimes', 'required', 'string', 'in:administrator,it_support,noc,keuangan,teknisi,cs'],
            'nickname' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_if' => 'Nomor HP wajib diisi untuk role teknisi.',
            'phone.max' => 'Nomor HP maksimal 30 karakter.',
        ];
    }
}
