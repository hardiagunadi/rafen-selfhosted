<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMikrotikConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'host' => ['required', 'string', 'max:191'],
            'api_port' => ['required', 'integer', 'between:1,65535'],
            'api_ssl_port' => ['required', 'integer', 'between:1,65535'],
            'use_ssl' => ['sometimes', 'boolean'],
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:191'],
            'radius_secret' => ['nullable', 'string', 'max:191'],
            'ros_version' => ['required', 'string', 'in:6,7,auto'],
            'api_timeout' => ['required', 'integer', 'between:1,120'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'auth_port' => ['required', 'integer', 'between:1,65535'],
            'acct_port' => ['required', 'integer', 'between:1,65535'],
            'timezone' => ['required', 'string', 'max:120'],
        ];
    }
}
