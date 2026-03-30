<?php

use App\Models\MikrotikConnection;
use App\Models\RadiusAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows active and inactive session monitoring pages for super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    RadiusAccount::factory()->create([
        'service' => 'pppoe',
        'is_active' => true,
        'username' => 'ppp-online-01',
    ]);

    RadiusAccount::factory()->create([
        'service' => 'hotspot',
        'is_active' => false,
        'username' => 'hs-offline-01',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.sessions.pppoe'))
        ->assertSuccessful()
        ->assertSee('Sesi PPPoE Aktif');

    $this->actingAs($admin)
        ->get(route('super-admin.sessions.hotspot-inactive'))
        ->assertSuccessful()
        ->assertSee('Sesi Hotspot Tidak Aktif');
});

it('blocks non super admin users from session monitoring pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.sessions.pppoe'))
        ->assertForbidden();
});

it('returns filtered datatable payloads for active and inactive sessions', function () {
    $admin = User::factory()->superAdmin()->create();
    $routerA = MikrotikConnection::factory()->create(['name' => 'Router A']);
    $routerB = MikrotikConnection::factory()->create(['name' => 'Router B']);

    RadiusAccount::factory()->create([
        'mikrotik_connection_id' => $routerA->id,
        'service' => 'pppoe',
        'is_active' => true,
        'username' => 'ppp-a-1',
        'ipv4_address' => '10.10.10.2',
        'bytes_in' => 2048,
        'bytes_out' => 4096,
    ]);

    RadiusAccount::factory()->create([
        'mikrotik_connection_id' => $routerB->id,
        'service' => 'pppoe',
        'is_active' => true,
        'username' => 'ppp-b-1',
    ]);

    RadiusAccount::factory()->create([
        'mikrotik_connection_id' => $routerA->id,
        'service' => 'hotspot',
        'is_active' => false,
        'username' => 'hs-a-off',
        'caller_id' => 'AA:BB:CC:DD:EE:FF',
    ]);

    $this->actingAs($admin)
        ->getJson(route('super-admin.sessions.pppoe.datatable', [
            'router_id' => $routerA->id,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.username', 'ppp-a-1')
        ->assertJsonPath('data.0.router', 'Router A');

    $this->actingAs($admin)
        ->getJson(route('super-admin.sessions.hotspot-inactive.datatable', [
            'search' => 'hs-a-off',
        ]))
        ->assertSuccessful()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.username', 'hs-a-off');
});
