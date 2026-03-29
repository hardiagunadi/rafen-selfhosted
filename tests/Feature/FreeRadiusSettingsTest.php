<?php

use App\Models\RadiusNas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->radiusDirectory = storage_path('framework/testing/radius');
    $this->radiusClientsPath = $this->radiusDirectory.'/clients-selfhosted.conf';
    $this->radiusLogPath = $this->radiusDirectory.'/freeradius.log';

    File::deleteDirectory($this->radiusDirectory);
    File::ensureDirectoryExists($this->radiusDirectory);
    File::put($this->radiusLogPath, "radiusd: ready to process requests\n");

    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('radius.clients_path', $this->radiusClientsPath);
    config()->set('radius.log_path', $this->radiusLogPath);
    config()->set('radius.reload_command', 'radiusctl reload');
    config()->set('radius.restart_command', 'radiusctl restart');
    config()->set('radius.status_command', 'radiusctl status');

    Process::fake([
        'radiusctl reload' => Process::result('reloaded', '', 0),
        'radiusctl restart' => Process::result('restarted', '', 0),
        'radiusctl status' => Process::result('active', '', 0),
    ]);
});

afterEach(function (): void {
    File::deleteDirectory($this->radiusDirectory);
});

it('shows the freeradius page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.freeradius.index'))
        ->assertSuccessful()
        ->assertSee('FreeRADIUS')
        ->assertSee('Tambah NAS');
});

it('blocks non super admin users from the freeradius page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.freeradius.index'))
        ->assertForbidden();
});

it('creates and updates a radius nas client', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.freeradius.nas.store'), [
            'name' => 'MikroTik Cabang A',
            'shortname' => 'cabang_a',
            'ip_address' => '10.10.10.1',
            'secret' => 'radius-secret',
            'require_message_authenticator' => '1',
            'auth_port' => 1812,
            'acct_port' => 1813,
            'is_active' => '1',
            'notes' => 'Router utama',
        ])
        ->assertRedirect(route('super-admin.settings.freeradius.index'))
        ->assertSessionHas('success');

    $radiusNas = RadiusNas::query()->first();

    expect($radiusNas)->not->toBeNull()
        ->and($radiusNas->shortname)->toBe('cabang_a');

    $this->actingAs($user)
        ->put(route('super-admin.settings.freeradius.nas.update', $radiusNas), [
            'name' => 'MikroTik Cabang A',
            'shortname' => 'cabang_a_main',
            'ip_address' => '10.10.10.2',
            'secret' => 'radius-secret-new',
            'auth_port' => 18120,
            'acct_port' => 18130,
            'notes' => 'Router utama update',
        ])
        ->assertRedirect(route('super-admin.settings.freeradius.index'))
        ->assertSessionHas('success');

    expect($radiusNas->fresh()->shortname)->toBe('cabang_a_main')
        ->and($radiusNas->fresh()->ip_address)->toBe('10.10.10.2')
        ->and($radiusNas->fresh()->require_message_authenticator)->toBeFalse();
});

it('syncs radius nas clients into the clients file', function () {
    $user = User::factory()->superAdmin()->create();
    RadiusNas::factory()->create([
        'name' => 'MikroTik Pusat',
        'shortname' => 'mikrotik_pusat',
        'ip_address' => '10.20.30.1',
        'secret' => 'super-secret',
        'require_message_authenticator' => true,
    ]);

    $this->actingAs($user)
        ->post(route('super-admin.settings.freeradius.sync'))
        ->assertRedirect(route('super-admin.settings.freeradius.index'))
        ->assertSessionHas('success');

    expect(File::exists($this->radiusClientsPath))->toBeTrue();

    $payload = File::get($this->radiusClientsPath);

    expect($payload)
        ->toContain('client mikrotik_pusat {')
        ->toContain('ipaddr = 10.20.30.1')
        ->toContain('secret = super-secret')
        ->toContain('require_message_authenticator = yes');

    Process::assertRan('radiusctl reload');
});

it('restarts freeradius service from the settings page', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.freeradius.service', 'restart'))
        ->assertRedirect(route('super-admin.settings.freeradius.index'))
        ->assertSessionHas('success');

    Process::assertRan('radiusctl restart');
});

it('deletes a radius nas client', function () {
    $user = User::factory()->superAdmin()->create();
    $radiusNas = RadiusNas::factory()->create();

    $this->actingAs($user)
        ->delete(route('super-admin.settings.freeradius.nas.destroy', $radiusNas))
        ->assertRedirect(route('super-admin.settings.freeradius.index'))
        ->assertSessionHas('success');

    expect(RadiusNas::query()->count())->toBe(0);
});
