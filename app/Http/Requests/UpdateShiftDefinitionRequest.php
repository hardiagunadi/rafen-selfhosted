<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
            'role' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
