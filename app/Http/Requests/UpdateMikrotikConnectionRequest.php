<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMikrotikConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'host' => ['sometimes', 'required', 'string', 'max:191'],
            'api_port' => ['sometimes', 'required', 'integer', 'between:1,65535'],
            'api_ssl_port' => ['sometimes', 'required', 'integer', 'between:1,65535'],
            'use_ssl' => ['sometimes', 'boolean'],
            'username' => ['sometimes', 'required', 'string', 'max:120'],
            'password' => ['sometimes', 'required', 'string', 'max:191'],
            'radius_secret' => ['nullable', 'string', 'max:191'],
            'ros_version' => ['sometimes', 'required', 'string', 'in:6,7,auto'],
            'api_timeout' => ['sometimes', 'required', 'integer', 'between:1,120'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'auth_port' => ['sometimes', 'required', 'integer', 'between:1,65535'],
            'acct_port' => ['sometimes', 'required', 'integer', 'between:1,65535'],
            'timezone' => ['sometimes', 'required', 'string', 'max:120'],
        ];
    }
}
