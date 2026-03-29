<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWgPeerRequest;
use App\Models\WgPeer;
use App\Services\WgKeyService;
use App\Services\WgPeerSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WgSettingsController extends Controller
{
    public function index(WgKeyService $wgKeyService): View
    {
        $serverKeys = $wgKeyService->resolveServerKeypair();

        return view('super-admin.settings.wireguard', [
            'peers' => WgPeer::query()->orderBy('name')->get(),
            'wg' => [
                'host' => $wgKeyService->resolveHost(),
                'interface' => (string) config('wg.interface', 'wg0'),
                'listen_port' => (string) config('wg.listen_port', '51820'),
                'server_ip' => (string) config('wg.server_ip', '10.0.0.1'),
                'server_address' => (string) config('wg.server_address', '10.0.0.1/24'),
                'pool_start' => (string) config('wg.pool_start', '10.0.0.2'),
                'pool_end' => (string) config('wg.pool_end', '10.0.0.254'),
                'config_path' => (string) config('wg.config_path'),
                'server_public_key' => $serverKeys['public_key'],
            ],
        ]);
    }

    public function store(StoreWgPeerRequest $request, WgKeyService $wgKeyService, WgPeerSynchronizer $synchronizer): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['public_key'] = $data['public_key'] ?? null;
        $data['private_key'] = $data['private_key'] ?? null;

        if (empty($data['public_key']) || empty($data['private_key'])) {
            $generatedKeys = $wgKeyService->generateKeypair();
            $data['public_key'] = $data['public_key'] ?: $generatedKeys['public_key'];
            $data['private_key'] = $data['private_key'] ?: $generatedKeys['private_key'];
        }

        if (empty($data['vpn_ip'])) {
            $data['vpn_ip'] = $this->allocateVpnIp();
        }

        if (! $data['vpn_ip']) {
            return redirect()
                ->route('super-admin.settings.wireguard.index')
                ->with('error', 'IP VPN pool sudah habis atau belum diatur.');
        }

        $peer = WgPeer::query()->create($data);

        try {
            $synchronizer->syncAll(WgPeer::query()->get());
            $peer->update(['last_synced_at' => now()]);
        } catch (\Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.wireguard.index')
                ->with('error', 'Peer tersimpan, tetapi sinkronisasi config gagal: '.$throwable->getMessage());
        }

        return redirect()
            ->route('super-admin.settings.wireguard.index')
            ->with('success', 'WireGuard peer berhasil dibuat.');
    }

    public function destroy(WgPeer $wgPeer, WgPeerSynchronizer $synchronizer): RedirectResponse
    {
        $peerName = $wgPeer->name;
        $wgPeer->delete();

        try {
            $synchronizer->syncAll(WgPeer::query()->get());
        } catch (\Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.wireguard.index')
                ->with('error', 'Peer dihapus, tetapi sinkronisasi config gagal: '.$throwable->getMessage());
        }

        return redirect()
            ->route('super-admin.settings.wireguard.index')
            ->with('success', "WireGuard peer {$peerName} berhasil dihapus.");
    }

    public function sync(WgPeerSynchronizer $synchronizer): RedirectResponse
    {
        try {
            $synchronizer->syncAll(WgPeer::query()->get());
            WgPeer::query()->where('is_active', true)->update(['last_synced_at' => now()]);
        } catch (\Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.wireguard.index')
                ->with('error', 'Sinkronisasi konfigurasi gagal: '.$throwable->getMessage());
        }

        return redirect()
            ->route('super-admin.settings.wireguard.index')
            ->with('success', 'Konfigurasi WireGuard berhasil disinkronkan.');
    }

    public function keygen(WgPeer $wgPeer, WgKeyService $wgKeyService, WgPeerSynchronizer $synchronizer): RedirectResponse
    {
        $keys = $wgKeyService->generateKeypair();

        $wgPeer->update([
            'private_key' => $keys['private_key'],
            'public_key' => $keys['public_key'],
        ]);

        try {
            $synchronizer->syncAll(WgPeer::query()->get());
            $wgPeer->update(['last_synced_at' => now()]);
        } catch (\Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.wireguard.index')
                ->with('error', 'Keypair diperbarui, tetapi sinkronisasi config gagal: '.$throwable->getMessage());
        }

        return redirect()
            ->route('super-admin.settings.wireguard.index')
            ->with('success', 'Keypair peer berhasil diperbarui.');
    }

    private function allocateVpnIp(): ?string
    {
        $startLong = $this->ipToLong((string) config('wg.pool_start', ''));
        $endLong = $this->ipToLong((string) config('wg.pool_end', ''));

        if ($startLong === null || $endLong === null || $startLong > $endLong) {
            return null;
        }

        $used = WgPeer::query()
            ->whereNotNull('vpn_ip')
            ->pluck('vpn_ip')
            ->map(fn (string $ip): ?int => $this->ipToLong($ip))
            ->filter(fn (?int $value): bool => $value !== null)
            ->all();

        $usedLookup = array_fill_keys($used, true);
        $serverLong = $this->ipToLong((string) config('wg.server_ip', ''));

        if ($serverLong !== null) {
            $usedLookup[$serverLong] = true;
        }

        for ($current = $startLong; $current <= $endLong; $current++) {
            if (! isset($usedLookup[$current])) {
                return long2ip($current) ?: null;
            }
        }

        return null;
    }

    private function ipToLong(string $ip): ?int
    {
        $value = ip2long($ip);

        if ($value === false) {
            return null;
        }

        return (int) sprintf('%u', $value);
    }
}
