<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBandwidthProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'upload_min_mbps' => ['required', 'integer', 'min:0'],
            'upload_max_mbps' => ['required', 'integer', 'min:0'],
            'download_min_mbps' => ['required', 'integer', 'min:0'],
            'download_max_mbps' => ['required', 'integer', 'min:0'],
        ];
    }
}
