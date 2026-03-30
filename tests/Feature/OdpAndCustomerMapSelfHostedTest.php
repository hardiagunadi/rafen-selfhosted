<?php

use App\Models\Odp;
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

it('shows odp inventory and customer map pages for super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.odps.index'))
        ->assertSuccessful()
        ->assertSee('Inventaris ODP');

    $this->actingAs($admin)
        ->get(route('super-admin.customer-map.index'))
        ->assertSuccessful()
        ->assertSee('Peta ODP dan Pelanggan PPP');
});

it('creates updates codes and protects odps linked to ppp customers', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->getJson(route('super-admin.odps.generate-code', [
            'area_name' => 'Purwokerto Timur',
        ]))
        ->assertSuccessful()
        ->assertJsonPath('prefix', 'PURWOKERTO-TIMUR');

    $this->actingAs($admin)
        ->post(route('super-admin.odps.store'), [
            'code' => 'ODP-PWT-001',
            'name' => 'ODP Purwokerto Timur 1',
            'area' => 'Purwokerto Timur',
            'latitude' => '-7.4301000',
            'longitude' => '109.2478000',
            'capacity_ports' => 16,
            'status' => 'active',
            'notes' => 'Node utama timur',
        ])
        ->assertRedirect(route('super-admin.odps.index'))
        ->assertSessionHas('success');

    $odp = Odp::query()->first();

    expect($odp)->not->toBeNull()
        ->and($odp->code)->toBe('ODP-PWT-001');

    $profileGroup = ProfileGroup::factory()->create();
    $pppProfile = PppProfile::factory()->create([
        'profile_group_id' => $profileGroup->id,
    ]);

    $this->actingAs($admin)
        ->post(route('super-admin.settings.ppp-users.store'), [
            'status_registrasi' => 'aktif',
            'tipe_pembayaran' => 'prepaid',
            'status_bayar' => 'belum_bayar',
            'status_akun' => 'enable',
            'ppp_profile_id' => $pppProfile->id,
            'tipe_service' => 'pppoe',
            'aksi_jatuh_tempo' => 'isolir',
            'tipe_ip' => 'dhcp',
            'profile_group_id' => $profileGroup->id,
            'odp_id' => $odp->id,
            'customer_id' => '000000090001',
            'customer_name' => 'Pelanggan ODP Map',
            'nomor_hp' => '6281333333333',
            'latitude' => '-7.4311000',
            'longitude' => '109.2488000',
            'location_accuracy_m' => '8.50',
            'location_capture_method' => 'gps',
            'location_captured_at' => now()->format('Y-m-d H:i:s'),
            'metode_login' => 'username_password',
            'username' => 'pelanggan.odp',
            'ppp_password' => 'secret123',
            'password_clientarea' => 'secret123',
        ])
        ->assertRedirect(route('super-admin.settings.ppp-users.index'))
        ->assertSessionHas('success');

    $pppUser = PppUser::query()->first();

    expect($pppUser->odp_id)->toBe($odp->id)
        ->and($pppUser->odp_pop)->toBe('ODP-PWT-001');

    $this->actingAs($admin)
        ->get(route('super-admin.customer-map.index'))
        ->assertSuccessful()
        ->assertSee('ODP-PWT-001')
        ->assertSee('Pelanggan ODP Map');

    $this->actingAs($admin)
        ->delete(route('super-admin.odps.destroy', $odp))
        ->assertRedirect(route('super-admin.odps.index'))
        ->assertSessionHas('error');

    $pppUser->delete();

    $this->actingAs($admin)
        ->delete(route('super-admin.odps.destroy', $odp))
        ->assertRedirect(route('super-admin.odps.index'))
        ->assertSessionHas('success');

    expect(Odp::query()->count())->toBe(0);
});
