<?php

use App\Models\ActivityLog;
use App\Models\CpeDevice;
use App\Models\PortalSession;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\RadiusAccount;
use App\Models\WaConversation;
use App\Models\WaTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

function makePortalOperationsUser(array $attributes = []): PppUser
{
    $profileGroup = ProfileGroup::factory()->create();
    $profile = PppProfile::factory()->create([
        'name' => 'Paket Portal Ops',
        'profile_group_id' => $profileGroup->id,
    ]);

    return PppUser::factory()->create(array_merge([
        'customer_id' => '000000099001',
        'customer_name' => 'Portal Operasional',
        'username' => 'portal-ops',
        'nomor_hp' => '6282112233445',
        'password_clientarea' => 'portal-secret',
        'ppp_profile_id' => $profile->id,
        'profile_group_id' => $profileGroup->id,
    ], $attributes));
}

function makePortalOperationsToken(PppUser $pppUser): string
{
    $token = Str::random(64);

    PortalSession::query()->create([
        'ppp_user_id' => $pppUser->id,
        'token' => $token,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'last_activity_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    return $token;
}

it('creates a portal support ticket and chat conversation', function () {
    $pppUser = makePortalOperationsUser();
    $token = makePortalOperationsToken($pppUser);

    $this->withCookie('portal_session', $token)
        ->post(route('portal.tickets.store'), [
            'subject' => 'Internet sering putus',
            'message' => 'Mohon dicek karena sejak pagi koneksi tidak stabil.',
            'type' => 'troubleshoot',
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('ticket.customer_name', $pppUser->customer_name);

    $ticket = WaTicket::query()->first();
    $conversation = WaConversation::query()->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket?->customer_type)->toBe('ppp')
        ->and($ticket?->customer_id)->toBe($pppUser->id)
        ->and($ticket?->status)->toBe('open');

    expect($conversation)->not->toBeNull()
        ->and($conversation?->contact_phone)->toBe($pppUser->nomor_hp)
        ->and($conversation?->messages()->count())->toBe(1)
        ->and($conversation?->last_message)->toContain('koneksi tidak stabil');

    expect(ActivityLog::query()->where('action', 'portal_ticket_created')->exists())->toBeTrue();
});

it('updates wifi settings from the portal when a linked cpe is available', function () {
    config()->set('genieacs.nbi_url', 'http://127.0.0.1:7557');

    $pppUser = makePortalOperationsUser([
        'username' => 'portal-cpe',
    ]);
    $token = makePortalOperationsToken($pppUser);

    $radiusAccount = RadiusAccount::factory()->create([
        'username' => 'portal-cpe',
        'service' => 'pppoe',
    ]);

    $cpeDevice = CpeDevice::factory()->create([
        'radius_account_id' => $radiusAccount->id,
        'genieacs_device_id' => 'PORTAL-CPE-001',
        'param_profile' => 'igd',
        'cached_params' => [
            'wifi_networks' => [
                ['index' => 1, 'ssid' => 'Wifi-Lama', 'enabled' => true],
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/PORTAL-CPE-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'portal-wifi-task'], 202);
        }

        if ($request->method() === 'DELETE' && $request->url() === 'http://127.0.0.1:7557/tasks/portal-wifi-task') {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });

    $this->withCookie('portal_session', $token)
        ->post(route('portal.wifi.update'), [
            'ssid' => 'Wifi-Baru-Portal',
            'password' => 'password123',
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(data_get($cpeDevice->fresh()->cached_params, 'wifi_networks.0.ssid'))->toBe('Wifi-Baru-Portal');
    expect(ActivityLog::query()->where('action', 'portal_wifi_updated')->exists())->toBeTrue();
});

it('returns a validation error when portal wifi update has no linked device', function () {
    config()->set('genieacs.nbi_url', 'http://127.0.0.1:7557');

    $pppUser = makePortalOperationsUser([
        'username' => 'portal-no-device',
    ]);
    $token = makePortalOperationsToken($pppUser);

    $this->withCookie('portal_session', $token)
        ->post(route('portal.wifi.update'), [
            'ssid' => 'Wifi-Baru-Portal',
            'password' => 'password123',
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('no_device', true);
});

it('returns traffic snapshots based on the radius cache', function () {
    $pppUser = makePortalOperationsUser([
        'username' => 'portal-traffic',
    ]);
    $token = makePortalOperationsToken($pppUser);

    RadiusAccount::factory()->create([
        'username' => 'portal-traffic',
        'service' => 'pppoe',
        'is_active' => true,
        'bytes_in' => 1048576,
        'bytes_out' => 524288,
        'uptime' => '00:10:00',
        'updated_at' => now(),
    ]);

    $this->withCookie('portal_session', $token)
        ->get(route('portal.traffic'), ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('is_active', true)
        ->assertJsonPath('bytes_in', 1048576)
        ->assertJsonPath('bytes_out', 524288)
        ->assertJsonPath('username', 'portal-traffic');
});
