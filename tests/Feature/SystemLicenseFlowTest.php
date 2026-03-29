<?php

use App\Models\SystemLicense;
use App\Models\User;
use App\Services\FeatureGateService;
use App\Services\LicenseFingerprintService;
use App\Services\LicenseSignatureService;
use App\Services\SystemLicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->licensePath = storage_path('framework/testing/license/rafen.lic');
    $this->activationRequestPath = storage_path('framework/testing/license/activation-request.json');
    $this->machineIdPath = storage_path('framework/testing/license/machine-id');
    $this->keyPair = sodium_crypto_sign_keypair();
    $this->publicKey = base64_encode(sodium_crypto_sign_publickey($this->keyPair));
    $this->secretKey = sodium_crypto_sign_secretkey($this->keyPair);

    File::ensureDirectoryExists(dirname($this->licensePath));
    File::put($this->machineIdPath, 'machine-id-for-tests');
    File::delete($this->licensePath);

    config()->set('app.url', 'https://self-hosted.test');
    config()->set('app.env', 'testing');
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', true);
    config()->set('license.public_key', $this->publicKey);
    config()->set('license.path', $this->licensePath);
    config()->set('license.machine_id_path', $this->machineIdPath);
    config()->set('license.default_grace_days', 21);
});

afterEach(function (): void {
    File::delete($this->licensePath);
    File::delete($this->activationRequestPath);
    File::delete($this->machineIdPath);
});

it('uploads and validates a signed system license', function () {
    $user = User::factory()->superAdmin()->create();
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->addMonth()->toDateString(), modules: ['vpn', 'wa']);

    $this->actingAs($user)
        ->post(route('super-admin.settings.license.update'), [
            'license_file' => UploadedFile::fake()->createWithContent(
                'rafen.lic',
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ),
        ])
        ->assertRedirect(route('super-admin.settings.license'))
        ->assertSessionHas('success');

    $license = SystemLicense::query()->first();

    expect($license)->not->toBeNull()
        ->and($license->status)->toBe('active')
        ->and($license->license_id)->toBe('RAFEN-SH-TEST-0001')
        ->and($license->modules)->toBe(['vpn', 'wa']);
});

it('rejects an uploaded license when the signature is invalid', function () {
    $user = User::factory()->superAdmin()->create();
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->addMonth()->toDateString());
    $payload['customer_name'] = 'Tampered Customer';

    $this->actingAs($user)
        ->post(route('super-admin.settings.license.update'), [
            'license_file' => UploadedFile::fake()->createWithContent(
                'rafen-invalid.lic',
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ),
        ])
        ->assertRedirect(route('super-admin.settings.license'))
        ->assertSessionHas('error');

    $license = SystemLicense::query()->first();

    expect($license)->not->toBeNull()
        ->and($license->status)->toBe('invalid')
        ->and($license->validation_error)->toBe('Signature lisensi tidak valid.');
});

it('keeps an expired license valid during grace period', function () {
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->subDays(3)->toDateString(), graceDays: 7);

    File::put($this->licensePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $license = app(SystemLicenseService::class)->syncFromDisk();

    expect($license->status)->toBe('grace')
        ->and($license->is_valid)->toBeTrue()
        ->and(app(SystemLicenseService::class)->allowsAccess())->toBeTrue();
});

it('enters restricted mode after grace period ends and blocks gated routes', function () {
    Route::middleware(['web', 'auth', 'system.license'])
        ->get('/_test/system-license-gated', fn () => 'ok');

    $user = User::factory()->superAdmin()->create();
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->subDays(10)->toDateString(), graceDays: 3);

    File::put($this->licensePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $license = app(SystemLicenseService::class)->syncFromDisk();

    $this->actingAs($user)
        ->get('/_test/system-license-gated')
        ->assertRedirect(route('super-admin.settings.license'))
        ->assertSessionHas('error');

    expect($license->status)->toBe('restricted')
        ->and($license->is_valid)->toBeFalse()
        ->and(app(SystemLicenseService::class)->allowsAccess())->toBeFalse();
});

it('blocks features that are not included in the active license modules', function () {
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->addMonth()->toDateString(), modules: ['vpn']);

    File::put($this->licensePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    app(SystemLicenseService::class)->syncFromDisk();

    $featureGateService = app(FeatureGateService::class);

    expect($featureGateService->isEnabled('vpn'))->toBeTrue()
        ->and($featureGateService->isEnabled('wa'))->toBeFalse()
        ->and($featureGateService->message('wa'))->toContain('WhatsApp Gateway');
});

it('downloads an activation request from the super admin page', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this->actingAs($user)
        ->get(route('super-admin.settings.license.activation-request'));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/json');

    $payload = json_decode($response->streamedContent(), true);

    expect($payload)->toBeArray()
        ->and($payload['app_name'])->toBe(config('app.name'))
        ->and($payload['current_license_status'])->toBe('missing')
        ->and($payload['fingerprint'])->toBe(app(LicenseFingerprintService::class)->generate());
});

it('writes an activation request file from the cli command', function () {
    $this->artisan("license:activation-request --path={$this->activationRequestPath}")
        ->expectsOutputToContain('Activation request disimpan ke:')
        ->assertExitCode(0);

    expect(File::exists($this->activationRequestPath))->toBeTrue();

    $payload = json_decode((string) File::get($this->activationRequestPath), true);

    expect($payload)->toBeArray()
        ->and($payload['current_license_status'])->toBe('missing')
        ->and($payload['fingerprint'])->toBe(app(LicenseFingerprintService::class)->generate());
});

it('reports active status from the cli after syncing a valid license', function () {
    $payload = makeSignedLicensePayload($this->secretKey, expiresAt: now()->addMonth()->toDateString(), modules: ['vpn', 'wa']);

    File::put($this->licensePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    app(SystemLicenseService::class)->syncFromDisk();

    Artisan::call('license:status', ['--json' => true]);
    $output = Artisan::output();
    $decoded = json_decode($output, true);

    expect($decoded)->toBeArray()
        ->and($decoded['status'])->toBe('active')
        ->and($decoded['is_valid'])->toBeTrue()
        ->and($decoded['license_id'])->toBe('RAFEN-SH-TEST-0001')
        ->and($decoded['modules'])->toBe(['vpn', 'wa']);
});

/**
 * @param  list<string>  $modules
 * @param  array<string, mixed>  $limits
 * @return array<string, mixed>
 */
function makeSignedLicensePayload(
    string $secretKey,
    string $expiresAt,
    int $graceDays = 21,
    array $modules = ['vpn'],
    array $limits = ['max_mikrotik' => 10],
): array {
    $payload = [
        'license_id' => 'RAFEN-SH-TEST-0001',
        'customer_name' => 'PT Test Self Hosted',
        'instance_name' => 'production',
        'issued_at' => now()->toDateString(),
        'expires_at' => $expiresAt,
        'support_until' => now()->addYear()->toDateString(),
        'grace_days' => $graceDays,
        'fingerprint' => app(LicenseFingerprintService::class)->generate(),
        'domains' => ['self-hosted.test'],
        'modules' => $modules,
        'limits' => $limits,
    ];

    $signature = sodium_crypto_sign_detached(
        app(LicenseSignatureService::class)->canonicalize($payload),
        $secretKey,
    );

    $payload['signature'] = base64_encode($signature);

    return $payload;
}
