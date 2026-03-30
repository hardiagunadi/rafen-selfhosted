<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    File::delete(base_path('_self_hosted_update_notice.json'));
});

it('syncs bundled update metadata into system settings', function () {
    config()->set('app.version', '2026.03.30-sh.4');

    File::put(
        base_path('_self_hosted_update_notice.json'),
        json_encode([
            'schema' => 'self-hosted-update-notice:v1',
            'generated_at' => '2026-03-30T22:15:00+07:00',
            'available_version' => '2026.03.30-sh.5',
            'headline' => 'Update sinkronisasi tersedia',
            'summary' => 'Jadwalkan deploy manual pada maintenance window.',
            'instructions' => 'Backup lalu deploy pada malam hari.',
            'release_notes_url' => 'https://updates.example.test/releases/sync',
            'severity' => 'danger',
            'manual_only' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $this->artisan('self-hosted:sync-update-notice')
        ->expectsOutputToContain('Notifikasi update manual berhasil disinkronkan ke database.')
        ->expectsOutputToContain('Installed Version: 2026.03.30-sh.4')
        ->expectsOutputToContain('Available Version: 2026.03.30-sh.5')
        ->assertSuccessful();

    $settings = SystemSetting::instance()->fresh();

    expect($settings->update_is_active)->toBeTrue()
        ->and($settings->update_available_version)->toBe('2026.03.30-sh.5')
        ->and($settings->update_headline)->toBe('Update sinkronisasi tersedia')
        ->and($settings->update_severity)->toBe('danger')
        ->and($settings->update_release_notes_url)->toBe('https://updates.example.test/releases/sync');
});

it('marks notice inactive when bundled version matches installed version', function () {
    config()->set('app.version', '2026.03.30-sh.5');

    File::put(
        base_path('_self_hosted_update_notice.json'),
        json_encode([
            'available_version' => '2026.03.30-sh.5',
            'headline' => 'Versi sudah terbaru',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $this->artisan('self-hosted:sync-update-notice')
        ->expectsOutputToContain('Metadata update dibaca, tetapi versi saat ini sudah sama dengan versi terpasang.')
        ->assertSuccessful();

    expect(SystemSetting::instance()->fresh()->update_is_active)->toBeFalse();
});

it('can deactivate update notice when metadata file is missing', function () {
    SystemSetting::instance()->update([
        'update_is_active' => true,
        'update_available_version' => '2026.03.30-sh.5',
        'update_headline' => 'Notice lama',
    ]);

    $this->artisan('self-hosted:sync-update-notice --deactivate-missing')
        ->expectsOutputToContain('Notifikasi update manual dinonaktifkan karena file metadata tidak ditemukan.')
        ->assertSuccessful();

    $settings = SystemSetting::instance()->fresh();

    expect($settings->update_is_active)->toBeFalse()
        ->and($settings->update_available_version)->toBeNull()
        ->and($settings->update_headline)->toBeNull();
});

it('fails when metadata file is invalid', function () {
    File::put(base_path('_self_hosted_update_notice.json'), '{invalid-json');

    $this->artisan('self-hosted:sync-update-notice')
        ->expectsOutputToContain('File metadata update self-hosted tidak valid.')
        ->assertFailed();
});
