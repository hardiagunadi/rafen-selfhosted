<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWaKeywordRuleRequest extends FormRequest
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
            'keywords_text' => ['required', 'string', 'max:1000'],
            'reply_text' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keywords_text.required' => 'Keyword wajib diisi.',
            'reply_text.required' => 'Pesan balasan otomatis wajib diisi.',
        ];
    }
}
