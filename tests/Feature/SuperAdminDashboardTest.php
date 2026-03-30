<?php

use App\Models\CpeDevice;
use App\Models\MikrotikConnection;
use App\Models\OltConnection;
use App\Models\RadiusAccount;
use App\Models\RadiusNas;
use App\Models\SystemLicense;
use App\Models\User;
use App\Models\WaMultiSessionDevice;
use App\Models\WgPeer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects the root path to the super admin dashboard', function () {
    $this->get('/')
        ->assertRedirect('/super-admin/dashboard');
});

it('shows the self-hosted dashboard summary for super admin', function () {
    config([
        'license.self_hosted_enabled' => true,
        'license.enforce' => false,
    ]);

    $user = User::factory()->superAdmin()->create();

    SystemLicense::query()->create([
        'status' => 'active',
        'license_id' => 'LIC-SH-001',
        'customer_name' => 'PT Rafen Jaya',
        'instance_name' => 'Self-Hosted Jakarta',
        'fingerprint' => 'fp-demo',
        'issued_at' => now()->subMonth()->toDateString(),
        'expires_at' => now()->addMonth()->toDateString(),
        'support_until' => now()->addMonths(2)->toDateString(),
        'grace_days' => 14,
        'domains' => ['selfhosted.test'],
        'modules' => ['radius', 'genieacs', 'olt', 'vpn', 'wa'],
        'limits' => ['devices' => 500],
        'payload' => ['license_id' => 'LIC-SH-001'],
        'uploaded_at' => now(),
        'last_verified_at' => now(),
    ]);

    $connections = MikrotikConnection::factory()->count(2)->create();
    $accounts = RadiusAccount::factory()->count(3)->create([
        'mikrotik_connection_id' => $connections->first()->id,
    ]);
    RadiusNas::factory()->create();
    CpeDevice::factory()->count(2)->create([
        'radius_account_id' => $accounts->first()->id,
        'status' => 'online',
    ]);
    CpeDevice::factory()->create([
        'radius_account_id' => $accounts->last()->id,
        'status' => 'offline',
    ]);
    OltConnection::factory()->create();
    WgPeer::factory()->count(2)->create();
    WaMultiSessionDevice::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Ringkasan Self-Hosted')
        ->assertSee('Self-Hosted Jakarta')
        ->assertSee('2 koneksi MikroTik')
        ->assertSee('3 akun radius / 1 NAS')
        ->assertSee('3 device / 2 online')
        ->assertSee('2 peer VPN')
        ->assertSee('2 device WhatsApp');
});

it('blocks a non super admin from the self-hosted dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertForbidden();
});
