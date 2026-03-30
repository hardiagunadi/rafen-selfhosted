<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunTerminalCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'command.required' => 'Command wajib diisi.',
            'command.max' => 'Command maksimal 300 karakter.',
        ];
    }
}
