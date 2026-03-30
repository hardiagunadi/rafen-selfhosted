<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_name',
        'business_logo',
        'business_phone',
        'business_email',
        'business_address',
        'website',
        'portal_title',
        'portal_description',
        'isolir_page_title',
        'isolir_page_body',
        'isolir_page_contact',
        'isolir_page_bg_color',
        'isolir_page_accent_color',
        'update_available_version',
        'update_headline',
        'update_summary',
        'update_instructions',
        'update_release_notes_url',
        'update_severity',
        'update_available_at',
        'update_manual_only',
        'update_is_active',
    ];

    protected function casts(): array
    {
        return [
            'update_available_at' => 'datetime',
            'update_manual_only' => 'boolean',
            'update_is_active' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Rafen Self-Hosted',
                'portal_title' => 'Portal Pelanggan',
                'portal_description' => 'Cek tagihan dan status layanan internet Anda.',
                'isolir_page_bg_color' => '#1a1a2e',
                'isolir_page_accent_color' => '#e94560',
                'update_manual_only' => true,
                'update_is_active' => false,
            ],
        );
    }

    public function installedVersion(string $fallback = 'self-hosted-dev'): string
    {
        $version = trim((string) config('app.version', $fallback));

        return $version !== '' ? $version : $fallback;
    }

    public function appName(string $fallback = 'Rafen Self-Hosted'): string
    {
        $businessName = trim((string) $this->business_name);

        return $businessName !== '' ? $businessName : $fallback;
    }

    public function portalName(): string
    {
        $portalTitle = trim((string) $this->portal_title);

        if ($portalTitle !== '') {
            return $portalTitle;
        }

        return 'Portal '.$this->appName('Pelanggan');
    }

    public function portalDescription(): string
    {
        $description = trim((string) $this->portal_description);

        return $description !== ''
            ? $description
            : 'Cek tagihan dan status layanan internet Anda.';
    }

    public function getIsolirPageTitle(): string
    {
        $title = trim((string) $this->isolir_page_title);

        if ($title !== '') {
            return $title;
        }

        return 'Layanan '.$this->appName('Internet').' Dinonaktifkan';
    }

    public function getIsolirPageBody(): string
    {
        $body = trim((string) $this->isolir_page_body);

        if ($body !== '') {
            return $body;
        }

        return "Layanan internet Anda telah dinonaktifkan sementara karena belum melakukan pembayaran.\n\nSilakan hubungi admin untuk proses aktivasi kembali.";
    }

    public function getIsolirPageContact(): string
    {
        $contact = trim((string) $this->isolir_page_contact);

        if ($contact !== '') {
            return $contact;
        }

        $parts = array_filter([
            trim((string) $this->business_phone),
            trim((string) $this->business_email),
        ]);

        return implode(' | ', $parts);
    }

    public function hasUpdateNotice(): bool
    {
        if ($this->update_is_active !== true) {
            return false;
        }

        $availableVersion = trim((string) $this->update_available_version);

        if ($availableVersion === '') {
            return false;
        }

        return $availableVersion !== $this->installedVersion();
    }

    public function updateSeverityBadge(): string
    {
        return match ($this->update_severity) {
            'info' => 'info',
            'danger' => 'danger',
            default => 'warning',
        };
    }

    public function updateHeadlineText(): string
    {
        $headline = trim((string) $this->update_headline);

        return $headline !== '' ? $headline : 'Update manual self-hosted tersedia';
    }

    public function updateSummaryText(): string
    {
        $summary = trim((string) $this->update_summary);

        if ($summary !== '') {
            return $summary;
        }

        return 'Versi baru tersedia. Jadwalkan maintenance window, ambil backup, lalu lakukan update secara manual agar operasional tidak terganggu.';
    }

    public function updateInstructionsText(): string
    {
        $instructions = trim((string) $this->update_instructions);

        if ($instructions !== '') {
            return $instructions;
        }

        return 'Disarankan: backup database, uji di staging/cadangan, lalu update pada jam maintenance yang aman.';
    }
}
