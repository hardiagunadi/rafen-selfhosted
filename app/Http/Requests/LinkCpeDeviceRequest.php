<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkCpeDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'radius_account_id' => ['required', 'integer', 'exists:radius_accounts,id'],
            'device_id' => ['required', 'string', 'max:191'],
        ];
    }
}
