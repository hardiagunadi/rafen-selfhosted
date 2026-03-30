<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileGroupRequest extends FormRequest
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
            'mikrotik_connection_id' => ['nullable', 'integer', 'exists:mikrotik_connections,id'],
            'type' => ['sometimes', 'required', 'string', 'in:hotspot,pppoe'],
            'ip_pool_mode' => ['sometimes', 'required', 'string', 'in:group_only,sql'],
            'ip_pool_name' => ['nullable', 'string', 'max:120'],
            'ip_address' => ['nullable', 'string', 'max:120'],
            'netmask' => ['nullable', 'string', 'max:120'],
            'range_start' => ['nullable', 'string', 'max:120'],
            'range_end' => ['nullable', 'string', 'max:120'],
            'dns_servers' => ['nullable', 'string', 'max:191'],
            'parent_queue' => ['nullable', 'string', 'max:120'],
        ];
    }
}
