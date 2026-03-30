<?php

namespace App\Services;

use InvalidArgumentException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class SystemCommandRunner
{
    private const TIMEOUT_SECONDS = 25;

    private const OUTPUT_LIMIT_CHARS = 15000;

    /**
     * @var list<string>
     */
    private const ALLOWED_ARTISAN_COMMANDS = [
        'about',
        'cache:clear',
        'config:clear',
        'license:activation-request',
        'license:refresh',
        'license:status',
        'optimize:clear',
        'queue:restart',
        'route:list',
        'schedule:list',
        'vouchers:expire',
        'vouchers:mark-used',
        'wireguard:sync',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_SYSTEMD_SERVICES = [
        'freeradius',
        'genieacs-cwmp',
        'genieacs-fs',
        'genieacs-ui',
        'nginx',
        'php8.4-fpm',
        'wg-quick@wg0',
    ];

    /**
     * @return list<array{label: string, command: string, note: string}>
     */
    public function presets(): array
    {
        $logPath = storage_path('logs/laravel.log');

        return [
            [
                'label' => 'Status Lisensi',
                'command' => 'php artisan license:status --json',
                'note' => 'Audit status lisensi self-hosted dalam format JSON.',
            ],
            [
                'label' => 'Refresh Lisensi',
                'command' => 'php artisan license:refresh',
                'note' => 'Baca ulang file lisensi dari disk tanpa restart aplikasi.',
            ],
            [
                'label' => 'Voucher Mark Used',
                'command' => 'php artisan vouchers:mark-used',
                'note' => 'Sinkronkan voucher yang sudah dipakai dari riwayat radacct.',
            ],
            [
                'label' => 'Voucher Expire',
                'command' => 'php artisan vouchers:expire',
                'note' => 'Hapus voucher kadaluarsa dan bersihkan row RADIUS terkait.',
            ],
            [
                'label' => 'List Route Super Admin',
                'command' => 'php artisan route:list --path=super-admin',
                'note' => 'Cek semua endpoint admin yang sudah aktif di target self-hosted.',
            ],
            [
                'label' => 'Clear Cache',
                'command' => 'php artisan optimize:clear',
                'note' => 'Bersihkan cache framework, config, route, dan view.',
            ],
            [
                'label' => 'Status FreeRADIUS',
                'command' => 'systemctl status freeradius',
                'note' => 'Periksa status service FreeRADIUS pada host.',
            ],
            [
                'label' => 'Status WireGuard',
                'command' => 'wg show',
                'note' => 'Lihat interface dan peer WireGuard yang sedang aktif.',
            ],
            [
                'label' => 'Tail Laravel Log',
                'command' => "tail -50 {$logPath}",
                'note' => 'Tampilkan 50 baris terakhir log aplikasi Laravel.',
            ],
        ];
    }

    public function timeoutSeconds(): int
    {
        return self::TIMEOUT_SECONDS;
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     command: string,
     *     exit_code: int|null,
     *     duration_ms: int,
     *     output: string
     * }
     */
    public function run(string $command): array
    {
        $resolved = $this->resolveProcessArguments($command);
        $startedAt = microtime(true);

        try {
            $process = new Process($resolved['arguments'], base_path());
            $process->setTimeout(self::TIMEOUT_SECONDS);
            $process->run();

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $success = $process->isSuccessful();

            return [
                'success' => $success,
                'message' => $success ? 'Perintah berhasil dijalankan.' : 'Perintah selesai dengan error.',
                'command' => $resolved['display'],
                'exit_code' => $process->getExitCode(),
                'duration_ms' => $durationMs,
                'output' => $this->sanitizeOutput($process->getOutput().$process->getErrorOutput()),
            ];
        } catch (ProcessTimedOutException $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            return [
                'success' => false,
                'message' => 'Perintah dihentikan karena melewati batas waktu eksekusi.',
                'command' => $resolved['display'],
                'exit_code' => null,
                'duration_ms' => $durationMs,
                'output' => $this->sanitizeOutput($exception->getMessage()),
            ];
        }
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveProcessArguments(string $command): array
    {
        $normalized = $this->normalizeCommand($command);

        if ($normalized === '') {
            throw new InvalidArgumentException('Perintah tidak boleh kosong.');
        }

        if (preg_match('/[;&|`><]/', $normalized) === 1) {
            throw new InvalidArgumentException('Perintah tidak diizinkan.');
        }

        if (str_starts_with($normalized, 'php artisan ')) {
            return $this->resolveArtisanCommand($normalized);
        }

        if (str_starts_with($normalized, 'systemctl ')) {
            return $this->resolveSystemctlCommand($normalized);
        }

        if (str_starts_with($normalized, 'journalctl ')) {
            return $this->resolveJournalctlCommand($normalized);
        }

        if (str_starts_with($normalized, 'wg show')) {
            return $this->resolveWireGuardCommand($normalized);
        }

        if (str_starts_with($normalized, 'tail ')) {
            return $this->resolveTailCommand($normalized);
        }

        if (str_starts_with($normalized, 'ping ')) {
            return $this->resolvePingCommand($normalized);
        }

        throw new InvalidArgumentException('Perintah tidak diizinkan.');
    }

    private function normalizeCommand(string $command): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $command));
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveArtisanCommand(string $command): array
    {
        $tokens = preg_split('/\s+/', $command) ?: [];

        if (count($tokens) < 3) {
            throw new InvalidArgumentException('Format command artisan tidak valid.');
        }

        $artisanCommand = $tokens[2];

        if (! in_array($artisanCommand, self::ALLOWED_ARTISAN_COMMANDS, true)) {
            throw new InvalidArgumentException('Perintah artisan tidak masuk allowlist self-hosted.');
        }

        foreach (array_slice($tokens, 3) as $token) {
            if (preg_match('/^--[A-Za-z0-9:_-]+(?:=[A-Za-z0-9._:\\/@-]+)?$/', $token) !== 1) {
                throw new InvalidArgumentException('Opsi command artisan tidak valid.');
            }
        }

        return [
            'arguments' => $tokens,
            'display' => implode(' ', $tokens),
        ];
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveSystemctlCommand(string $command): array
    {
        if (preg_match('/^systemctl (status|restart|reload) ([A-Za-z0-9@._-]+)$/', $command, $matches) !== 1) {
            throw new InvalidArgumentException('Format systemctl tidak valid.');
        }

        [$fullMatch, $action, $service] = $matches;
        unset($fullMatch);

        if (! in_array($service, self::ALLOWED_SYSTEMD_SERVICES, true)) {
            throw new InvalidArgumentException('Service systemd tidak diizinkan.');
        }

        return [
            'arguments' => ['sudo', '-n', 'systemctl', $action, $service],
            'display' => "sudo -n systemctl {$action} {$service}",
        ];
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveJournalctlCommand(string $command): array
    {
        if (preg_match('/^journalctl -u ([A-Za-z0-9@._-]+)$/', $command, $matches) !== 1) {
            throw new InvalidArgumentException('Format journalctl tidak valid.');
        }

        $service = $matches[1];

        if (! in_array($service, self::ALLOWED_SYSTEMD_SERVICES, true)) {
            throw new InvalidArgumentException('Service journalctl tidak diizinkan.');
        }

        return [
            'arguments' => ['sudo', '-n', 'journalctl', '-u', $service],
            'display' => "sudo -n journalctl -u {$service}",
        ];
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveWireGuardCommand(string $command): array
    {
        if (preg_match('/^wg show(?: ([A-Za-z0-9@._-]+))?$/', $command, $matches) !== 1) {
            throw new InvalidArgumentException('Format command WireGuard tidak valid.');
        }

        $arguments = ['sudo', '-n', 'wg', 'show'];
        $display = 'sudo -n wg show';

        if (isset($matches[1]) && $matches[1] !== '') {
            $arguments[] = $matches[1];
            $display .= ' '.$matches[1];
        }

        return [
            'arguments' => $arguments,
            'display' => $display,
        ];
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolveTailCommand(string $command): array
    {
        if (preg_match('/^tail -(\d{1,3}) (.+)$/', $command, $matches) !== 1) {
            throw new InvalidArgumentException('Format tail log tidak valid.');
        }

        $lines = (int) $matches[1];
        $path = $matches[2];
        $expectedPath = storage_path('logs/laravel.log');

        if ($lines < 1 || $lines > 200 || $path !== $expectedPath) {
            throw new InvalidArgumentException('Akses log tidak diizinkan.');
        }

        return [
            'arguments' => ['tail', '-'.$lines, $expectedPath],
            'display' => "tail -{$lines} {$expectedPath}",
        ];
    }

    /**
     * @return array{arguments: list<string>, display: string}
     */
    private function resolvePingCommand(string $command): array
    {
        if (preg_match('/^ping -c (\d{1,2}) ([A-Za-z0-9.-]+)$/', $command, $matches) !== 1) {
            throw new InvalidArgumentException('Format ping tidak valid.');
        }

        $count = (int) $matches[1];
        $host = $matches[2];

        if ($count < 1 || $count > 5) {
            throw new InvalidArgumentException('Jumlah ping tidak diizinkan.');
        }

        return [
            'arguments' => ['ping', '-c', (string) $count, $host],
            'display' => "ping -c {$count} {$host}",
        ];
    }

    private function sanitizeOutput(string $output): string
    {
        $normalized = trim((string) preg_replace('/\e\[[\d;]*m/', '', $output));

        if ($normalized === '') {
            return '[tidak ada output]';
        }

        if (mb_strlen($normalized) <= self::OUTPUT_LIMIT_CHARS) {
            return $normalized;
        }

        return mb_substr($normalized, 0, self::OUTPUT_LIMIT_CHARS)."\n\n...[output dipotong]";
    }
}
