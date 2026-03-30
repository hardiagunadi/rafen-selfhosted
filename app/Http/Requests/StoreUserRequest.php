<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30', 'required_if:role,teknisi'],
            'role' => ['required', 'string', 'in:administrator,it_support,noc,keuangan,teknisi,cs'],
            'nickname' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'phone.required_if' => 'Nomor HP wajib diisi untuk role teknisi.',
            'phone.max' => 'Nomor HP maksimal 30 karakter.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ];
    }
}
