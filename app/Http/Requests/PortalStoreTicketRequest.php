<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortalStoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'string', 'in:complaint,installation,troubleshoot,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Subjek pengaduan wajib diisi.',
            'message.required' => 'Detail pengaduan wajib diisi.',
            'type.in' => 'Tipe pengaduan tidak valid.',
        ];
    }
}
