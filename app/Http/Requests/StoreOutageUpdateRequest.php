<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOutageUpdateRequest extends FormRequest
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
            'body' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:created,note,status_change,resolved'],
            'meta' => ['nullable', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
            'change_status' => ['nullable', 'string', 'in:open,in_progress,resolved'],
        ];
    }
}
