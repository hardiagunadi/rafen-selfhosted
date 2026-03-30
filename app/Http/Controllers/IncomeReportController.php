<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinanceReportRequest;
use App\Http\Requests\StoreFinanceExpenseRequest;
use App\Models\FinanceExpense;
use App\Services\FinanceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncomeReportController extends Controller
{
    public function __invoke(FinanceReportRequest $request, FinanceReportService $financeReportService): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validated();
        $reportType = (string) ($validated['report'] ?? 'daily');

        $pageTitle = match ($reportType) {
            'period' => 'Pendapatan Periode',
            'expense' => 'Pengeluaran',
            'profit_loss' => 'Laba Rugi',
            'bhp_uso' => 'Hitung BHP | USO',
            default => 'Pendapatan Harian',
        };

        $filters = [
            'report' => $reportType,
            'tipe_user' => (string) ($validated['tipe_user'] ?? 'semua'),
            'service_type' => (string) ($validated['service_type'] ?? ''),
            'date' => (string) ($validated['date'] ?? now()->toDateString()),
            'start_date' => (string) ($validated['start_date'] ?? now()->startOfMonth()->toDateString()),
            'end_date' => (string) ($validated['end_date'] ?? now()->endOfMonth()->toDateString()),
            'bhp_rate' => (float) ($validated['bhp_rate'] ?? FinanceReportService::DEFAULT_BHP_RATE_PERCENT),
            'uso_rate' => (float) ($validated['uso_rate'] ?? FinanceReportService::DEFAULT_USO_RATE_PERCENT),
            'bad_debt_deduction' => (float) ($validated['bad_debt_deduction'] ?? 0),
            'interconnection_deduction' => (float) ($validated['interconnection_deduction'] ?? 0),
        ];

        return view('super-admin.reports-income', [
            'pageTitle' => $pageTitle,
            'reportType' => $reportType,
            'filters' => $filters,
            'report' => $financeReportService->build($filters),
        ]);
    }

    public function storeExpense(StoreFinanceExpenseRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        FinanceExpense::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        $expenseDate = $request->date('expense_date') ?? now();

        return redirect()
            ->route('super-admin.reports.income', [
                'report' => 'expense',
                'start_date' => $expenseDate->copy()->startOfMonth()->toDateString(),
                'end_date' => $expenseDate->copy()->endOfMonth()->toDateString(),
            ])
            ->with('success', 'Pengeluaran manual berhasil ditambahkan.');
    }
}
