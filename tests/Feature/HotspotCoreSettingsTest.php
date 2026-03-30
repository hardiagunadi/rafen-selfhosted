<?php

use App\Models\BandwidthProfile;
use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\MikrotikConnection;
use App\Models\ProfileGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows hotspot core settings pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $bandwidthProfile = BandwidthProfile::factory()->create(['name' => 'BW Hotspot']);
    $profileGroup = ProfileGroup::factory()->create(['name' => 'GROUP HOTSPOT']);
    $hotspotProfile = HotspotProfile::factory()->create([
        'name' => 'Voucher Harian',
        'bandwidth_profile_id' => $bandwidthProfile->id,
        'profile_group_id' => $profileGroup->id,
    ]);
    HotspotUser::factory()->create([
        'customer_name' => 'Dewi Hotspot',
        'hotspot_profile_id' => $hotspotProfile->id,
        'profile_group_id' => $profileGroup->id,
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.settings.hotspot-profiles.index'))
        ->assertSuccessful()
        ->assertSee('Paket Hotspot')
        ->assertSee('Voucher Harian');

    $this->actingAs($user)
        ->get(route('super-admin.settings.hotspot-users.index'))
        ->assertSuccessful()
        ->assertSee('Pelanggan Hotspot')
        ->assertSee('Dewi Hotspot');
});

it('blocks non super admin users from hotspot core settings pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.hotspot-users.index'))
        ->assertForbidden();
});

it('creates updates and deletes hotspot core resources', function () {
    $user = User::factory()->superAdmin()->create();
    $mikrotikConnection = MikrotikConnection::factory()->create();

    $profileGroup = ProfileGroup::factory()->create([
        'mikrotik_connection_id' => $mikrotikConnection->id,
        'type' => 'hotspot',
    ]);
    $bandwidthProfile = BandwidthProfile::factory()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.hotspot-profiles.store'), [
            'name' => 'Voucher Bulanan',
            'harga_jual' => 50000,
            'harga_promo' => 45000,
            'ppn' => 11,
            'bandwidth_profile_id' => $bandwidthProfile->id,
            'profile_type' => 'unlimited',
            'masa_aktif_value' => 30,
            'masa_aktif_unit' => 'hari',
            'profile_group_id' => $profileGroup->id,
            'parent_queue' => 'ROOT-HS',
            'shared_users' => 2,
            'prioritas' => 'prioritas1',
        ])
        ->assertRedirect(route('super-admin.settings.hotspot-profiles.index'))
        ->assertSessionHas('success');

    $hotspotProfile = HotspotProfile::query()->first();

    $this->actingAs($user)
        ->post(route('super-admin.settings.hotspot-users.store'), [
            'status_registrasi' => 'aktif',
            'tipe_pembayaran' => 'prepaid',
            'status_bayar' => 'belum_bayar',
            'status_akun' => 'enable',
            'hotspot_profile_id' => $hotspotProfile->id,
            'aksi_jatuh_tempo' => 'isolir',
            'customer_name' => 'Hotspot Baru',
            'email' => 'hotspot@example.test',
            'metode_login' => 'username_equals_password',
            'username' => 'hs-baru',
        ])
        ->assertRedirect(route('super-admin.settings.hotspot-users.index'))
        ->assertSessionHas('success');

    $hotspotUser = HotspotUser::query()->first();

    expect($hotspotUser)->not->toBeNull()
        ->and($hotspotUser->customer_id)->toMatch('/^MX-\d{6}$/')
        ->and($hotspotUser->hotspot_password)->toBe('hs-baru')
        ->and($hotspotUser->profile_group_id)->toBe($profileGroup->id);

    $this->actingAs($user)
        ->get(route('super-admin.settings.hotspot-users.customer-id'))
        ->assertSuccessful()
        ->assertJson([
            'customer_id' => 'MX-000002',
        ]);

    $this->actingAs($user)
        ->put(route('super-admin.settings.hotspot-users.update', $hotspotUser), [
            'status_registrasi' => 'on_process',
            'tipe_pembayaran' => 'postpaid',
            'status_bayar' => 'sudah_bayar',
            'status_akun' => 'disable',
            'hotspot_profile_id' => $hotspotProfile->id,
            'aksi_jatuh_tempo' => 'tetap_terhubung',
            'profile_group_id' => $profileGroup->id,
            'customer_id' => $hotspotUser->customer_id,
            'customer_name' => 'Hotspot Update',
            'nomor_hp' => '6281234567111',
            'email' => 'hotspot-update@example.test',
            'metode_login' => 'username_password',
            'username' => 'hs-update',
            'hotspot_password' => 'rahasia-hs',
            'biaya_instalasi' => 25000,
        ])
        ->assertRedirect(route('super-admin.settings.hotspot-users.index'))
        ->assertSessionHas('success');

    expect($hotspotUser->fresh()->customer_name)->toBe('Hotspot Update')
        ->and($hotspotUser->fresh()->status_akun)->toBe('disable')
        ->and($hotspotUser->fresh()->hotspot_password)->toBe('rahasia-hs');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.hotspot-users.destroy', $hotspotUser))
        ->assertRedirect(route('super-admin.settings.hotspot-users.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.hotspot-profiles.destroy', $hotspotProfile))
        ->assertRedirect(route('super-admin.settings.hotspot-profiles.index'))
        ->assertSessionHas('success');
});
