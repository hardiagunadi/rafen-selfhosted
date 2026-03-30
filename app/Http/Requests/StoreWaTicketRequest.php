<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWaTicketRequest extends FormRequest
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
            'ppp_user_id' => ['nullable', 'integer', 'exists:ppp_users,id'],
            'customer_name' => ['nullable', 'string', 'max:255', 'required_without:ppp_user_id'],
            'customer_phone' => ['nullable', 'string', 'max:32', 'required_without:ppp_user_id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:complaint,troubleshoot,installation,other'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required_without' => 'Nama pelanggan manual wajib diisi jika tidak memilih pelanggan PPP.',
            'customer_phone.required_without' => 'Nomor kontak manual wajib diisi jika tidak memilih pelanggan PPP.',
        ];
    }
}
