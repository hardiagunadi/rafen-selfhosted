<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBandwidthProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'upload_min_mbps' => ['sometimes', 'required', 'integer', 'min:0'],
            'upload_max_mbps' => ['sometimes', 'required', 'integer', 'min:0'],
            'download_min_mbps' => ['sometimes', 'required', 'integer', 'min:0'],
            'download_max_mbps' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
