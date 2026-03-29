<?php

use App\Models\MikrotikConnection;
use App\Models\User;
use App\Services\MikrotikPingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows the mikrotik settings page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    MikrotikConnection::factory()->create([
        'name' => 'Router Pusat',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.settings.mikrotik.index'))
        ->assertSuccessful()
        ->assertSee('Koneksi MikroTik')
        ->assertSee('Router Pusat')
        ->assertSee('Tambah Koneksi MikroTik');
});

it('blocks non super admin users from the mikrotik settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.mikrotik.index'))
        ->assertForbidden();
});

it('creates updates and deletes a mikrotik connection', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.mikrotik.store'), [
            'name' => 'Router Cabang',
            'host' => '10.10.10.1',
            'api_port' => 8728,
            'api_ssl_port' => 8729,
            'username' => 'admin',
            'password' => 'secret123',
            'radius_secret' => 'radius123',
            'ros_version' => 'auto',
            'api_timeout' => 10,
            'auth_port' => 1812,
            'acct_port' => 1813,
            'timezone' => '+07:00 Asia/Jakarta',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.mikrotik.index'))
        ->assertSessionHas('success');

    $connection = MikrotikConnection::query()->first();

    expect($connection)->not->toBeNull()
        ->and($connection->name)->toBe('Router Cabang');

    $this->actingAs($user)
        ->put(route('super-admin.settings.mikrotik.update', $connection), [
            'name' => 'Router Cabang Baru',
            'host' => '10.10.10.2',
            'api_port' => 8728,
            'api_ssl_port' => 8729,
            'username' => 'admin2',
            'password' => 'secret456',
            'radius_secret' => 'radius456',
            'ros_version' => '7',
            'api_timeout' => 12,
            'auth_port' => 1812,
            'acct_port' => 1813,
            'timezone' => '+07:00 Asia/Jakarta',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.mikrotik.index'))
        ->assertSessionHas('success');

    expect($connection->fresh()->name)->toBe('Router Cabang Baru')
        ->and($connection->fresh()->host)->toBe('10.10.10.2');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.mikrotik.destroy', $connection))
        ->assertRedirect(route('super-admin.settings.mikrotik.index'))
        ->assertSessionHas('success');

    expect(MikrotikConnection::query()->count())->toBe(0);
});

it('tests a mikrotik connection via json endpoint', function () {
    $user = User::factory()->superAdmin()->create();

    app()->instance(MikrotikPingService::class, new class extends MikrotikPingService
    {
        public function probe(string $host, int $timeout, int $port, bool $useSsl = false): array
        {
            return [
                'online' => true,
                'ping_success' => true,
                'latency' => 18,
                'port_open' => true,
            ];
        }
    });

    $this->actingAs($user)
        ->postJson(route('super-admin.settings.mikrotik.test'), [
            'host' => '10.10.10.1',
            'api_timeout' => 10,
            'api_port' => 8728,
            'use_ssl' => false,
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'latency' => 18,
            'port_open' => true,
        ]);
});

it('updates ping status for a stored mikrotik connection', function () {
    $user = User::factory()->superAdmin()->create();
    $connection = MikrotikConnection::factory()->create([
        'host' => '10.10.10.1',
    ]);

    app()->instance(MikrotikPingService::class, new class extends MikrotikPingService
    {
        public function ping(MikrotikConnection $connection): void
        {
            $connection->forceFill([
                'is_online' => true,
                'ping_unstable' => false,
                'last_ping_message' => 'Koneksi OK (11 ms)',
                'last_ping_latency_ms' => 11,
                'last_ping_at' => now(),
            ])->save();
        }
    });

    $this->actingAs($user)
        ->postJson(route('super-admin.settings.mikrotik.ping', $connection))
        ->assertSuccessful()
        ->assertJson([
            'is_online' => true,
            'ping_unstable' => false,
            'message' => 'Koneksi OK (11 ms)',
        ]);
});
