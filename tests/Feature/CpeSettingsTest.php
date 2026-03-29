<?php

use App\Models\CpeDevice;
use App\Models\RadiusAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('genieacs.nbi_url', 'http://127.0.0.1:7557');
    config()->set('genieacs.username', 'genieacs');
    config()->set('genieacs.password', 'secret');
    config()->set('genieacs.online_threshold_minutes', 70);
});

it('shows the cpe inventory page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'alice',
        'service' => 'pppoe',
    ]);

    CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-001',
        'manufacturer' => 'ZTE',
        'model' => 'F670L',
        'status' => 'online',
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/devices/?limit=1') {
            return Http::response([['_id' => 'ONU-001']], 200);
        }

        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/devices/?limit=200') {
            return Http::response([
                [
                    '_id' => 'ONU-001',
                    '_deviceId' => [
                        '_Manufacturer' => 'ZTE',
                        '_ProductClass' => 'F670L',
                        '_SerialNumber' => 'ZTE001',
                    ],
                ],
                [
                    '_id' => 'ONU-002',
                    '_deviceId' => [
                        '_Manufacturer' => 'Huawei',
                        '_ProductClass' => 'HG8245H',
                        '_SerialNumber' => 'HW002',
                    ],
                    'InternetGatewayDevice' => [
                        'WANDevice' => [
                            1 => [
                                'WANConnectionDevice' => [
                                    1 => [
                                        'WANPPPConnection' => [
                                            1 => [
                                                'Username' => ['_value' => 'bob'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->get(route('super-admin.settings.cpe.index'))
        ->assertSuccessful()
        ->assertSee('Inventory CPE')
        ->assertSee('alice')
        ->assertSee('ONU-002')
        ->assertSee('bob');
});

it('blocks non super admin users from the cpe page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.cpe.index'))
        ->assertForbidden();
});

it('syncs active pppoe radius accounts into local cpe devices', function () {
    $user = User::factory()->superAdmin()->create();

    RadiusAccount::factory()->create([
        'username' => 'pppoe-active',
        'service' => 'pppoe',
        'is_active' => true,
    ]);

    RadiusAccount::factory()->create([
        'username' => 'hotspot-user',
        'service' => 'hotspot',
        'is_active' => true,
    ]);

    RadiusAccount::factory()->create([
        'username' => 'pppoe-disabled',
        'service' => 'pppoe',
        'is_active' => false,
    ]);

    Http::fake(function ($request) {
        if ($request->method() !== 'GET') {
            return Http::response([], 200);
        }

        $url = $request->url();

        if ($url === 'http://127.0.0.1:7557/devices/?query=%7B%22InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username._value%22%3A%22pppoe-active%22%7D') {
            return Http::response([[
                '_id' => 'ONU-PPPOE-001',
                '_lastInform' => now()->subMinutes(8)->toIso8601String(),
                '_deviceId' => [
                    '_Manufacturer' => 'ZTE',
                    '_ProductClass' => 'F609',
                    '_SerialNumber' => 'SER001',
                ],
                'InternetGatewayDevice' => [
                    'WANDevice' => [
                        1 => [
                            'WANConnectionDevice' => [
                                1 => [
                                    'WANPPPConnection' => [
                                        1 => [
                                            'Username' => ['_value' => 'pppoe-active'],
                                            'MACAddress' => ['_value' => 'AA:BB:CC:DD:EE:FF'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]], 200);
        }

        if (str_contains($url, 'pppoe-disabled') || str_contains($url, 'hotspot-user')) {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.sync'))
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    expect(CpeDevice::query()->count())->toBe(1);

    $cpeDevice = CpeDevice::query()->with('radiusAccount')->first();

    expect($cpeDevice?->genieacs_device_id)->toBe('ONU-PPPOE-001')
        ->and($cpeDevice?->radiusAccount?->username)->toBe('pppoe-active')
        ->and($cpeDevice?->status)->toBe('online');
});

it('links a genieacs device manually to a radius account', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'manual-link',
        'service' => 'pppoe',
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'GET' && str_contains($request->url(), 'ONU-MANUAL-001')) {
            return Http::response([[
                '_id' => 'ONU-MANUAL-001',
                '_lastInform' => now()->subMinutes(12)->toIso8601String(),
                '_deviceId' => [
                    '_Manufacturer' => 'FiberHome',
                    '_ProductClass' => 'AN5506',
                    '_SerialNumber' => 'FH001',
                ],
            ]], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.link'), [
            'radius_account_id' => $radiusAccount->id,
            'device_id' => 'ONU-MANUAL-001',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('cpe_devices', [
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-MANUAL-001',
        'manufacturer' => 'FiberHome',
    ]);
});

it('reboots a linked cpe device', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'genieacs_device_id' => 'ONU-REBOOT-001',
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-REBOOT-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'task-1'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/task-1') {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.reboot', $cpeDevice))
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');
});

it('can unlink a cpe device', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create();

    $this->actingAs($user)
        ->delete(route('super-admin.settings.cpe.destroy', $cpeDevice))
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('cpe_devices', [
        'id' => $cpeDevice->id,
    ]);
});
