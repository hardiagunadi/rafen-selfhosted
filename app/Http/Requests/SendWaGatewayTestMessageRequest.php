<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWaGatewayTestMessageRequest extends FormRequest
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
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
            'device_id' => ['nullable', 'integer', 'exists:wa_multi_session_devices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_phone.max' => 'Nomor tujuan maksimal 30 karakter.',
            'message.max' => 'Pesan test maksimal 2000 karakter.',
            'device_id.exists' => 'Device WhatsApp yang dipilih tidak ditemukan.',
        ];
    }
}
