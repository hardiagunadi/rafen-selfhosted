<?php

use App\Models\ShiftDefinition;
use App\Models\ShiftSchedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows shift pages based on role permissions', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMINISTRATOR,
    ]);
    $viewer = User::factory()->create([
        'role' => User::ROLE_NOC,
    ]);
    $blocked = User::factory()->create([
        'role' => 'random',
    ]);

    $this->actingAs($admin)
        ->get(route('shifts.index'))
        ->assertSuccessful()
        ->assertSee('Jadwal Shift');

    $this->actingAs($viewer)
        ->get(route('shifts.my'))
        ->assertSuccessful()
        ->assertSee('Jadwal Shift Saya');

    $this->actingAs($viewer)
        ->get(route('shifts.index'))
        ->assertForbidden();

    $this->actingAs($blocked)
        ->get(route('shifts.my'))
        ->assertForbidden();
});

it('creates schedules and handles swap approval', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMINISTRATOR,
    ]);
    $requester = User::factory()->create([
        'role' => User::ROLE_TEKNISI,
    ]);
    $target = User::factory()->create([
        'role' => User::ROLE_NOC,
    ]);

    $definition = ShiftDefinition::factory()->create([
        'name' => 'Pagi',
    ]);

    $this->actingAs($admin)
        ->post(route('shifts.definitions.store'), [
            'name' => 'Malam',
            'start_time' => '20:00',
            'end_time' => '04:00',
            'color' => '#111827',
            'is_active' => '1',
        ])
        ->assertRedirect(route('shifts.index'))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->post(route('shifts.schedule.store'), [
            'user_id' => $requester->id,
            'shift_definition_id' => $definition->id,
            'schedule_date' => '2026-04-01',
        ])
        ->assertRedirect(route('shifts.index'))
        ->assertSessionHas('success');

    $targetSchedule = ShiftSchedule::factory()->create([
        'user_id' => $target->id,
        'shift_definition_id' => $definition->id,
        'schedule_date' => '2026-04-01',
    ]);

    $requesterSchedule = ShiftSchedule::query()->where('user_id', $requester->id)->firstOrFail();

    $this->actingAs($requester)
        ->post(route('shifts.swap-requests.store'), [
            'from_schedule_id' => $requesterSchedule->id,
            'to_schedule_id' => $targetSchedule->id,
            'target_id' => $target->id,
            'reason' => 'Tukar jadwal lapangan',
        ])
        ->assertRedirect(route('shifts.my'))
        ->assertSessionHas('success');

    $swap = ShiftSwapRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('shifts.swap-requests.review', $swap), [
            'action' => 'approve',
        ])
        ->assertRedirect(route('shifts.index'))
        ->assertSessionHas('success');

    $swap->refresh();
    $requesterSchedule->refresh();
    $targetSchedule->refresh();

    expect($swap->status)->toBe('approved')
        ->and($requesterSchedule->user_id)->toBe($target->id)
        ->and($targetSchedule->user_id)->toBe($requester->id)
        ->and($requesterSchedule->status)->toBe('swapped')
        ->and($targetSchedule->status)->toBe('swapped');
});

it('redirects non super admin users to my schedule after login', function () {
    $viewer = User::factory()->create([
        'role' => User::ROLE_CS,
        'email' => 'cs@example.com',
        'password' => 'password123',
    ]);

    $this->post(route('login.attempt'), [
        'email' => 'cs@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('shifts.my'));

    expect($viewer->fresh()?->last_login_at)->not->toBeNull();
});
