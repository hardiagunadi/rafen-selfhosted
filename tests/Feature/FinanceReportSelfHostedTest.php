<?php

use App\Models\FinanceExpense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PppUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows the income report page for a super admin and blocks non admin users', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.reports.income'))
        ->assertSuccessful()
        ->assertSee('Pendapatan Harian');

    $this->actingAs($user)
        ->get(route('super-admin.reports.income'))
        ->assertForbidden();
});

it('calculates daily income from invoices and voucher transactions', function () {
    $admin = User::factory()->superAdmin()->create();
    $pppUser = PppUser::factory()->create();

    Invoice::query()->create([
        'invoice_number' => 'INV-RPT-001',
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppUser->ppp_profile_id,
        'customer_id' => 'MX-001',
        'customer_name' => 'Pelanggan A',
        'tipe_service' => 'pppoe',
        'paket_langganan' => 'Paket A',
        'total' => 100000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    Transaction::query()->create([
        'type' => 'voucher',
        'username' => 'VC001',
        'plan_name' => 'Voucher 5K',
        'total' => 50000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.reports.income', [
            'report' => 'daily',
            'date' => now()->toDateString(),
            'tipe_user' => 'semua',
        ]))
        ->assertSuccessful()
        ->assertViewHas('report', function (array $report): bool {
            return (float) ($report['summary']['total_income'] ?? 0) === 150000.0
                && (float) ($report['summary']['customer_income'] ?? 0) === 100000.0
                && (float) ($report['summary']['voucher_income'] ?? 0) === 50000.0
                && $report['items']->count() === 2;
        });
});

it('stores manual expenses and calculates profit loss with gateway fee and bhp uso', function () {
    $admin = User::factory()->superAdmin()->create();
    $pppUser = PppUser::factory()->create();

    $invoice = Invoice::query()->create([
        'invoice_number' => 'INV-RPT-PL-001',
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppUser->ppp_profile_id,
        'customer_id' => 'MX-002',
        'customer_name' => 'Pelanggan B',
        'tipe_service' => 'pppoe',
        'paket_langganan' => 'Paket B',
        'total' => 800000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    Payment::query()->create([
        'payment_number' => 'PAY-RPT-001',
        'payment_type' => 'invoice',
        'invoice_id' => $invoice->id,
        'payment_channel' => 'manual_transfer',
        'payment_method' => 'transfer',
        'amount' => 800000,
        'fee' => 10000,
        'total_amount' => 810000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('super-admin.reports.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'category' => 'Gaji Teknisi',
            'service_type' => 'pppoe',
            'amount' => 200000,
            'payment_method' => 'transfer',
            'reference' => 'EXP-001',
            'description' => 'Pengeluaran teknisi bulanan',
        ])
        ->assertRedirect();

    expect(FinanceExpense::query()->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('super-admin.reports.income', [
            'report' => 'profit_loss',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'bhp_rate' => 0.5,
            'uso_rate' => 1.25,
        ]))
        ->assertSuccessful()
        ->assertViewHas('report', function (array $report): bool {
            return (float) ($report['summary']['gross_revenue'] ?? 0) === 800000.0
                && (float) ($report['summary']['gateway_expense'] ?? 0) === 10000.0
                && (float) ($report['summary']['manual_expense'] ?? 0) === 200000.0
                && (float) ($report['summary']['bhp_amount'] ?? 0) === 4000.0
                && (float) ($report['summary']['uso_amount'] ?? 0) === 10000.0
                && (float) ($report['summary']['total_expense'] ?? 0) === 224000.0
                && (float) ($report['summary']['net_profit'] ?? 0) === 576000.0;
        });
});
