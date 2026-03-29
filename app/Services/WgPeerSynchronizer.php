<?php

namespace App\Services;

use App\Models\WgPeer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;

class WgPeerSynchronizer
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly WgKeyService $wgKeyService,
    ) {}

    /**
     * @param  Collection<int, WgPeer>  $peers
     */
    public function syncAll(Collection $peers): void
    {
        $configPath = (string) config('wg.config_path');

        if ($configPath === '') {
            throw new RuntimeException('Path konfigurasi WireGuard belum diatur (WG_CONFIG_PATH).');
        }

        $directory = dirname($configPath);
        $this->filesystem->ensureDirectoryExists($directory);

        if (! $this->filesystem->isWritable($directory)) {
            throw new RuntimeException("Direktori {$directory} tidak dapat ditulis.");
        }

        if ($this->filesystem->exists($configPath) && ! $this->filesystem->isWritable($configPath)) {
            throw new RuntimeException("File {$configPath} tidak dapat ditulis.");
        }

        $serverKeys = $this->wgKeyService->resolveServerKeypair();

        if ($serverKeys['private_key'] === '') {
            throw new RuntimeException('Server private key WireGuard belum tersedia.');
        }

        $payload = $this->buildConfig(
            $serverKeys['private_key'],
            (string) config('wg.server_address', '10.0.0.1/24'),
            (string) config('wg.listen_port', '51820'),
            (string) config('wg.post_up', ''),
            (string) config('wg.post_down', ''),
            $peers,
        );

        $tempPath = $configPath.'.tmp.'.getmypid();
        $this->filesystem->put($tempPath, $payload);
        $this->filesystem->move($tempPath, $configPath);

        $applyCommand = trim((string) config('wg.apply_command', ''));

        if ($applyCommand !== '') {
            $process = Process::fromShellCommandline($applyCommand);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Perintah apply WireGuard gagal: '.trim($process->getErrorOutput() ?: $process->getOutput()));
            }
        }
    }

    /**
     * @param  Collection<int, WgPeer>  $peers
     */
    private function buildConfig(
        string $serverPrivateKey,
        string $serverAddress,
        string $listenPort,
        string $postUp,
        string $postDown,
        Collection $peers,
    ): string {
        $lines = [
            '# Managed by Rafen Self-Hosted',
            '# Updated at '.now()->toDateTimeString(),
            '',
            '[Interface]',
            "PrivateKey = {$serverPrivateKey}",
            "Address = {$serverAddress}",
            "ListenPort = {$listenPort}",
        ];

        if ($postUp !== '') {
            $lines[] = "PostUp = {$postUp}";
        }

        if ($postDown !== '') {
            $lines[] = "PostDown = {$postDown}";
        }

        $activePeers = $peers->filter(fn (WgPeer $peer): bool => $peer->is_active && $peer->public_key !== '' && $peer->vpn_ip !== null);

        foreach ($activePeers as $peer) {
            $lines[] = '';
            $lines[] = "# Peer: {$peer->name}";
            $lines[] = '[Peer]';
            $lines[] = "PublicKey = {$peer->public_key}";

            if ($peer->preshared_key) {
                $lines[] = "PresharedKey = {$peer->preshared_key}";
            }

            $allowedIps = "{$peer->vpn_ip}/32";

            if ($peer->extra_allowed_ips) {
                $allowedIps .= ', '.trim($peer->extra_allowed_ips, ', ');
            }

            $lines[] = "AllowedIPs = {$allowedIps}";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
