<?php

namespace App\Console\Commands;

use App\Services\SystemLicenseService;
use Illuminate\Console\Command;

class ShowSystemLicenseStatus extends Command
{
    protected $signature = 'license:status {--json : Output as JSON}';

    protected $description = 'Tampilkan status lisensi sistem self-hosted.';

    public function handle(SystemLicenseService $systemLicenseService): int
    {
        $snapshot = $systemLicenseService->getSnapshot();
        $license = $snapshot['license'];
        $licensePayload = is_array($license->payload ?? null) ? $license->payload : [];
        $accessMode = $licensePayload['access_mode'] ?? null;
        $allowedHosts = $licensePayload['allowed_hosts'] ?? $license->domains ?? [];

        $payload = [
            'status' => $license->status,
            'status_label' => $snapshot['status_label'],
            'is_valid' => $snapshot['is_valid'],
            'is_enforced' => $snapshot['is_enforced'],
            'license_id' => $license->license_id,
            'customer_name' => $license->customer_name,
            'instance_name' => $license->instance_name,
            'expires_at' => $license->expires_at?->toDateString(),
            'support_until' => $license->support_until?->toDateString(),
            'grace_days' => $license->grace_days,
            'fingerprint' => $snapshot['expected_fingerprint'],
            'license_path' => $snapshot['license_path'],
            'file_exists' => $snapshot['file_exists'],
            'validation_error' => $license->validation_error,
            'modules' => $license->modules ?? [],
            'limits' => $license->limits ?? [],
            'access_mode' => $accessMode,
            'access_mode_label' => $this->formatAccessMode($accessMode),
            'allowed_hosts' => is_array($allowedHosts) ? array_values($allowedHosts) : [],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Status Lisensi Sistem');
        $this->line('Status          : '.$payload['status_label']);
        $this->line('Enforced        : '.($payload['is_enforced'] ? 'yes' : 'no'));
        $this->line('Valid           : '.($payload['is_valid'] ? 'yes' : 'no'));
        $this->line('License ID      : '.($payload['license_id'] ?: '-'));
        $this->line('Customer        : '.($payload['customer_name'] ?: '-'));
        $this->line('Instance        : '.($payload['instance_name'] ?: '-'));
        $this->line('Expires At      : '.($payload['expires_at'] ?: '-'));
        $this->line('Support Until   : '.($payload['support_until'] ?: '-'));
        $this->line('Grace Days      : '.$payload['grace_days']);
        $this->line('Access Mode     : '.$payload['access_mode_label']);
        $this->line('Allowed Hosts   : '.($payload['allowed_hosts'] !== [] ? implode(', ', $payload['allowed_hosts']) : '-'));
        $this->line('Fingerprint     : '.$payload['fingerprint']);
        $this->line('License Path    : '.$payload['license_path']);
        $this->line('File Exists     : '.($payload['file_exists'] ? 'yes' : 'no'));

        if ($payload['validation_error']) {
            $this->warn('Validation Error: '.$payload['validation_error']);
        }

        if ($payload['modules'] !== []) {
            $this->line('Modules         : '.implode(', ', $payload['modules']));
        }

        return self::SUCCESS;
    }

    private function formatAccessMode(?string $value): string
    {
        return match ($value) {
            'fingerprint_only' => 'Fingerprint Only',
            'ip_based' => 'IP-Based',
            'domain_based' => 'Domain-Based',
            'hybrid' => 'Hybrid',
            null, '' => 'Belum Dicatat',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }
}
