<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePppProfileRequest extends FormRequest
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
            'harga_modal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'harga_promo' => ['sometimes', 'required', 'numeric', 'min:0'],
            'ppn' => ['sometimes', 'required', 'numeric', 'min:0'],
            'profile_group_id' => ['nullable', 'integer', 'exists:profile_groups,id'],
            'bandwidth_profile_id' => ['nullable', 'integer', 'exists:bandwidth_profiles,id'],
            'parent_queue' => ['nullable', 'string', 'max:200'],
            'masa_aktif' => ['sometimes', 'required', 'integer', 'min:1'],
            'satuan' => ['sometimes', 'required', 'string', 'in:bulan,hari,minggu,jam,menit'],
        ];
    }
}
