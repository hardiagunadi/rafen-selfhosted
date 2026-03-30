<?php

use App\Models\Invoice;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\TeknisiSetoran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows teknisi setoran pages for allowed roles and blocks unauthorized users', function () {
    $admin = User::factory()->superAdmin()->create();
    $teknisi = User::factory()->create([
        'role' => User::ROLE_TEKNISI,
    ]);
    $guestRole = User::factory()->create([
        'role' => User::ROLE_NOC,
    ]);

    $this->actingAs($admin)
        ->get(route('teknisi-setoran.index'))
        ->assertSuccessful()
        ->assertSee('Rekonsiliasi Nota Teknisi');

    $this->actingAs($teknisi)
        ->get(route('teknisi-setoran.index'))
        ->assertSuccessful()
        ->assertSee('Rekonsiliasi Nota Teknisi');

    $this->actingAs($guestRole)
        ->get(route('teknisi-setoran.index'))
        ->assertForbidden();
});

it('creates submits and verifies teknisi setoran from paid invoices', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $keuangan = User::factory()->create([
        'role' => User::ROLE_KEUANGAN,
    ]);
    $teknisi = User::factory()->create([
        'role' => User::ROLE_TEKNISI,
        'phone' => '0811111111',
    ]);
    $profileGroup = ProfileGroup::factory()->create();
    $pppProfile = PppProfile::factory()->create([
        'profile_group_id' => $profileGroup->id,
    ]);
    $pppUser = PppUser::factory()->create([
        'ppp_profile_id' => $pppProfile->id,
        'profile_group_id' => $profileGroup->id,
        'customer_name' => 'Pelanggan Setoran',
        'customer_id' => '000000060001',
    ]);

    Invoice::factory()->create([
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppProfile->id,
        'customer_name' => $pppUser->customer_name,
        'customer_id' => $pppUser->customer_id,
        'total' => 150000,
        'status' => 'paid',
        'payment_method' => 'cash',
        'cash_received' => 150000,
        'transfer_amount' => 0,
        'paid_by' => $teknisi->id,
        'paid_at' => '2026-03-30 10:00:00',
        'due_date' => '2026-03-30',
    ]);

    $this->actingAs($superAdmin)
        ->post(route('teknisi-setoran.store'), [
            'teknisi_id' => $teknisi->id,
            'period_date' => '2026-03-30',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $setoran = TeknisiSetoran::query()->firstOrFail();

    expect($setoran->total_invoices)->toBe(1)
        ->and((float) $setoran->total_tagihan)->toBe(150000.0)
        ->and((float) $setoran->total_cash)->toBe(150000.0)
        ->and($setoran->status)->toBe('draft');

    $this->actingAs($teknisi)
        ->post(route('teknisi-setoran.submit', $setoran))
        ->assertRedirect(route('teknisi-setoran.show', $setoran))
        ->assertSessionHas('success');

    $setoran->refresh();

    expect($setoran->status)->toBe('submitted')
        ->and($setoran->submitted_at)->not->toBeNull();

    $this->actingAs($keuangan)
        ->post(route('teknisi-setoran.verify', $setoran), [
            'notes' => 'Uang diterima lengkap.',
        ])
        ->assertRedirect(route('teknisi-setoran.show', $setoran))
        ->assertSessionHas('success');

    $setoran->refresh();

    expect($setoran->status)->toBe('verified')
        ->and($setoran->verified_by)->toBe($keuangan->id)
        ->and($setoran->notes)->toBe('Uang diterima lengkap.');
});
