<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SelfHostedUpdateNoticeSyncService
{
    /**
     * @return array<string, mixed>
     */
    public function sync(?string $path = null, bool $deactivateMissing = false): array
    {
        $resolvedPath = $path !== null && trim($path) !== ''
            ? trim($path)
            : app(SelfHostedUpdateNoticeResolver::class)->defaultPath();

        $settings = SystemSetting::instance();

        if (! File::exists($resolvedPath)) {
            if ($deactivateMissing) {
                $this->clearNotice($settings);

                return [
                    'status' => 'deactivated',
                    'path' => $resolvedPath,
                ];
            }

            throw new RuntimeException('File metadata update self-hosted tidak ditemukan.');
        }

        $decoded = json_decode((string) File::get($resolvedPath), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('File metadata update self-hosted tidak valid.');
        }

        $availableVersion = trim((string) ($decoded['available_version'] ?? ''));

        if ($availableVersion === '') {
            throw new RuntimeException('Field available_version wajib ada pada metadata update self-hosted.');
        }

        $severity = trim((string) ($decoded['severity'] ?? 'warning'));

        if (! in_array($severity, ['info', 'warning', 'danger'], true)) {
            $severity = 'warning';
        }

        $settings->update([
            'update_available_version' => $availableVersion,
            'update_headline' => trim((string) ($decoded['headline'] ?? '')) ?: null,
            'update_summary' => trim((string) ($decoded['summary'] ?? '')) ?: null,
            'update_instructions' => trim((string) ($decoded['instructions'] ?? '')) ?: null,
            'update_release_notes_url' => trim((string) ($decoded['release_notes_url'] ?? '')) ?: null,
            'update_severity' => $severity,
            'update_available_at' => $decoded['generated_at'] ?? now(),
            'update_manual_only' => (bool) ($decoded['manual_only'] ?? true),
            'update_is_active' => $availableVersion !== $settings->installedVersion(),
        ]);

        return [
            'status' => $availableVersion === $settings->installedVersion() ? 'up_to_date' : 'synced',
            'path' => $resolvedPath,
            'available_version' => $availableVersion,
            'installed_version' => $settings->installedVersion(),
        ];
    }

    private function clearNotice(SystemSetting $settings): void
    {
        $settings->update([
            'update_available_version' => null,
            'update_headline' => null,
            'update_summary' => null,
            'update_instructions' => null,
            'update_release_notes_url' => null,
            'update_severity' => null,
            'update_available_at' => null,
            'update_manual_only' => true,
            'update_is_active' => false,
        ]);
    }
}
