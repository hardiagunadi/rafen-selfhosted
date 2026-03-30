<?php

use App\Models\PortalSession;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\PushSubscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('push.vapid.public_key', 'test-vapid-public-key');
    Storage::fake('public');
    File::delete(base_path('_self_hosted_update_notice.json'));
});

function makePortalReadyUser(array $attributes = []): PppUser
{
    $profileGroup = ProfileGroup::factory()->create();
    $profile = PppProfile::factory()->create([
        'name' => 'Portal PWA',
        'profile_group_id' => $profileGroup->id,
    ]);

    return PppUser::factory()->create(array_merge([
        'customer_id' => '000000090001',
        'customer_name' => 'Portal Push',
        'username' => 'portal-push',
        'nomor_hp' => '6281111111999',
        'password_clientarea' => 'portal-secret',
        'ppp_profile_id' => $profile->id,
        'profile_group_id' => $profileGroup->id,
    ], $attributes));
}

function makePortalToken(PppUser $pppUser): string
{
    $token = Str::random(64);

    PortalSession::query()->create([
        'ppp_user_id' => $pppUser->id,
        'token' => $token,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'last_activity_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    return $token;
}

it('shows and updates system settings for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.system.index'))
        ->assertSuccessful()
        ->assertSee('Pengaturan Sistem');

    $this->actingAs($user)
        ->put(route('super-admin.settings.system.update-business'), [
            'business_name' => 'ISP Mandiri',
            'business_phone' => '081234567890',
            'business_email' => 'hello@isp.test',
            'website' => 'https://isp.test',
            'business_address' => 'Jl. Merdeka 1',
            'portal_title' => 'Portal ISP Mandiri',
            'portal_description' => 'Portal pelanggan self-hosted',
        ])
        ->assertRedirect(route('super-admin.settings.system.index'));

    $this->actingAs($user)
        ->put(route('super-admin.settings.system.update-isolir'), [
            'isolir_page_title' => 'Layanan Ditangguhkan',
            'isolir_page_body' => 'Harap selesaikan pembayaran.',
            'isolir_page_contact' => 'WA 081234567890',
            'isolir_page_bg_color' => '112233',
            'isolir_page_accent_color' => '#445566',
        ])
        ->assertRedirect(route('super-admin.settings.system.index'));

    $this->actingAs($user)
        ->put(route('super-admin.settings.system.update-notice'), [
            'update_is_active' => '1',
            'update_available_version' => '2026.03.30-sh.5',
            'update_headline' => 'Update stabilitas tersedia',
            'update_summary' => 'Jadwalkan update manual di luar jam sibuk.',
            'update_instructions' => 'Ambil backup lalu update pada maintenance window malam hari.',
            'update_release_notes_url' => 'https://updates.example.test/releases/2026-03-30',
            'update_severity' => 'warning',
            'update_available_at' => '2026-03-30 20:15:00',
        ])
        ->assertRedirect(route('super-admin.settings.system.index'));

    $logo = UploadedFile::fake()->image('logo.png', 300, 300);

    $this->actingAs($user)
        ->post(route('super-admin.settings.system.upload-logo'), [
            'business_logo' => $logo,
        ])
        ->assertRedirect(route('super-admin.settings.system.index'));

    $settings = SystemSetting::instance()->fresh();

    expect($settings->business_name)->toBe('ISP Mandiri');
    expect($settings->portal_title)->toBe('Portal ISP Mandiri');
    expect($settings->isolir_page_title)->toBe('Layanan Ditangguhkan');
    expect($settings->isolir_page_bg_color)->toBe('#112233');
    expect($settings->business_logo)->not->toBeNull();
    expect($settings->update_is_active)->toBeTrue();
    expect($settings->update_available_version)->toBe('2026.03.30-sh.5');
    expect($settings->update_headline)->toBe('Update stabilitas tersedia');

    Storage::disk('public')->assertExists((string) $settings->business_logo);
});

it('shows manual update notice in the admin layout when configured', function () {
    config()->set('app.version', '2026.03.30-sh.4');

    SystemSetting::instance()->update([
        'update_is_active' => true,
        'update_available_version' => '2026.03.30-sh.5',
        'update_headline' => 'Patch keamanan siap dipasang',
        'update_summary' => 'Lakukan update manual di luar jam operasional.',
        'update_instructions' => 'Backup database lalu deploy saat maintenance window.',
        'update_release_notes_url' => 'https://updates.example.test/releases/patch',
        'update_severity' => 'danger',
        'update_available_at' => now(),
    ]);

    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Patch keamanan siap dipasang')
        ->assertSee('Versi terpasang')
        ->assertSee('2026.03.30-sh.4')
        ->assertSee('2026.03.30-sh.5')
        ->assertSee('manual terjadwal');
});

it('shows manual update notice from bundled metadata file', function () {
    config()->set('app.version', '2026.03.30-sh.4');

    File::put(
        base_path('_self_hosted_update_notice.json'),
        json_encode([
            'schema' => 'self-hosted-update-notice:v1',
            'generated_at' => now()->toIso8601String(),
            'available_version' => '2026.03.30-sh.6',
            'headline' => 'Update bundle baru tersedia',
            'summary' => 'Maintainer sudah menyiapkan bundle baru. Jadwalkan deploy manual.',
            'instructions' => 'Ambil backup lalu deploy saat maintenance window.',
            'release_notes_url' => 'https://updates.example.test/releases/bundle',
            'severity' => 'info',
            'manual_only' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Update bundle baru tersedia')
        ->assertSee('2026.03.30-sh.4')
        ->assertSee('2026.03.30-sh.6')
        ->assertSee('Catatan Rilis');
});

it('blocks non super admin users from system settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.system.index'))
        ->assertForbidden();
});

it('shows public isolir and preview pages with custom settings', function () {
    $user = User::factory()->superAdmin()->create();
    $pppUser = makePortalReadyUser([
        'customer_name' => 'Budi Isolir',
    ]);

    SystemSetting::instance()->update([
        'business_name' => 'ISP Demo',
        'isolir_page_title' => 'Akses Dinonaktifkan',
        'isolir_page_body' => 'Tagihan Anda belum dibayar.',
        'isolir_page_contact' => '081122334455',
    ]);

    $this->get(route('isolir.show'))
        ->assertSuccessful()
        ->assertSee('Akses Dinonaktifkan')
        ->assertSee('081122334455');

    $this->get(route('isolir.customer', $pppUser))
        ->assertSuccessful()
        ->assertSee('Budi Isolir');

    $this->actingAs($user)
        ->get(route('super-admin.settings.system.isolir-preview'))
        ->assertSuccessful()
        ->assertSee('PREVIEW');
});

it('serves admin and portal manifests and icon routes', function () {
    SystemSetting::instance()->update([
        'business_name' => 'ISP Manifest',
        'portal_title' => 'Portal Manifest',
    ]);

    $this->get(route('manifest.admin'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertSee('ISP Manifest Admin');

    $this->get(route('portal.manifest'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertSee('Portal Manifest');

    $this->get(route('manifest.admin.icon', ['size' => 192]))->assertSuccessful();
    $this->get(route('portal.icon', ['size' => 192]))->assertSuccessful();
    $this->get(route('push.vapid-key'))->assertSuccessful()->assertJson([
        'publicKey' => 'test-vapid-public-key',
    ]);
});

it('stores and removes staff push subscriptions', function () {
    $user = User::factory()->superAdmin()->create();

    $payload = [
        'endpoint' => 'https://push.example.test/admin-endpoint',
        'keys' => [
            'p256dh' => 'public-key',
            'auth' => 'auth-token',
        ],
    ];

    $this->actingAs($user)
        ->postJson(route('push.subscribe'), $payload)
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect(PushSubscription::query()->where('subscribable_type', User::class)->exists())->toBeTrue();

    $this->actingAs($user)
        ->deleteJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect(PushSubscription::query()->where('subscribable_type', User::class)->exists())->toBeFalse();
});

it('stores and removes portal push subscriptions', function () {
    $pppUser = makePortalReadyUser();
    $token = makePortalToken($pppUser);

    $payload = [
        'endpoint' => 'https://push.example.test/portal-endpoint',
        'keys' => [
            'p256dh' => 'portal-public-key',
            'auth' => 'portal-auth-token',
        ],
    ];

    $this->withCookie('portal_session', $token)
        ->post(route('portal.push.subscribe'), $payload, ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect(PushSubscription::query()->where('subscribable_type', PppUser::class)->exists())->toBeTrue();

    $this->withCookie('portal_session', $token)
        ->delete(route('portal.push.unsubscribe'), ['endpoint' => $payload['endpoint']], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect(PushSubscription::query()->where('subscribable_type', PppUser::class)->exists())->toBeFalse();
});
