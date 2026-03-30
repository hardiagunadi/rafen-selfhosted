<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'shift_definition_id' => ['required', 'integer', 'exists:shift_definitions,id'],
            'schedule_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:scheduled,confirmed,swapped,cancelled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
