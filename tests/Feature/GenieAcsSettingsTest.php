<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->genieacsDirectory = storage_path('framework/testing/genieacs');
    $this->genieacsLogPath = $this->genieacsDirectory.'/genieacs.log';

    File::deleteDirectory($this->genieacsDirectory);
    File::ensureDirectoryExists($this->genieacsDirectory);
    File::put($this->genieacsLogPath, "genieacs-nbi ready\n");

    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('genieacs.nbi_url', 'http://127.0.0.1:7557');
    config()->set('genieacs.ui_url', 'http://127.0.0.1:3000');
    config()->set('genieacs.username', 'genieacs');
    config()->set('genieacs.password', 'secret');
    config()->set('genieacs.log_path', $this->genieacsLogPath);
    config()->set('genieacs.online_threshold_minutes', 70);
    config()->set('genieacs.services', [
        'cwmp' => [
            'label' => 'GenieACS CWMP',
            'status_command' => 'genieacsctl status cwmp',
            'restart_command' => 'genieacsctl restart cwmp',
        ],
        'nbi' => [
            'label' => 'GenieACS NBI',
            'status_command' => 'genieacsctl status nbi',
            'restart_command' => 'genieacsctl restart nbi',
        ],
        'fs' => [
            'label' => 'GenieACS FS',
            'status_command' => 'genieacsctl status fs',
            'restart_command' => 'genieacsctl restart fs',
        ],
    ]);

    Process::fake([
        'genieacsctl status cwmp' => Process::result('active', '', 0),
        'genieacsctl status nbi' => Process::result('active', '', 0),
        'genieacsctl status fs' => Process::result('active', '', 0),
        'genieacsctl restart cwmp' => Process::result('restarted cwmp', '', 0),
        'genieacsctl restart nbi' => Process::result('restarted nbi', '', 0),
        'genieacsctl restart fs' => Process::result('restarted fs', '', 0),
    ]);

    Http::fake(function ($request) {
        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/devices/?limit=500') {
            return Http::response([
                ['_id' => 'ONU-001', '_lastInform' => now()->subMinutes(5)->toIso8601String()],
                ['_id' => 'ONU-002', '_lastInform' => now()->subMinutes(120)->toIso8601String()],
            ], 200);
        }

        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/devices/?limit=1') {
            return Http::response([['_id' => 'ONU-001']], 200);
        }

        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/tasks?limit=500') {
            return Http::response([['_id' => 'task-1']], 200);
        }

        if ($request->method() === 'GET' && $request->url() === 'http://127.0.0.1:7557/faults?limit=500') {
            return Http::response([['_id' => 'fault-1']], 200);
        }

        if ($request->method() === 'POST' && $request->url() === 'http://127.0.0.1:7557/devices/ONU-001/tasks?connection_request&timeout=3000') {
            return Http::response(['_id' => 'task-cr-1'], 200);
        }

        if ($request->method() === 'GET' && str_starts_with($request->url(), 'http://127.0.0.1:7557/tasks/?query=')) {
            return Http::response([
                ['_id' => 'task-1'],
                ['_id' => 'task-2'],
            ], 200);
        }

        if ($request->method() === 'DELETE' && in_array($request->url(), [
            'http://127.0.0.1:7557/tasks/task-1',
            'http://127.0.0.1:7557/tasks/task-2',
        ], true)) {
            return Http::response([], 200);
        }

        return Http::response([], 200);
    });
});

afterEach(function (): void {
    File::deleteDirectory($this->genieacsDirectory);
});

it('shows the genieacs page for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.genieacs.index'))
        ->assertSuccessful()
        ->assertSee('GenieACS')
        ->assertSee('Total Device')
        ->assertSee('2')
        ->assertSee('Device Online');
});

it('blocks non super admin users from the genieacs page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.settings.genieacs.index'))
        ->assertForbidden();
});

it('tests genieacs nbi connectivity from the settings page', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.genieacs.test-connection'))
        ->assertRedirect(route('super-admin.settings.genieacs.index'))
        ->assertSessionHas('success');
});

it('restarts a genieacs service from the settings page', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.genieacs.service', 'restart-nbi'))
        ->assertRedirect(route('super-admin.settings.genieacs.index'))
        ->assertSessionHas('success');

    Process::assertRan('genieacsctl restart nbi');
});

it('sends a genieacs connection request for a device', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.settings.genieacs.devices.connection-request'), [
            'device_id' => 'ONU-001',
            'profile' => 'igd',
        ])
        ->assertRedirect(route('super-admin.settings.genieacs.index'))
        ->assertSessionHas('success');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'http://127.0.0.1:7557/devices/ONU-001/tasks?connection_request&timeout=3000');
});

it('clears pending genieacs tasks for a device', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->delete(route('super-admin.settings.genieacs.devices.clear-tasks'), [
            'device_id' => 'ONU-001',
        ])
        ->assertRedirect(route('super-admin.settings.genieacs.index'))
        ->assertSessionHas('success');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'http://127.0.0.1:7557/tasks/?query='));
});
