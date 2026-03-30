<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftSwapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_schedule_id' => ['required', 'integer', 'exists:shift_schedules,id'],
            'to_schedule_id' => ['nullable', 'integer', 'different:from_schedule_id', 'exists:shift_schedules,id'],
            'target_id' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
