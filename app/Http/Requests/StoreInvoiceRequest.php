<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'ppp_user_id' => ['required', 'integer', 'exists:ppp_users,id'],
            'due_date' => ['required', 'date'],
            'harga_dasar' => ['nullable', 'numeric', 'min:0'],
            'ppn_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paket_langganan' => ['nullable', 'string', 'max:150'],
        ];
    }
}
