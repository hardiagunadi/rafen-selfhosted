<?php

use App\Models\WgPeer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->wireguardDirectory = storage_path('framework/testing/wireguard-command');
    $this->wireguardConfigPath = $this->wireguardDirectory.'/wg0.conf';
    $this->wireguardPrivateKeyPath = $this->wireguardDirectory.'/server_private.key';
    $this->wireguardPublicKeyPath = $this->wireguardDirectory.'/server_public.key';

    File::deleteDirectory($this->wireguardDirectory);
    File::ensureDirectoryExists($this->wireguardDirectory);

    config()->set('wg.server_address', '10.8.0.1/24');
    config()->set('wg.listen_port', '51820');
    config()->set('wg.config_path', $this->wireguardConfigPath);
    config()->set('wg.server_private_key_path', $this->wireguardPrivateKeyPath);
    config()->set('wg.server_public_key_path', $this->wireguardPublicKeyPath);
    config()->set('wg.apply_command', '');
});

afterEach(function (): void {
    File::deleteDirectory($this->wireguardDirectory);
});

it('syncs active peers through the artisan command', function () {
    $peer = WgPeer::factory()->create([
        'name' => 'HQ Router',
        'vpn_ip' => '10.8.0.2',
        'extra_allowed_ips' => '192.168.10.0/24',
        'is_active' => true,
    ]);

    $this->artisan('wireguard:sync')
        ->expectsOutputToContain('Konfigurasi WireGuard berhasil disinkronkan.')
        ->expectsOutputToContain('Peer aktif: 1')
        ->assertExitCode(0);

    expect(File::exists($this->wireguardConfigPath))->toBeTrue();

    $config = File::get($this->wireguardConfigPath);

    expect($config)
        ->toContain('# Peer: HQ Router')
        ->toContain("AllowedIPs = {$peer->vpn_ip}/32, 192.168.10.0/24");

    expect($peer->fresh()->last_synced_at)->not->toBeNull();
});

it('skips the command gracefully when the wireguard table is not available', function () {
    Schema::drop('wg_peers');

    $this->artisan('wireguard:sync')
        ->expectsOutputToContain('Tabel wg_peers belum tersedia, sinkronisasi WireGuard dilewati.')
        ->assertExitCode(0);
});
