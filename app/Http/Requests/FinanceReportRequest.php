<?php

namespace App\Http\Requests;

use App\Services\FinanceReportService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report' => ['required', 'string', 'in:daily,period,expense,profit_loss,bhp_uso'],
            'tipe_user' => ['required', 'string', 'in:semua,customer,voucher'],
            'service_type' => ['nullable', 'string', 'in:pppoe,hotspot,voucher'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'bhp_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'uso_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bad_debt_deduction' => ['nullable', 'numeric', 'min:0'],
            'interconnection_deduction' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report' => $this->input('report', 'daily'),
            'tipe_user' => $this->input('tipe_user', 'semua'),
            'service_type' => trim((string) $this->input('service_type', '')) ?: null,
            'date' => $this->input('date', now()->toDateString()),
            'start_date' => $this->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $this->input('end_date', now()->endOfMonth()->toDateString()),
            'bhp_rate' => $this->input('bhp_rate', FinanceReportService::DEFAULT_BHP_RATE_PERCENT),
            'uso_rate' => $this->input('uso_rate', FinanceReportService::DEFAULT_USO_RATE_PERCENT),
            'bad_debt_deduction' => $this->input('bad_debt_deduction', 0),
            'interconnection_deduction' => $this->input('interconnection_deduction', 0),
        ]);
    }
}
