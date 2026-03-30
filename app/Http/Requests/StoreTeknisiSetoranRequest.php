<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeknisiSetoranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teknisi_id' => ['nullable', 'integer', 'exists:users,id'],
            'period_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_date.required' => 'Tanggal periode wajib diisi.',
            'period_date.date' => 'Tanggal periode tidak valid.',
            'teknisi_id.exists' => 'Teknisi yang dipilih tidak ditemukan.',
        ];
    }
}
