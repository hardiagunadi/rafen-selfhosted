<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRadiusAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mikrotik_connection_id' => ['nullable', 'integer', 'exists:mikrotik_connections,id'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('radius_accounts', 'username')->ignore($this->route('radiusAccount')?->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'max:191'],
            'service' => ['sometimes', 'required', 'string', 'in:pppoe,hotspot'],
            'ipv4_address' => [
                Rule::requiredIf(fn () => ($this->input('service') ?? $this->route('radiusAccount')?->service) === 'pppoe'),
                'nullable',
                'ipv4',
            ],
            'rate_limit' => ['nullable', 'string', 'max:120'],
            'profile' => ['nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
