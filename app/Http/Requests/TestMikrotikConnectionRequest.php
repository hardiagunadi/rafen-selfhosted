<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestMikrotikConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:191'],
            'api_timeout' => ['nullable', 'integer', 'between:1,120'],
            'api_port' => ['nullable', 'integer', 'between:1,65535'],
            'api_ssl_port' => ['nullable', 'integer', 'between:1,65535'],
            'use_ssl' => ['nullable', 'boolean'],
        ];
    }
}
