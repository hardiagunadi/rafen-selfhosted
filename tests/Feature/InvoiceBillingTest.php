<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows invoice and payment pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $profileGroup = ProfileGroup::factory()->create();
    $pppProfile = PppProfile::factory()->create([
        'name' => 'Paket Billing',
        'profile_group_id' => $profileGroup->id,
    ]);
    $pppUser = PppUser::factory()->create([
        'customer_name' => 'Budi Billing',
        'customer_id' => '000000010001',
        'ppp_profile_id' => $pppProfile->id,
        'profile_group_id' => $profileGroup->id,
    ]);
    $invoice = Invoice::factory()->create([
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppProfile->id,
        'customer_name' => $pppUser->customer_name,
        'customer_id' => $pppUser->customer_id,
        'paket_langganan' => $pppProfile->name,
        'total' => 111000,
        'due_date' => '2026-03-10',
        'status' => 'unpaid',
    ]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 111000,
        'total_amount' => 111000,
    ]);
    $invoice->update(['payment_id' => $payment->id]);

    $this->actingAs($user)
        ->get(route('super-admin.invoices.index'))
        ->assertSuccessful()
        ->assertSee('Data Tagihan')
        ->assertSee('Rekap Invoice Terhutang per Bulan')
        ->assertSee('Budi Billing');

    $this->actingAs($user)
        ->get(route('super-admin.payments.index'))
        ->assertSuccessful()
        ->assertSee('Pembayaran')
        ->assertSee($payment->payment_number);
});

it('blocks non super admin users from billing pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.invoices.index'))
        ->assertForbidden();
});

it('creates pays and renews invoices in single tenant mode', function () {
    $user = User::factory()->superAdmin()->create();
    $profileGroup = ProfileGroup::factory()->create();
    $pppProfile = PppProfile::factory()->create([
        'name' => 'Paket Platinum Billing',
        'harga_promo' => 150000,
        'ppn' => 11,
        'masa_aktif' => 1,
        'satuan' => 'bulan',
        'profile_group_id' => $profileGroup->id,
    ]);
    $pppUser = PppUser::factory()->create([
        'status_registrasi' => 'on_process',
        'status_akun' => 'isolir',
        'status_bayar' => 'belum_bayar',
        'tagihkan_ppn' => true,
        'customer_id' => '000000020001',
        'customer_name' => 'Andi Billing',
        'ppp_profile_id' => $pppProfile->id,
        'profile_group_id' => $profileGroup->id,
        'jatuh_tempo' => '2026-03-10',
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.invoices.store'), [
            'ppp_user_id' => $pppUser->id,
            'due_date' => '2026-03-10',
        ])
        ->assertRedirect(route('super-admin.invoices.index'))
        ->assertSessionHas('success');

    $invoice = Invoice::query()->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_number)->toMatch('/^INV-\d{10}$/')
        ->and((float) $invoice->harga_dasar)->toBe(150000.0)
        ->and((float) $invoice->ppn_amount)->toBe(16500.0)
        ->and((float) $invoice->total)->toBe(166500.0);

    $this->actingAs($user)
        ->post(route('super-admin.invoices.pay', $invoice), [
            'payment_method' => 'cash',
            'cash_received' => 166500,
            'payment_note' => 'Lunas tunai',
        ])
        ->assertRedirect(route('super-admin.invoices.show', $invoice))
        ->assertSessionHas('success');

    $invoice->refresh();
    $pppUser->refresh();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->payment_method)->toBe('cash')
        ->and($invoice->paid_by)->toBe($user->id)
        ->and($pppUser->status_registrasi)->toBe('aktif')
        ->and($pppUser->status_bayar)->toBe('sudah_bayar')
        ->and($pppUser->status_akun)->toBe('enable');

    expect(Payment::query()->count())->toBe(1);

    $renewInvoice = Invoice::factory()->create([
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppProfile->id,
        'customer_id' => $pppUser->customer_id,
        'customer_name' => $pppUser->customer_name,
        'tipe_service' => 'pppoe',
        'paket_langganan' => $pppProfile->name,
        'total' => 166500,
        'due_date' => '2026-03-15',
        'status' => 'unpaid',
    ]);

    $pppUser->update([
        'status_akun' => 'isolir',
        'status_bayar' => 'belum_bayar',
        'jatuh_tempo' => '2026-03-15',
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.invoices.renew', $renewInvoice))
        ->assertRedirect(route('super-admin.invoices.show', $renewInvoice))
        ->assertSessionHas('success');

    $renewInvoice->refresh();
    $pppUser->refresh();

    expect($renewInvoice->status)->toBe('unpaid')
        ->and($renewInvoice->renewed_without_payment)->toBeTrue()
        ->and($renewInvoice->due_date?->format('Y-m-d'))->toBe('2026-04-30')
        ->and($pppUser->status_bayar)->toBe('belum_bayar')
        ->and($pppUser->status_akun)->toBe('enable')
        ->and($pppUser->jatuh_tempo?->format('Y-m-d'))->toBe('2026-04-30');
});
