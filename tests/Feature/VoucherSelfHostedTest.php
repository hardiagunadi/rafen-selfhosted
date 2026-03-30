<?php

use App\Models\HotspotProfile;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows voucher page for super admin and blocks non admin users', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.vouchers.index'))
        ->assertSuccessful()
        ->assertSee('Voucher Internal');

    $this->actingAs($user)
        ->get(route('super-admin.vouchers.index'))
        ->assertForbidden();
});

it('creates prints and deletes voucher batches in single tenant mode', function () {
    $admin = User::factory()->superAdmin()->create();
    $hotspotProfile = HotspotProfile::factory()->create([
        'name' => 'Voucher Harian',
    ]);

    $this->actingAs($admin)
        ->post(route('super-admin.vouchers.store'), [
            'hotspot_profile_id' => $hotspotProfile->id,
            'batch_name' => 'Batch Maret',
            'jumlah' => 3,
        ])
        ->assertRedirect(route('super-admin.vouchers.index'))
        ->assertSessionHas('success');

    $vouchers = Voucher::query()->orderBy('code')->get();

    expect($vouchers)->toHaveCount(3)
        ->and($vouchers->pluck('code')->unique())->toHaveCount(3)
        ->and($vouchers->every(fn (Voucher $voucher): bool => $voucher->username === $voucher->code && $voucher->password === $voucher->code))->toBeTrue()
        ->and($vouchers->every(fn (Voucher $voucher): bool => $voucher->profile_group_id === $hotspotProfile->profile_group_id))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('super-admin.vouchers.print', ['batch' => 'Batch Maret']))
        ->assertSuccessful()
        ->assertSee('Batch Maret')
        ->assertSee($vouchers->first()->code);

    $usedVoucher = $vouchers->last();
    $usedVoucher->update([
        'status' => 'used',
        'used_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('super-admin.vouchers.destroy', $usedVoucher))
        ->assertRedirect(route('super-admin.vouchers.index'))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('super-admin.vouchers.bulk-destroy'), [
            'ids' => $vouchers->pluck('id')->all(),
        ])
        ->assertRedirect(route('super-admin.vouchers.index'))
        ->assertSessionHas('success');

    expect(Voucher::query()->count())->toBe(1)
        ->and($usedVoucher->fresh())->not->toBeNull()
        ->and($usedVoucher->fresh()?->status)->toBe('used');
});
