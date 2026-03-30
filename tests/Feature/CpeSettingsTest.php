<?php

use App\Models\CpeDevice;
use App\Models\RadiusAccount;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
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
        'cached_params' => [
            'wifi_networks' => [
                ['index' => 1, 'ssid' => 'Alice-Wifi', 'enabled' => true],
            ],
            'wan_connections' => [
                ['name' => 'pppoe-1', 'username' => 'alice', 'status' => 'Connected', 'mac_address' => 'aa:bb:cc:dd:ee:ff'],
            ],
            'pppoe_username' => 'alice',
        ],
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
                [
                    '_id' => 'ONU-003',
                    '_deviceId' => [
                        '_Manufacturer' => 'Nokia',
                        '_ProductClass' => 'G-2425G-A',
                        '_SerialNumber' => 'NK003',
                    ],
                    'InternetGatewayDevice' => [
                        'WANDevice' => [
                            2 => [
                                'WANConnectionDevice' => [
                                    4 => [
                                        'WANPPPConnection' => [
                                            7 => [
                                                'Username' => ['_value' => 'charlie'],
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
        ->assertSee('bob')
        ->assertSee('ONU-003')
        ->assertSee('charlie')
        ->assertSee('WAN Info')
        ->assertSee('pppoe-1');
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

it('refreshes a linked cpe device from genieacs', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'refresh-user',
        'service' => 'pppoe',
    ]);

    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-REFRESH-001',
        'param_profile' => 'igd',
        'status' => 'offline',
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-REFRESH-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'refresh-task-1'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/refresh-task-1') {
            return Http::response([], 200);
        }

        if ($request->method() === 'GET' && str_contains($request->url(), 'ONU-REFRESH-001')) {
            return Http::response([[
                '_id' => 'ONU-REFRESH-001',
                '_lastInform' => now()->subMinutes(3)->toIso8601String(),
                '_deviceId' => [
                    '_Manufacturer' => 'Huawei',
                    '_ProductClass' => 'HG8245H',
                    '_SerialNumber' => 'REF001',
                ],
                'InternetGatewayDevice' => [
                    'LANDevice' => [
                        1 => [
                            'WLANConfiguration' => [
                                1 => [
                                    'SSID' => ['_value' => 'Rumah-Baru'],
                                    'Enable' => ['_value' => true],
                                ],
                            ],
                        ],
                    ],
                    'WANDevice' => [
                        1 => [
                            'WANConnectionDevice' => [
                                1 => [
                                    'WANPPPConnection' => [
                                        1 => [
                                            'Username' => ['_value' => 'refresh-user'],
                                            'ConnectionStatus' => ['_value' => 'Connected'],
                                            'MACAddress' => ['_value' => 'AA:BB:CC:DD:EE:11'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.refresh', $cpeDevice))
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    expect($cpeDevice->fresh()->status)->toBe('online')
        ->and($cpeDevice->fresh()->manufacturer)->toBe('Huawei')
        ->and(data_get($cpeDevice->fresh()->cached_params, 'wifi_networks.0.ssid'))->toBe('Rumah-Baru');
});

it('updates cpe wifi settings', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'genieacs_device_id' => 'ONU-WIFI-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'wifi_networks' => [
                ['index' => 1, 'ssid' => 'Wifi-Lama', 'enabled' => true],
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-WIFI-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'wifi-task-1'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/wifi-task-1') {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-wifi', $cpeDevice), [
            'ssid' => 'Wifi-Baru',
            'password' => 'password123',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    expect(data_get($cpeDevice->fresh()->cached_params, 'wifi_networks.0.ssid'))->toBe('Wifi-Baru');
});

it('updates cpe wifi ssid without changing password', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'genieacs_device_id' => 'ONU-WIFI-SSID-ONLY-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'wifi_networks' => [
                ['index' => 1, 'ssid' => 'Wifi-Lama', 'enabled' => true],
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-WIFI-SSID-ONLY-001/tasks?connection_request&timeout=3000') {
            expect($request['parameterValues'])->toHaveCount(1)
                ->and($request['parameterValues'][0][0])->toBe(config('genieacs.params.igd.wifi_ssid'))
                ->and($request['parameterValues'][0][1])->toBe('Wifi-Baru');

            return Http::response(['_id' => 'wifi-task-ssid-only-1'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/wifi-task-ssid-only-1') {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-wifi', $cpeDevice), [
            'ssid' => 'Wifi-Baru',
            'password' => '',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    expect(data_get($cpeDevice->fresh()->cached_params, 'wifi_networks.0.ssid'))->toBe('Wifi-Baru');
});

it('does not update local wifi cache when genieacs wifi update fails', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'genieacs_device_id' => 'ONU-WIFI-FAIL-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'wifi_networks' => [
                ['index' => 1, 'ssid' => 'Wifi-Lama', 'enabled' => true],
            ],
        ],
    ]);

    Http::fake(fn () => Http::response([], 500));

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-wifi', $cpeDevice), [
            'ssid' => 'Wifi-Baru',
            'password' => 'password123',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('error');

    expect(data_get($cpeDevice->fresh()->cached_params, 'wifi_networks.0.ssid'))->toBe('Wifi-Lama');
});

it('updates cpe pppoe settings and syncs radius replies', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'lama-user',
        'password' => 'lama-pass',
        'service' => 'pppoe',
        'profile' => 'pool-a',
        'rate_limit' => '20M/20M',
        'ipv4_address' => '10.10.10.10',
    ]);

    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-PPPOE-UPDATE-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'pppoe_username' => 'lama-user',
        ],
    ]);

    RadiusCheck::query()->create([
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Cleartext-Password',
        'op' => ':=',
        'value' => 'lama-pass',
    ]);

    RadiusReply::query()->create([
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Framed-IP-Address',
        'op' => ':=',
        'value' => '10.10.10.10',
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-PPPOE-UPDATE-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'pppoe-task-1'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/pppoe-task-1') {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-pppoe', $cpeDevice), [
            'username' => 'baru-user',
            'password' => 'baru-pass',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('success');

    expect($radiusAccount->fresh()->username)->toBe('baru-user')
        ->and($radiusAccount->fresh()->password)->toBe('baru-pass')
        ->and(data_get($cpeDevice->fresh()->cached_params, 'pppoe_username'))->toBe('baru-user');

    $this->assertDatabaseHas('radius_checks', [
        'radius_account_id' => $radiusAccount->id,
        'username' => 'baru-user',
        'attribute' => 'Cleartext-Password',
        'value' => 'baru-pass',
    ]);

    $this->assertDatabaseHas('radius_replies', [
        'radius_account_id' => $radiusAccount->id,
        'username' => 'baru-user',
        'attribute' => 'Framed-IP-Address',
        'value' => '10.10.10.10',
    ]);

    $this->assertDatabaseMissing('radius_checks', [
        'username' => 'lama-user',
    ]);

    $this->assertDatabaseMissing('radius_replies', [
        'username' => 'lama-user',
    ]);

    expect(RadiusCheck::query()->count())->toBeGreaterThan(0)
        ->and(RadiusReply::query()->count())->toBeGreaterThan(0);
});

it('does not update local radius data when genieacs pppoe update fails', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'lama-user',
        'password' => 'lama-pass',
        'service' => 'pppoe',
        'profile' => 'pool-a',
        'rate_limit' => '20M/20M',
        'ipv4_address' => '10.10.10.10',
    ]);

    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-PPPOE-FAIL-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'pppoe_username' => 'lama-user',
        ],
    ]);

    RadiusCheck::query()->create([
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Cleartext-Password',
        'op' => ':=',
        'value' => 'lama-pass',
    ]);

    RadiusReply::query()->create([
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Framed-IP-Address',
        'op' => ':=',
        'value' => '10.10.10.10',
    ]);

    Http::fake(fn () => Http::response([], 500));

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-pppoe', $cpeDevice), [
            'username' => 'baru-user',
            'password' => 'baru-pass',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('error');

    expect($radiusAccount->fresh()->username)->toBe('lama-user')
        ->and($radiusAccount->fresh()->password)->toBe('lama-pass')
        ->and(data_get($cpeDevice->fresh()->cached_params, 'pppoe_username'))->toBe('lama-user');

    $this->assertDatabaseHas('radius_checks', [
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Cleartext-Password',
        'value' => 'lama-pass',
    ]);

    $this->assertDatabaseHas('radius_replies', [
        'radius_account_id' => $radiusAccount->id,
        'username' => 'lama-user',
        'attribute' => 'Framed-IP-Address',
        'value' => '10.10.10.10',
    ]);

    $this->assertDatabaseMissing('radius_checks', [
        'username' => 'baru-user',
    ]);

    $this->assertDatabaseMissing('radius_replies', [
        'username' => 'baru-user',
    ]);
});

it('rejects pppoe update when the cpe device is no longer linked to a radius account', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => null,
        'genieacs_device_id' => 'ONU-NO-RADIUS-001',
        'param_profile' => 'igd',
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.settings.cpe.update-pppoe', $cpeDevice), [
            'username' => 'baru-user',
            'password' => 'baru-pass',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHas('error');
});

it('validates cpe wifi input', function () {
    $user = User::factory()->superAdmin()->create();
    $cpeDevice = CpeDevice::factory()->create([
        'genieacs_device_id' => 'ONU-WIFI-VALIDATION-001',
    ]);

    $this->actingAs($user)
        ->from(route('super-admin.settings.cpe.index'))
        ->post(route('super-admin.settings.cpe.update-wifi', $cpeDevice), [
            'wifi_device_id' => $cpeDevice->id,
            'ssid' => '',
            'password' => 'pendek',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHasErrors(['ssid', 'password']);
});

it('validates duplicate pppoe usernames on cpe update', function () {
    $user = User::factory()->superAdmin()->create();
    RadiusAccount::factory()->create([
        'username' => 'sudah-dipakai',
        'service' => 'pppoe',
    ]);

    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'lama-user',
        'service' => 'pppoe',
    ]);

    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'ONU-PPPOE-VALIDATION-001',
        'param_profile' => 'igd',
    ]);

    $this->actingAs($user)
        ->from(route('super-admin.settings.cpe.index'))
        ->post(route('super-admin.settings.cpe.update-pppoe', $cpeDevice), [
            'pppoe_device_id' => $cpeDevice->id,
            'username' => 'sudah-dipakai',
            'password' => 'baru-pass',
        ])
        ->assertRedirect(route('super-admin.settings.cpe.index'))
        ->assertSessionHasErrors(['username']);
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
