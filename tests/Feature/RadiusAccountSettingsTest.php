<?php

use App\Models\MikrotikConnection;
use App\Models\RadiusAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows the radius accounts page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    RadiusAccount::factory()->create([
        'username' => 'user-pppoe',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.settings.radius-accounts.index'))
        ->assertSuccessful()
        ->assertSee('Akun RADIUS')
        ->assertSee('user-pppoe')
        ->assertSee('Tambah Akun RADIUS');
});

it('blocks non super admin users from the radius accounts page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.radius-accounts.index'))
        ->assertForbidden();
});

it('creates updates and deletes a radius account', function () {
    $user = User::factory()->superAdmin()->create();
    $connection = MikrotikConnection::factory()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.radius-accounts.store'), [
            'mikrotik_connection_id' => $connection->id,
            'username' => 'pppoe-user',
            'password' => 'secret123',
            'service' => 'pppoe',
            'ipv4_address' => '10.10.10.10',
            'rate_limit' => '10M/10M',
            'profile' => 'PPPOE-10M',
            'notes' => 'Akun pelanggan',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.radius-accounts.index'))
        ->assertSessionHas('success');

    $account = RadiusAccount::query()->first();

    expect($account)->not->toBeNull()
        ->and($account->username)->toBe('pppoe-user')
        ->and($account->mikrotik_connection_id)->toBe($connection->id);

    $this->actingAs($user)
        ->put(route('super-admin.settings.radius-accounts.update', $account), [
            'mikrotik_connection_id' => $connection->id,
            'username' => 'pppoe-user-baru',
            'password' => 'secret456',
            'service' => 'hotspot',
            'rate_limit' => '20M/20M',
            'profile' => 'HOTSPOT-20M',
            'notes' => 'Akun diperbarui',
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.settings.radius-accounts.index'))
        ->assertSessionHas('success');

    expect($account->fresh()->username)->toBe('pppoe-user-baru')
        ->and($account->fresh()->service)->toBe('hotspot')
        ->and($account->fresh()->ipv4_address)->toBeNull();

    $this->actingAs($user)
        ->delete(route('super-admin.settings.radius-accounts.destroy', $account))
        ->assertRedirect(route('super-admin.settings.radius-accounts.index'))
        ->assertSessionHas('success');

    expect(RadiusAccount::query()->count())->toBe(0);
});
