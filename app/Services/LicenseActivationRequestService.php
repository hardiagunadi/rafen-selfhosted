<?php

namespace App\Services;

class LicenseActivationRequestService
{
    public function __construct(
        private readonly LicenseFingerprintService $fingerprintService,
        private readonly SystemLicenseService $systemLicenseService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function makePayload(): array
    {
        $currentLicense = $this->systemLicenseService->getCurrent();
        $appUrl = (string) config('app.url');
        $appUrlHost = (string) parse_url($appUrl, PHP_URL_HOST);
        $serverName = php_uname('n');
        $hostCandidates = array_values(array_unique(array_filter([$appUrlHost, $serverName])));
        $accessMode = $this->resolveAccessMode($appUrlHost);

        return [
            'app_name' => (string) config('app.name'),
            'app_url' => $appUrl,
            'app_url_host' => $appUrlHost,
            'app_env' => (string) config('app.env'),
            'access_mode' => $accessMode,
            'requested_hosts' => $hostCandidates,
            'generated_at' => now()->toIso8601String(),
            'server_name' => $serverName,
            'fingerprint' => $this->fingerprintService->generate(),
            'current_license_status' => $currentLicense->status,
            'current_license_id' => $currentLicense->license_id,
        ];
    }

    private function resolveAccessMode(string $appUrlHost): string
    {
        if ($appUrlHost === '' || in_array($appUrlHost, ['localhost', '127.0.0.1'], true)) {
            return 'fingerprint_only';
        }

        if (filter_var($appUrlHost, FILTER_VALIDATE_IP) !== false) {
            return 'ip_based';
        }

        return 'domain_based';
    }
}
