<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaMultiSessionDeviceRequest extends FormRequest
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
            'device_name' => ['required', 'string', 'max:120', 'unique:wa_multi_session_devices,device_name'],
            'wa_number' => ['nullable', 'string', 'max:30', 'regex:/^\d+$/'],
            'session_id' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:wa_multi_session_devices,session_id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_name.required' => 'Nama device wajib diisi.',
            'device_name.unique' => 'Nama device sudah digunakan.',
            'wa_number.regex' => 'Nomor WhatsApp harus berupa angka saja.',
            'session_id.regex' => 'Session ID hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda minus.',
            'session_id.unique' => 'Session ID sudah digunakan.',
        ];
    }
}
