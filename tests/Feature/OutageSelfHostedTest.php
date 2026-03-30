<?php

use App\Models\Outage;
use App\Models\OutageUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows outage pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $outage = Outage::factory()->create([
        'title' => 'Fiber Cut Jalur Barat',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.outages.index'))
        ->assertSuccessful()
        ->assertSee('Gangguan Jaringan')
        ->assertSee('Fiber Cut Jalur Barat');

    $this->actingAs($user)
        ->get(route('super-admin.outages.show', $outage))
        ->assertSuccessful()
        ->assertSee('Fiber Cut Jalur Barat');
});

it('blocks non super admin users from outage pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.outages.index'))
        ->assertForbidden();
});

it('creates updates resolves and exposes public outage status', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('super-admin.outages.store'), [
            'title' => 'Gangguan Backbone Kota',
            'description' => 'Ada gangguan pada jalur utama.',
            'severity' => 'critical',
            'started_at' => '2026-03-30 08:00:00',
            'estimated_resolved_at' => '2026-03-30 12:00:00',
            'custom_areas' => ['Area Barat', 'Cluster Timur'],
            'include_status_link' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $outage = Outage::query()->first();

    expect($outage)->not->toBeNull()
        ->and($outage->status)->toBe('open')
        ->and($outage->public_token)->toHaveLength(32)
        ->and($outage->affectedAreas()->count())->toBe(2);

    $this->actingAs($user)
        ->post(route('super-admin.outages.updates.store', $outage), [
            'body' => 'Tim sedang pengecekan ODC.',
            'change_status' => 'in_progress',
            'is_public' => '1',
        ])
        ->assertRedirect(route('super-admin.outages.show', $outage))
        ->assertSessionHas('success');

    expect($outage->fresh()->status)->toBe('in_progress');

    $this->actingAs($user)
        ->post(route('super-admin.outages.resolve', $outage))
        ->assertRedirect(route('super-admin.outages.show', $outage))
        ->assertSessionHas('success');

    expect($outage->fresh()->status)->toBe('resolved')
        ->and($outage->fresh()->resolved_at)->not->toBeNull();

    expect(OutageUpdate::query()->where('outage_id', $outage->id)->count())->toBe(3);

    $this->get(route('outage.public-status', $outage->public_token))
        ->assertSuccessful()
        ->assertSee('Gangguan Backbone Kota')
        ->assertSee('Area Barat')
        ->assertSee('Cluster Timur')
        ->assertSee('Tim sedang pengecekan ODC.')
        ->assertSee('Layanan dinyatakan pulih.');
});
