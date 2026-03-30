<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOutageRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'started_at' => ['required', 'date'],
            'estimated_resolved_at' => ['nullable', 'date', 'after:started_at'],
            'area_labels' => ['nullable', 'string'],
            'custom_areas' => ['nullable', 'array'],
            'custom_areas.*' => ['string', 'max:150'],
            'include_status_link' => ['sometimes', 'boolean'],
        ];
    }
}
