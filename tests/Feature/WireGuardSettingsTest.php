<?php

use App\Models\User;
use App\Models\WgPeer;
use App\Services\WgPeerSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->wireguardDirectory = storage_path('framework/testing/wireguard');
    $this->wireguardConfigPath = $this->wireguardDirectory.'/wg0.conf';
    $this->wireguardPrivateKeyPath = $this->wireguardDirectory.'/server_private.key';
    $this->wireguardPublicKeyPath = $this->wireguardDirectory.'/server_public.key';

    File::deleteDirectory($this->wireguardDirectory);
    File::ensureDirectoryExists($this->wireguardDirectory);

    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('wg.host', 'vpn.self-hosted.test');
    config()->set('wg.server_ip', '10.0.0.1');
    config()->set('wg.server_address', '10.0.0.1/24');
    config()->set('wg.listen_port', '51820');
    config()->set('wg.interface', 'wg0');
    config()->set('wg.config_path', $this->wireguardConfigPath);
    config()->set('wg.server_private_key_path', $this->wireguardPrivateKeyPath);
    config()->set('wg.server_public_key_path', $this->wireguardPublicKeyPath);
    config()->set('wg.apply_command', '');
    config()->set('wg.pool_start', '10.0.0.2');
    config()->set('wg.pool_end', '10.0.0.10');
});

afterEach(function (): void {
    File::deleteDirectory($this->wireguardDirectory);
});

it('shows the wireguard settings page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.wireguard.index'))
        ->assertSuccessful()
        ->assertSee('WireGuard')
        ->assertSee('Tambah Peer');
});

it('blocks non super admin users from the wireguard settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.wireguard.index'))
        ->assertForbidden();
});

it('creates a peer, allocates a vpn ip, and syncs the wireguard config', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.wireguard.peers.store'), [
            'name' => 'Router Cabang A',
            'extra_allowed_ips' => '172.16.0.0/16',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.wireguard.index'))
        ->assertSessionHas('success');

    $peer = WgPeer::query()->first();

    expect($peer)->not->toBeNull()
        ->and($peer->vpn_ip)->toBe('10.0.0.2')
        ->and($peer->last_synced_at)->not->toBeNull()
        ->and(File::exists($this->wireguardConfigPath))->toBeTrue();

    $config = File::get($this->wireguardConfigPath);

    expect($config)->toContain('[Interface]')
        ->and($config)->toContain('# Peer: Router Cabang A')
        ->and($config)->toContain("AllowedIPs = {$peer->vpn_ip}/32, 172.16.0.0/16");
});

it('regenerates a peer keypair and rewrites the config', function () {
    $user = User::factory()->superAdmin()->create();
    $peer = WgPeer::factory()->create([
        'name' => 'Router Cabang B',
        'vpn_ip' => '10.0.0.4',
    ]);

    $oldPublicKey = $peer->public_key;

    $this->actingAs($user)
        ->post(route('super-admin.settings.wireguard.peers.keygen', $peer))
        ->assertRedirect(route('super-admin.settings.wireguard.index'))
        ->assertSessionHas('success');

    $peer->refresh();

    expect($peer->public_key)->not->toBe($oldPublicKey)
        ->and($peer->last_synced_at)->not->toBeNull();

    $config = File::get($this->wireguardConfigPath);

    expect($config)->toContain($peer->public_key)
        ->and($config)->not->toContain($oldPublicKey);
});

it('deletes a peer and removes it from the synced config', function () {
    $user = User::factory()->superAdmin()->create();
    $peer = WgPeer::factory()->create([
        'name' => 'Router Cabang C',
        'vpn_ip' => '10.0.0.5',
    ]);

    app(WgPeerSynchronizer::class)->syncAll(WgPeer::query()->get());

    $this->actingAs($user)
        ->delete(route('super-admin.settings.wireguard.peers.destroy', $peer))
        ->assertRedirect(route('super-admin.settings.wireguard.index'))
        ->assertSessionHas('success');

    expect(WgPeer::query()->count())->toBe(0);

    $config = File::get($this->wireguardConfigPath);

    expect($config)->not->toContain('Router Cabang C');
});
