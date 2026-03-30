<?php

use App\Models\Invoice;
use App\Models\PortalSession;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

function makePortalPppUser(array $attributes = []): PppUser
{
    $profileGroup = ProfileGroup::factory()->create();
    $profile = PppProfile::factory()->create([
        'name' => 'Paket Portal',
        'profile_group_id' => $profileGroup->id,
    ]);

    return PppUser::factory()->create(array_merge([
        'customer_id' => '000000030001',
        'customer_name' => 'Portal Pelanggan',
        'username' => 'portal-user',
        'nomor_hp' => '6281111111111',
        'password_clientarea' => 'portal-secret',
        'ppp_profile_id' => $profile->id,
        'profile_group_id' => $profileGroup->id,
    ], $attributes));
}

function makePortalSession(PppUser $pppUser, ?string $token = null): string
{
    $token ??= Str::random(64);

    PortalSession::query()->create([
        'ppp_user_id' => $pppUser->id,
        'token' => $token,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'last_activity_at' => now()->subHour(),
        'expires_at' => now()->addDay(),
    ]);

    return $token;
}

it('shows the portal login page', function () {
    $this->get(route('portal.login'))
        ->assertSuccessful()
        ->assertSee('Portal Pelanggan');
});

it('can login with plain text portal password using customer id', function () {
    $pppUser = makePortalPppUser([
        'password_clientarea' => 'mysecret',
    ]);

    $this->post(route('portal.login.post'), [
        'login' => $pppUser->customer_id,
        'password' => 'mysecret',
    ])->assertRedirect(route('portal.dashboard'));

    expect(PortalSession::query()->where('ppp_user_id', $pppUser->id)->exists())->toBeTrue();
});

it('can login with hashed portal password using username', function () {
    $pppUser = makePortalPppUser([
        'username' => 'portal-hash',
        'password_clientarea' => Hash::make('hash-secret'),
    ]);

    $this->post(route('portal.login.post'), [
        'login' => 'portal-hash',
        'password' => 'hash-secret',
    ])->assertRedirect(route('portal.dashboard'));
});

it('redirects unauthenticated portal requests to login', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    $this->get(route('portal.invoices'))->assertRedirect(route('portal.login'));
    $this->get(route('portal.account'))->assertRedirect(route('portal.login'));
});

it('redirects when the portal session is expired', function () {
    $pppUser = makePortalPppUser();
    $token = Str::random(64);

    PortalSession::query()->create([
        'ppp_user_id' => $pppUser->id,
        'token' => $token,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'last_activity_at' => now()->subDays(2),
        'expires_at' => now()->subMinute(),
    ]);

    $this->withCookie('portal_session', $token)
        ->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));
});

it('shows dashboard invoices and account pages for authenticated portal users', function () {
    $pppUser = makePortalPppUser([
        'customer_name' => 'Budi Portal',
    ]);
    $token = makePortalSession($pppUser);

    Invoice::factory()->create([
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $pppUser->ppp_profile_id,
        'customer_id' => $pppUser->customer_id,
        'customer_name' => $pppUser->customer_name,
        'paket_langganan' => 'Paket Portal',
        'total' => 111000,
        'status' => 'unpaid',
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $this->withCookie('portal_session', $token)
        ->get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSee('Budi Portal')
        ->assertSee('Tagihan Terakhir');

    $this->withCookie('portal_session', $token)
        ->get(route('portal.invoices'))
        ->assertSuccessful()
        ->assertSee('Riwayat Tagihan')
        ->assertSee('Paket Portal');

    $this->withCookie('portal_session', $token)
        ->get(route('portal.account'))
        ->assertSuccessful()
        ->assertSee('Akun Portal')
        ->assertSee('Budi Portal');
});

it('allows portal users to change their password', function () {
    $pppUser = makePortalPppUser([
        'password_clientarea' => 'old-secret',
    ]);
    $token = makePortalSession($pppUser);

    $this->withCookie('portal_session', $token)
        ->post(
            route('portal.change-password'),
            [
                'current_password' => 'old-secret',
                'new_password' => 'new-secret',
                'new_password_confirmation' => 'new-secret',
            ],
            ['Accept' => 'application/json']
        )
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
        ]);

    expect(Hash::check('new-secret', (string) $pppUser->fresh()->password_clientarea))->toBeTrue();
});

it('logs portal users out and deletes the portal session', function () {
    $pppUser = makePortalPppUser();
    $token = makePortalSession($pppUser);

    $this->withCookie('portal_session', $token)
        ->post(route('portal.logout'))
        ->assertRedirect(route('portal.login'));

    expect(PortalSession::query()->where('token', $token)->exists())->toBeFalse();
});
