<?php

use App\Models\BandwidthProfile;
use App\Models\MikrotikConnection;
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

it('shows ppp core settings pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $bandwidthProfile = BandwidthProfile::factory()->create(['name' => 'BW 20M']);
    $profileGroup = ProfileGroup::factory()->create(['name' => 'GROUP PPP']);
    $pppProfile = PppProfile::factory()->create([
        'name' => 'Paket Gold',
        'bandwidth_profile_id' => $bandwidthProfile->id,
        'profile_group_id' => $profileGroup->id,
    ]);
    PppUser::factory()->create([
        'customer_name' => 'Budi Pelanggan',
        'ppp_profile_id' => $pppProfile->id,
        'profile_group_id' => $profileGroup->id,
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.settings.bandwidth-profiles.index'))
        ->assertSuccessful()
        ->assertSee('Profil Bandwidth')
        ->assertSee('BW 20M');

    $this->actingAs($user)
        ->get(route('super-admin.settings.profile-groups.index'))
        ->assertSuccessful()
        ->assertSee('Profile Group')
        ->assertSee('GROUP PPP');

    $this->actingAs($user)
        ->get(route('super-admin.settings.ppp-profiles.index'))
        ->assertSuccessful()
        ->assertSee('Paket PPP')
        ->assertSee('Paket Gold');

    $this->actingAs($user)
        ->get(route('super-admin.settings.ppp-users.index'))
        ->assertSuccessful()
        ->assertSee('Pelanggan PPP')
        ->assertSee('Budi Pelanggan');
});

it('blocks non super admin users from ppp core settings pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.ppp-users.index'))
        ->assertForbidden();
});

it('creates updates and deletes ppp core resources', function () {
    $user = User::factory()->superAdmin()->create();
    $mikrotikConnection = MikrotikConnection::factory()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.bandwidth-profiles.store'), [
            'name' => 'BW 50M',
            'upload_min_mbps' => 10,
            'upload_max_mbps' => 50,
            'download_min_mbps' => 10,
            'download_max_mbps' => 50,
        ])
        ->assertRedirect(route('super-admin.settings.bandwidth-profiles.index'))
        ->assertSessionHas('success');

    $bandwidthProfile = BandwidthProfile::query()->first();

    $this->actingAs($user)
        ->post(route('super-admin.settings.profile-groups.store'), [
            'name' => 'PG-1',
            'mikrotik_connection_id' => $mikrotikConnection->id,
            'type' => 'pppoe',
            'ip_pool_mode' => 'group_only',
            'ip_pool_name' => 'POOL-1',
            'parent_queue' => 'ROOT-PPP',
        ])
        ->assertRedirect(route('super-admin.settings.profile-groups.index'))
        ->assertSessionHas('success');

    $profileGroup = ProfileGroup::query()->first();

    $this->actingAs($user)
        ->post(route('super-admin.settings.ppp-profiles.store'), [
            'name' => 'Paket Platinum',
            'harga_modal' => 100000,
            'harga_promo' => 150000,
            'ppn' => 11,
            'profile_group_id' => $profileGroup->id,
            'bandwidth_profile_id' => $bandwidthProfile->id,
            'parent_queue' => 'ROOT-PPP',
            'masa_aktif' => 1,
            'satuan' => 'bulan',
        ])
        ->assertRedirect(route('super-admin.settings.ppp-profiles.index'))
        ->assertSessionHas('success');

    $pppProfile = PppProfile::query()->first();

    $this->actingAs($user)
        ->post(route('super-admin.settings.ppp-users.store'), [
            'status_registrasi' => 'aktif',
            'tipe_pembayaran' => 'prepaid',
            'status_bayar' => 'belum_bayar',
            'status_akun' => 'enable',
            'ppp_profile_id' => $pppProfile->id,
            'tipe_service' => 'pppoe',
            'tagihkan_ppn' => '1',
            'aksi_jatuh_tempo' => 'isolir',
            'tipe_ip' => 'dhcp',
            'profile_group_id' => $profileGroup->id,
            'customer_name' => 'Andi Baru',
            'nomor_hp' => '6281234567000',
            'email' => 'andi@example.test',
            'metode_login' => 'username_equals_password',
            'username' => 'andi-ppp',
        ])
        ->assertRedirect(route('super-admin.settings.ppp-users.index'))
        ->assertSessionHas('success');

    $pppUser = PppUser::query()->first();

    expect($pppUser)->not->toBeNull()
        ->and($pppUser->customer_id)->toMatch('/^\d{12}$/')
        ->and($pppUser->ppp_password)->toBe('andi-ppp')
        ->and($pppUser->password_clientarea)->toBe('andi-ppp');

    $this->actingAs($user)
        ->put(route('super-admin.settings.ppp-users.update', $pppUser), [
            'status_registrasi' => 'on_process',
            'tipe_pembayaran' => 'postpaid',
            'status_bayar' => 'sudah_bayar',
            'status_akun' => 'isolir',
            'ppp_profile_id' => $pppProfile->id,
            'tipe_service' => 'pppoe',
            'aksi_jatuh_tempo' => 'tetap_terhubung',
            'tipe_ip' => 'static',
            'profile_group_id' => $profileGroup->id,
            'customer_id' => $pppUser->customer_id,
            'customer_name' => 'Andi Update',
            'nomor_hp' => '6281234567000',
            'email' => 'andi-update@example.test',
            'metode_login' => 'username_password',
            'username' => 'andi-update',
            'ppp_password' => 'rahasia123',
            'password_clientarea' => 'portal123',
            'ip_static' => '10.10.10.2',
        ])
        ->assertRedirect(route('super-admin.settings.ppp-users.index'))
        ->assertSessionHas('success');

    expect($pppUser->fresh()->customer_name)->toBe('Andi Update')
        ->and($pppUser->fresh()->status_akun)->toBe('isolir')
        ->and($pppUser->fresh()->tipe_ip)->toBe('static')
        ->and($pppUser->fresh()->ip_static)->toBe('10.10.10.2');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.ppp-users.destroy', $pppUser))
        ->assertRedirect(route('super-admin.settings.ppp-users.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.ppp-profiles.destroy', $pppProfile))
        ->assertRedirect(route('super-admin.settings.ppp-profiles.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.profile-groups.destroy', $profileGroup))
        ->assertRedirect(route('super-admin.settings.profile-groups.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.bandwidth-profiles.destroy', $bandwidthProfile))
        ->assertRedirect(route('super-admin.settings.bandwidth-profiles.index'))
        ->assertSessionHas('success');
});
