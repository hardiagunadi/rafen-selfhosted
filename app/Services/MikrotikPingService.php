<?php

namespace App\Services;

use App\Models\MikrotikConnection;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;

class MikrotikPingService
{
    public function ping(MikrotikConnection $connection): void
    {
        $host = $connection->host;
        $timeout = max(1, (int) $connection->api_timeout);
        $port = $this->resolvePort($connection);
        $useSsl = (bool) $connection->use_ssl;
        $result = $this->probe($host, $timeout, $port, $useSsl);
        $failedCount = $connection->failed_ping_count ?? 0;
        $failThreshold = (int) config('ping.fail_threshold', 3);

        if ($result['online']) {
            $wasUnstable = (bool) ($connection->ping_unstable ?? false);
            $wasOnline = (bool) ($connection->is_online ?? false);
            $newFailedCount = 0;
            $isOnline = true;
            $pingUnstable = $wasUnstable && ! $wasOnline;
        } else {
            $newFailedCount = $failedCount + 1;

            if ($newFailedCount >= $failThreshold) {
                $isOnline = false;
                $pingUnstable = false;
            } else {
                $isOnline = $connection->is_online ?? false;
                $pingUnstable = true;
            }
        }

        $connection->forceFill([
            'is_online' => $isOnline,
            'ping_unstable' => $pingUnstable,
            'last_ping_latency_ms' => $result['latency'],
            'last_ping_at' => Carbon::now(),
            'failed_ping_count' => $newFailedCount,
            'last_port_open' => $result['port_open'],
            'last_ping_message' => $this->buildMessage(
                $result['ping_success'],
                $result['latency'],
                $result['port_open'],
                $host,
                $port,
            ),
        ])->save();
    }

    /**
     * @return array{online: bool, ping_success: bool, latency: int|null, port_open: bool}
     */
    public function probe(string $host, int $timeout, int $port, bool $useSsl = false): array
    {
        $pingTimeout = 2;
        $process = Process::fromShellCommandline(sprintf('ping -c 3 -W %d %s', $pingTimeout, escapeshellarg($host)));
        $process->run();

        $pingSuccess = $process->isSuccessful();
        $latency = $this->extractLatency($process->getOutput());
        $portOpen = $this->isPortOpen($host, $port, min($timeout, 3), $useSsl);

        return [
            'online' => $pingSuccess && $portOpen,
            'ping_success' => $pingSuccess,
            'latency' => $latency,
            'port_open' => $portOpen,
        ];
    }

    private function extractLatency(string $output): ?int
    {
        if (preg_match('/time=([\d\.]+)/', $output, $matches) === 1) {
            return (int) round((float) $matches[1]);
        }

        return null;
    }

    private function isPortOpen(string $host, int $port, int $timeout, bool $useSsl): bool
    {
        $address = ($useSsl ? 'ssl' : 'tcp').'://'.$host.':'.$port;
        $context = $useSsl
            ? stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]])
            : null;

        $connection = @stream_socket_client(
            $address,
            $errno,
            $error,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    private function resolvePort(MikrotikConnection $connection): int
    {
        if ($connection->use_ssl) {
            return $connection->api_ssl_port ?: 8729;
        }

        return $connection->api_port ?: 8728;
    }

    private function buildMessage(bool $pingSuccess, ?int $latency, bool $portOpen, string $host, int $port): string
    {
        if (! $pingSuccess) {
            return 'Ping ke '.$host.' gagal';
        }

        if (! $portOpen) {
            return 'Ping OK, port API '.$host.':'.$port.' tertutup';
        }

        if ($latency !== null) {
            return 'Koneksi OK ('.$latency.' ms)';
        }

        return 'Koneksi OK';
    }
}
