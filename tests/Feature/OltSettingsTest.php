<?php

use App\Models\OltConnection;
use App\Models\OltOnuOptic;
use App\Models\User;
use App\Services\HsgqSnmpCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows the olt page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    OltConnection::factory()->create([
        'name' => 'OLT HSGQ Watu 01',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.settings.olt.index'))
        ->assertSuccessful()
        ->assertSee('Monitoring OLT')
        ->assertSee('OLT HSGQ Watu 01')
        ->assertSee('Tambah Koneksi OLT');
});

it('blocks non super admin users from the olt page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.olt.index'))
        ->assertForbidden();
});

it('creates updates and deletes an olt connection', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.olt.store'), [
            'vendor' => 'hsgq',
            'name' => 'OLT Pusat',
            'olt_model' => 'HSGQ GPON 8 PON',
            'host' => '10.10.10.1',
            'snmp_port' => 161,
            'snmp_version' => '2c',
            'snmp_community' => 'public',
            'snmp_write_community' => 'private',
            'snmp_timeout' => 5,
            'snmp_retries' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $connection = OltConnection::query()->first();

    expect($connection)->not->toBeNull()
        ->and($connection->name)->toBe('OLT Pusat');

    $this->actingAs($user)
        ->put(route('super-admin.settings.olt.update', $connection), [
            'vendor' => 'hsgq',
            'name' => 'OLT Cabang',
            'olt_model' => 'HSGQ GPON 4 PON',
            'host' => '10.10.10.2',
            'snmp_port' => 161,
            'snmp_version' => '2c',
            'snmp_community' => 'public2',
            'snmp_write_community' => 'private2',
            'snmp_timeout' => 6,
            'snmp_retries' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($connection->fresh()->name)->toBe('OLT Cabang')
        ->and($connection->fresh()->host)->toBe('10.10.10.2');

    $this->actingAs($user)
        ->delete(route('super-admin.settings.olt.destroy', $connection))
        ->assertRedirect(route('super-admin.settings.olt.index'))
        ->assertSessionHas('success');

    expect(OltConnection::query()->count())->toBe(0);
});

it('auto detects olt model from snmp', function () {
    $user = User::factory()->superAdmin()->create();
    $connection = OltConnection::factory()->create([
        'olt_model' => null,
        'host' => '10.10.10.1',
    ]);

    Process::fake(function ($process) {
        if (str_contains($process->command, '.1.3.6.1.2.1.1.1.0')) {
            return Process::result('.1.3.6.1.2.1.1.1.0 = STRING: "HSGQ GPON 8 PON"', '', 0);
        }

        if (str_contains($process->command, '.1.3.6.1.2.1.1.2.0')) {
            return Process::result('.1.3.6.1.2.1.1.2.0 = OID: .1.3.6.1.4.1.5875.800', '', 0);
        }

        return Process::result('', 'unexpected command', 1);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.olt.detect-model', $connection))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($connection->fresh()->olt_model)->toBe('HSGQ GPON 8 PON');
});

it('auto detects oid mapping from the selected model', function () {
    $user = User::factory()->superAdmin()->create();
    $connection = OltConnection::factory()->create([
        'olt_model' => 'HSGQ GPON 8 PON',
    ]);

    Process::fake(function ($process) {
        if (str_contains($process->command, '.1.3.6.1.4.1.5875.800.3.1.1.1.1.2')) {
            return Process::result('.1.3.6.1.4.1.5875.800.3.1.1.1.1.2.16777473 = Hex-STRING: D0 60 8C BC BD C3', '', 0);
        }

        return Process::result('', 'no response', 1);
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.olt.detect-oid', $connection))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($connection->fresh()->oid_serial)->toBe('1.3.6.1.4.1.5875.800.3.1.1.1.1.2')
        ->and($connection->fresh()->oid_onu_name)->toBe('1.3.6.1.4.1.5875.800.3.1.1.1.1.3');
});

it('polls olt optics and stores the latest onu data', function () {
    $user = User::factory()->superAdmin()->create();
    $connection = OltConnection::factory()->create();

    app()->instance(HsgqSnmpCollector::class, new class extends HsgqSnmpCollector
    {
        public function collectEssential(OltConnection $oltConnection): array
        {
            return [[
                'onu_index' => '16777473',
                'pon_interface' => 'PON1',
                'onu_number' => '1',
                'serial_number' => 'D0 60 8C BC BD C3',
                'onu_name' => 'ONU01',
                'distance_m' => 3798,
                'rx_onu_dbm' => -20.17,
                'status' => 'online',
                'raw_payload' => [
                    'distance' => '3798',
                    'rx_onu' => '-2017',
                    'status' => '1',
                ],
            ]];
        }
    });

    $this->actingAs($user)
        ->post(route('super-admin.settings.olt.poll', $connection))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($connection->fresh()->last_poll_success)->toBeTrue()
        ->and(OltOnuOptic::query()->where('olt_connection_id', $connection->id)->count())->toBe(1)
        ->and(OltOnuOptic::query()->where('olt_connection_id', $connection->id)->value('pon_interface'))->toBe('PON1');
});
