<?php

use App\Models\User;
use App\Models\WaGatewaySetting;
use App\Models\WaMultiSessionDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('wa.multi_session.host', '127.0.0.1');
    config()->set('wa.multi_session.port', 3100);
    config()->set('wa.multi_session.auth_token', 'env-token');
    config()->set('wa.multi_session.master_key', 'env-master-key');
    config()->set('wa.multi_session.pm2_name', 'wa-multi-session');
    config()->set('wa.multi_session.pm2_bin', 'pm2');
    config()->set('wa.multi_session.pm2_home', storage_path('framework/testing/.pm2'));
    config()->set('wa.multi_session.log_file', storage_path('logs/wa-multi-session.log'));

    Process::fake(fn () => Process::result('[]', '', 0));
    Http::fake([
        'http://127.0.0.1:3100/status' => Http::response(['status' => true], 200),
        '*' => Http::response(['status' => true], 200),
    ]);
});

it('shows the whatsapp gateway page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.wa-gateway.index'))
        ->assertSuccessful()
        ->assertSee('WhatsApp Gateway')
        ->assertSee('Tambah Device');
});

it('blocks non super admin users from the whatsapp gateway page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.wa-gateway.index'))
        ->assertForbidden();
});

it('updates whatsapp gateway settings', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->put(route('super-admin.settings.wa-gateway.update'), [
            'business_name' => 'Rafen Cabang Test',
            'business_phone' => '081234567890',
            'default_test_recipient' => '081234567891',
            'gateway_url' => 'http://127.0.0.1:3100',
            'auth_token' => 'device-token',
            'master_key' => 'master-key',
            'is_enabled' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'))
        ->assertSessionHas('success');

    $settings = WaGatewaySetting::instance();

    expect($settings->business_name)->toBe('Rafen Cabang Test')
        ->and($settings->business_phone)->toBe('081234567890')
        ->and($settings->default_test_recipient)->toBe('081234567891')
        ->and($settings->auth_token)->toBe('device-token')
        ->and($settings->is_enabled)->toBeTrue();
});

it('creates a whatsapp device and can promote another device as default', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.wa-gateway.devices.store'), [
            'device_name' => 'Device Utama',
            'session_id' => 'device-utama',
            'wa_number' => '081234567890',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'))
        ->assertSessionHas('success');

    $secondary = WaMultiSessionDevice::factory()->create([
        'device_name' => 'Device Cadangan',
        'session_id' => 'device-cadangan',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.settings.wa-gateway.devices.default', $secondary))
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'))
        ->assertSessionHas('success');

    expect(WaMultiSessionDevice::query()->where('device_name', 'Device Utama')->value('is_default'))->toBeFalse()
        ->and($secondary->fresh()->is_default)->toBeTrue();
});

it('sends a whatsapp test message through the selected device', function () {
    $user = User::factory()->superAdmin()->create();
    WaGatewaySetting::factory()->create([
        'id' => 1,
        'auth_token' => 'device-token',
        'gateway_url' => 'http://127.0.0.1:3100',
        'default_test_recipient' => '081234567890',
    ]);
    $device = WaMultiSessionDevice::factory()->create([
        'device_name' => 'Device Test',
        'session_id' => 'device-test',
        'is_default' => true,
    ]);

    Http::fake([
        'http://127.0.0.1:3100/api/v2/send-message' => Http::response([
            'status' => true,
            'data' => [
                'messages' => [[
                    'status' => 'queued',
                ]],
            ],
        ], 200),
        '*' => Http::response(['status' => true], 200),
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.settings.wa-gateway.test-message'), [
            'device_id' => $device->id,
            'recipient_phone' => '081234567890',
            'message' => 'Halo dari self-hosted',
        ])
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'))
        ->assertSessionHas('success');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'http://127.0.0.1:3100/api/v2/send-message'
            && $request['data'][0]['session'] === 'device-test'
            && $request['data'][0]['phone'] === '6281234567890';
    });
});

it('restarts the local whatsapp service via the manager', function () {
    $user = User::factory()->superAdmin()->create();

    Process::fake(fn () => Process::result('[]', '', 0));

    $this->actingAs($user)
        ->post(route('super-admin.settings.wa-gateway.service', 'restart'))
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'));

    Process::assertRan(function ($process): bool {
        return str_contains($process->command, 'pm2 restart wa-multi-session --update-env');
    });
});

it('refreshes whatsapp session status for a device', function () {
    $user = User::factory()->superAdmin()->create();
    WaGatewaySetting::factory()->create([
        'id' => 1,
        'auth_token' => 'device-token',
        'gateway_url' => 'http://127.0.0.1:3100',
    ]);
    $device = WaMultiSessionDevice::factory()->create([
        'session_id' => 'device-session-status',
        'last_status' => null,
    ]);

    Http::fake(function ($request) {
        if (str_starts_with($request->url(), 'http://127.0.0.1:3100/api/v2/sessions/status')) {
            return Http::response([
                'status' => true,
                'data' => [
                    'status' => 'connected',
                ],
            ], 200);
        }

        return Http::response(['status' => true], 200);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.wa-gateway.devices.session', [$device, 'status']))
        ->assertRedirect(route('super-admin.settings.wa-gateway.index'))
        ->assertSessionHas('success');

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'http://127.0.0.1:3100/api/v2/sessions/status'));

    expect($device->fresh()->last_seen_at)->not->toBeNull();
});
