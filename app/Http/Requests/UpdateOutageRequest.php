<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOutageRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['sometimes', 'required', 'string', 'in:low,medium,high,critical'],
            'started_at' => ['sometimes', 'required', 'date'],
            'estimated_resolved_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:open,in_progress,resolved'],
            'area_labels' => ['nullable', 'string'],
            'custom_areas' => ['nullable', 'array'],
            'custom_areas.*' => ['string', 'max:150'],
            'include_status_link' => ['sometimes', 'boolean'],
        ];
    }
}
