<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows terminal page for super admin and blocks non admin users', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.terminal.index'))
        ->assertSuccessful()
        ->assertSee('Terminal Super Admin')
        ->assertSee('Quick Command');

    $this->actingAs($user)
        ->get(route('super-admin.terminal.index'))
        ->assertForbidden();
});

it('rejects commands outside the allow list', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->postJson(route('super-admin.terminal.run'), [
            'command' => 'rm -rf /',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'super_admin_terminal_rejected',
    ]);
});

it('runs allowed artisan commands and stores an activity log', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
        ->postJson(route('super-admin.terminal.run'), [
            'command' => 'php artisan about --only=environment',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('command', 'php artisan about --only=environment');

    expect((string) $response->json('output'))->not->toBe('');

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'super_admin_terminal_run',
        'subject_type' => 'SystemCommand',
        'subject_label' => 'php artisan about --only=environment',
    ]);
});
