<?php

namespace App\Services;

use App\Models\FinanceExpense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public const DEFAULT_BHP_RATE_PERCENT = 0.5;

    public const DEFAULT_USO_RATE_PERCENT = 1.25;

    /**
     * @param  array{
     *      report:string,
     *      tipe_user:string,
     *      service_type:string,
     *      date:string,
     *      start_date:string,
     *      end_date:string,
     *      bhp_rate:float,
     *      uso_rate:float,
     *      bad_debt_deduction:float,
     *      interconnection_deduction:float
     *  }  $filters
     * @return array{
     *      total:float,
     *      currency:string,
     *      items:Collection<int, array<string, mixed>>,
     *      summary:array<string, float>,
     *      period:array{start:string, end:string, label:string}
     *  }
     */
    public function build(array $filters): array
    {
        $reportType = $filters['report'];
        [$periodStart, $periodEnd] = $this->resolvePeriod($filters);

        $incomeItems = $this->collectIncomeItems(
            tipeUser: $filters['tipe_user'],
            serviceType: $filters['service_type'],
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        $grossRevenue = (float) $incomeItems->sum('amount');
        $bhpUso = $this->calculateBhpUso(
            grossRevenue: $grossRevenue,
            bhpRate: (float) $filters['bhp_rate'],
            usoRate: (float) $filters['uso_rate'],
            badDebtDeduction: (float) $filters['bad_debt_deduction'],
            interconnectionDeduction: (float) $filters['interconnection_deduction'],
        );

        $expenseItems = in_array($reportType, ['expense', 'profit_loss'], true)
            ? $this->collectExpenseItems(
                serviceType: $filters['service_type'],
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                bhpUso: $bhpUso,
            )
            : collect();

        $gatewayExpense = (float) $expenseItems->where('expense_type', 'gateway_fee')->sum('amount');
        $manualExpense = (float) $expenseItems->where('expense_type', 'manual')->sum('amount');
        $expenseTotal = (float) $expenseItems->sum('amount');

        $summary = match ($reportType) {
            'expense' => [
                'total_expense' => $expenseTotal,
                'gateway_expense' => $gatewayExpense,
                'manual_expense' => $manualExpense,
                'bhp_amount' => $bhpUso['bhp_amount'],
                'uso_amount' => $bhpUso['uso_amount'],
            ],
            'profit_loss' => [
                'gross_revenue' => $grossRevenue,
                'gateway_expense' => $gatewayExpense,
                'manual_expense' => $manualExpense,
                'bhp_amount' => $bhpUso['bhp_amount'],
                'uso_amount' => $bhpUso['uso_amount'],
                'total_expense' => $expenseTotal,
                'net_profit' => $grossRevenue - $expenseTotal,
            ],
            'bhp_uso' => [
                'gross_revenue' => $grossRevenue,
                'bad_debt_deduction' => $bhpUso['bad_debt_deduction'],
                'interconnection_deduction' => $bhpUso['interconnection_deduction'],
                'deduction_total' => $bhpUso['deduction_total'],
                'revenue_basis' => $bhpUso['revenue_basis'],
                'bhp_rate' => $bhpUso['bhp_rate'],
                'uso_rate' => $bhpUso['uso_rate'],
                'bhp_amount' => $bhpUso['bhp_amount'],
                'uso_amount' => $bhpUso['uso_amount'],
                'total_obligation' => $bhpUso['total_obligation'],
            ],
            default => [
                'total_income' => $grossRevenue,
                'customer_income' => (float) $incomeItems->where('user_type', 'customer')->sum('amount'),
                'voucher_income' => (float) $incomeItems->where('user_type', 'voucher')->sum('amount'),
            ],
        };

        $items = $reportType === 'expense' ? $expenseItems : $incomeItems;
        $mainTotal = match ($reportType) {
            'expense' => $summary['total_expense'],
            'profit_loss' => $summary['net_profit'],
            'bhp_uso' => $summary['total_obligation'],
            default => $summary['total_income'],
        };

        return [
            'total' => $mainTotal,
            'currency' => 'IDR',
            'items' => $items,
            'summary' => $summary,
            'period' => [
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
                'label' => $periodStart->translatedFormat('d M Y').' - '.$periodEnd->translatedFormat('d M Y'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0:Carbon, 1:Carbon}
     */
    private function resolvePeriod(array $filters): array
    {
        if ($filters['report'] === 'daily') {
            $date = Carbon::parse((string) $filters['date']);

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        }

        $startDate = Carbon::parse((string) $filters['start_date'])->startOfDay();
        $endDate = Carbon::parse((string) $filters['end_date'])->endOfDay();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        return [$startDate, $endDate];
    }

    private function collectIncomeItems(
        string $tipeUser,
        string $serviceType,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): Collection {
        $includeCustomerIncome = $tipeUser !== 'voucher' && $serviceType !== 'voucher';
        $includeVoucherIncome = $tipeUser !== 'customer' && ($serviceType === '' || $serviceType === 'voucher');
        $items = collect();

        if ($includeCustomerIncome) {
            $items = $items->concat($this->collectInvoiceIncomeItems($serviceType, $periodStart, $periodEnd));
        }

        if ($includeVoucherIncome) {
            $items = $items->concat($this->collectVoucherIncomeItems($periodStart, $periodEnd));
        }

        return $items
            ->sortByDesc('timestamp')
            ->values()
            ->map(function (array $item): array {
                unset($item['timestamp']);

                return $item;
            });
    }

    private function collectInvoiceIncomeItems(string $serviceType, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $query = Invoice::query()->where('status', 'paid');

        if (in_array($serviceType, ['pppoe', 'hotspot'], true)) {
            $query->where('tipe_service', $serviceType);
        }

        $this->applyDateRange($query, 'paid_at', 'updated_at', $periodStart, $periodEnd);

        return $query->get()->map(function (Invoice $invoice): array {
            $paidTime = $invoice->paid_at ?? $invoice->updated_at ?? $invoice->created_at;

            return [
                'time' => $paidTime?->format('d/m/Y H:i') ?? '-',
                'timestamp' => $paidTime?->timestamp ?? 0,
                'reference' => (string) $invoice->invoice_number,
                'user_type' => 'customer',
                'service' => (string) ($invoice->tipe_service ?: 'pppoe'),
                'category' => 'Invoice',
                'amount' => (float) $invoice->total,
            ];
        });
    }

    private function collectVoucherIncomeItems(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $query = Transaction::query()
            ->where('status', 'paid')
            ->where('type', 'voucher');

        $this->applyDateRange($query, 'paid_at', 'created_at', $periodStart, $periodEnd);

        return $query->get()->map(function (Transaction $transaction): array {
            $paidTime = $transaction->paid_at ?? $transaction->created_at;

            return [
                'time' => $paidTime?->format('d/m/Y H:i') ?? '-',
                'timestamp' => $paidTime?->timestamp ?? 0,
                'reference' => (string) ($transaction->username ?: $transaction->plan_name ?: 'Voucher'),
                'user_type' => 'voucher',
                'service' => 'voucher',
                'category' => 'Voucher',
                'amount' => (float) $transaction->total,
            ];
        });
    }

    private function collectExpenseItems(
        string $serviceType,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $bhpUso,
    ): Collection {
        $gatewayExpense = Payment::query()
            ->where('status', 'paid')
            ->when(in_array($serviceType, ['pppoe', 'hotspot'], true), fn ($query) => $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('tipe_service', $serviceType)))
            ->where('fee', '>', 0);

        $this->applyDateRange($gatewayExpense, 'paid_at', 'created_at', $periodStart, $periodEnd);

        $manualExpense = FinanceExpense::query()
            ->when($serviceType !== '', fn ($query) => $query->where(function ($nestedQuery) use ($serviceType): void {
                $nestedQuery->where('service_type', 'general')
                    ->orWhere('service_type', $serviceType);
            }))
            ->whereBetween('expense_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ]);

        $items = $gatewayExpense->get()->map(function (Payment $payment): array {
            $paidTime = $payment->paid_at ?? $payment->created_at;

            return [
                'time' => $paidTime?->format('d/m/Y H:i') ?? '-',
                'timestamp' => $paidTime?->timestamp ?? 0,
                'category' => 'Biaya Gateway',
                'reference' => (string) ($payment->payment_number ?: $payment->reference ?: '-'),
                'description' => (string) ($payment->payment_channel ?: 'Gateway fee'),
                'expense_type' => 'gateway_fee',
                'amount' => (float) $payment->fee,
            ];
        })->concat(
            $manualExpense->get()->map(function (FinanceExpense $expense): array {
                $expenseTime = $expense->expense_date?->startOfDay();

                return [
                    'time' => $expenseTime?->format('d/m/Y') ?? '-',
                    'timestamp' => $expenseTime?->timestamp ?? 0,
                    'category' => (string) $expense->category,
                    'reference' => (string) ($expense->reference ?: '-'),
                    'description' => (string) ($expense->description ?: '-'),
                    'expense_type' => 'manual',
                    'amount' => (float) $expense->amount,
                ];
            })
        );

        if (($bhpUso['bhp_amount'] ?? 0) > 0) {
            $items->push([
                'time' => $periodEnd->format('d/m/Y H:i'),
                'timestamp' => $periodEnd->timestamp,
                'category' => 'Estimasi BHP',
                'reference' => '-',
                'description' => 'Kewajiban BHP',
                'expense_type' => 'bhp',
                'amount' => (float) $bhpUso['bhp_amount'],
            ]);
        }

        if (($bhpUso['uso_amount'] ?? 0) > 0) {
            $items->push([
                'time' => $periodEnd->format('d/m/Y H:i'),
                'timestamp' => $periodEnd->timestamp,
                'category' => 'Estimasi USO',
                'reference' => '-',
                'description' => 'Kewajiban USO',
                'expense_type' => 'uso',
                'amount' => (float) $bhpUso['uso_amount'],
            ]);
        }

        return $items
            ->sortByDesc('timestamp')
            ->values()
            ->map(function (array $item): array {
                unset($item['timestamp']);

                return $item;
            });
    }

    /**
     * @return array{
     *      bhp_rate:float,
     *      uso_rate:float,
     *      bad_debt_deduction:float,
     *      interconnection_deduction:float,
     *      deduction_total:float,
     *      revenue_basis:float,
     *      bhp_amount:float,
     *      uso_amount:float,
     *      total_obligation:float
     * }
     */
    private function calculateBhpUso(
        float $grossRevenue,
        float $bhpRate,
        float $usoRate,
        float $badDebtDeduction,
        float $interconnectionDeduction,
    ): array {
        $badDebtDeduction = max(0, $badDebtDeduction);
        $interconnectionDeduction = max(0, $interconnectionDeduction);
        $deductionTotal = $badDebtDeduction + $interconnectionDeduction;
        $revenueBasis = max(0, $grossRevenue - $deductionTotal);
        $bhpAmount = round($revenueBasis * ($bhpRate / 100), 2);
        $usoAmount = round($revenueBasis * ($usoRate / 100), 2);

        return [
            'bhp_rate' => $bhpRate,
            'uso_rate' => $usoRate,
            'bad_debt_deduction' => $badDebtDeduction,
            'interconnection_deduction' => $interconnectionDeduction,
            'deduction_total' => $deductionTotal,
            'revenue_basis' => $revenueBasis,
            'bhp_amount' => $bhpAmount,
            'uso_amount' => $usoAmount,
            'total_obligation' => $bhpAmount + $usoAmount,
        ];
    }

    private function applyDateRange(
        Builder $query,
        string $paidAtColumn,
        string $fallbackColumn,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): void {
        $query->where(function (Builder $builder) use ($paidAtColumn, $fallbackColumn, $periodStart, $periodEnd): void {
            $builder->whereBetween($paidAtColumn, [$periodStart, $periodEnd])
                ->orWhere(function (Builder $fallbackQuery) use ($paidAtColumn, $fallbackColumn, $periodStart, $periodEnd): void {
                    $fallbackQuery->whereNull($paidAtColumn)
                        ->whereBetween($fallbackColumn, [$periodStart, $periodEnd]);
                });
        });
    }
}
