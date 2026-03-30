<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOdpRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:120', 'unique:odps,code'],
            'name' => ['required', 'string', 'max:150'],
            'area' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity_ports' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', 'string', 'in:active,inactive,maintenance'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode ODP wajib diisi.',
            'name.required' => 'Nama ODP wajib diisi.',
        ];
    }
}
