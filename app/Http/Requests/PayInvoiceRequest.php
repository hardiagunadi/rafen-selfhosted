<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceRequest extends FormRequest
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
            'payment_method' => ['required', 'string', 'in:cash,transfer,other'],
            'cash_received' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,cash'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,transfer'],
            'payment_note' => ['nullable', 'string'],
        ];
    }
}
