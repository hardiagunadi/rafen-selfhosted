<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Throwable;

class SelfHostedUpdateNoticeResolver
{
    public function defaultPath(): string
    {
        return base_path('_self_hosted_update_notice.json');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(SystemSetting $settings): ?array
    {
        $databaseNotice = $this->resolveDatabaseNotice($settings);

        if ($databaseNotice !== null) {
            return $databaseNotice;
        }

        return $this->resolveFileNotice($settings);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveDatabaseNotice(SystemSetting $settings): ?array
    {
        if (! $settings->hasUpdateNotice()) {
            return null;
        }

        return [
            'source' => 'database',
            'current_version' => $settings->installedVersion(),
            'available_version' => $settings->update_available_version,
            'headline' => $settings->updateHeadlineText(),
            'summary' => $settings->updateSummaryText(),
            'instructions' => $settings->updateInstructionsText(),
            'release_notes_url' => $settings->update_release_notes_url,
            'severity' => $settings->updateSeverityBadge(),
            'available_at' => $settings->update_available_at,
            'manual_only' => true,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveFileNotice(SystemSetting $settings): ?array
    {
        $path = $this->defaultPath();

        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            return null;
        }

        $availableVersion = trim((string) ($decoded['available_version'] ?? ''));
        $currentVersion = $settings->installedVersion();

        if ($availableVersion === '' || $availableVersion === $currentVersion) {
            return null;
        }

        $severity = trim((string) ($decoded['severity'] ?? 'warning'));

        if (! in_array($severity, ['info', 'warning', 'danger'], true)) {
            $severity = 'warning';
        }

        return [
            'source' => 'file',
            'current_version' => $currentVersion,
            'available_version' => $availableVersion,
            'headline' => trim((string) ($decoded['headline'] ?? '')) ?: 'Update Rafen Self-Hosted tersedia',
            'summary' => trim((string) ($decoded['summary'] ?? '')) ?: 'Versi baru tersedia. Jadwalkan update manual di maintenance window agar operasional tidak terganggu.',
            'instructions' => trim((string) ($decoded['instructions'] ?? '')) ?: 'Ambil backup lalu update saat jam maintenance yang aman.',
            'release_notes_url' => trim((string) ($decoded['release_notes_url'] ?? '')) ?: null,
            'severity' => $severity,
            'available_at' => $this->parseDate($decoded['generated_at'] ?? null),
            'manual_only' => (bool) ($decoded['manual_only'] ?? true),
        ];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
