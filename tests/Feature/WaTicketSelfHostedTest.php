<?php

use App\Models\ActivityLog;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\User;
use App\Models\WaTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

function makePppTicketCustomer(array $attributes = []): PppUser
{
    $profileGroup = ProfileGroup::factory()->create();
    $profile = PppProfile::factory()->create([
        'name' => 'Paket Tiket',
        'profile_group_id' => $profileGroup->id,
    ]);

    return PppUser::factory()->create(array_merge([
        'customer_id' => '000000050001',
        'customer_name' => 'Pelanggan Tiket WA',
        'nomor_hp' => '6282222222222',
        'ppp_profile_id' => $profile->id,
        'profile_group_id' => $profileGroup->id,
    ], $attributes));
}

it('shows wa ticket pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $ticket = WaTicket::factory()->create([
        'title' => 'Gangguan routing',
        'customer_name' => 'Pelanggan WA',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.wa-tickets.index'))
        ->assertSuccessful()
        ->assertSee('Tiket WhatsApp')
        ->assertSee('Gangguan routing');

    $this->actingAs($user)
        ->get(route('super-admin.wa-tickets.show', $ticket))
        ->assertSuccessful()
        ->assertSee('Detail Tiket')
        ->assertSee('Gangguan routing');
});

it('blocks non super admin users from wa ticket pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.wa-tickets.index'))
        ->assertForbidden();
});

it('creates assigns updates and adds notes to wa tickets in single tenant mode', function () {
    $admin = User::factory()->superAdmin()->create();
    $assignee = User::factory()->create([
        'name' => 'Petugas Lapangan',
    ]);
    $pppUser = makePppTicketCustomer();

    $this->actingAs($admin)
        ->post(route('super-admin.wa-tickets.store'), [
            'ppp_user_id' => $pppUser->id,
            'title' => 'Internet mati total',
            'description' => 'Pelanggan tidak bisa online sejak pagi.',
            'type' => 'complaint',
            'priority' => 'high',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket = WaTicket::query()->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->customer_name)->toBe($pppUser->customer_name)
        ->and($ticket->customer_phone)->toBe($pppUser->nomor_hp)
        ->and($ticket->customer_type)->toBe('ppp')
        ->and($ticket->status)->toBe('open')
        ->and($ticket->public_token)->not->toBeEmpty();

    $this->actingAs($admin)
        ->post(route('super-admin.wa-tickets.assign', $ticket), [
            'assigned_to_id' => $assignee->id,
        ])
        ->assertRedirect(route('super-admin.wa-tickets.show', $ticket))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->put(route('super-admin.wa-tickets.update', $ticket), [
            'title' => 'Internet mati total',
            'description' => 'Gangguan terkonfirmasi dari sisi distribusi.',
            'status' => 'resolved',
            'priority' => 'high',
        ])
        ->assertRedirect(route('super-admin.wa-tickets.show', $ticket))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->post(route('super-admin.wa-tickets.notes.store', $ticket), [
            'note' => 'ODP dibersihkan dan koneksi pelanggan kembali normal.',
        ])
        ->assertRedirect(route('super-admin.wa-tickets.show', $ticket))
        ->assertSessionHas('success');

    $ticket->refresh();

    expect($ticket->assigned_to_id)->toBe($assignee->id)
        ->and($ticket->status)->toBe('resolved')
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->notes()->count())->toBe(4);

    expect(ActivityLog::query()->where('action', 'wa_ticket_created')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'wa_ticket_assigned')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'wa_ticket_updated')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'wa_ticket_noted')->exists())->toBeTrue();

    $this->get(route('ticket.public-progress', $ticket->public_token))
        ->assertSuccessful()
        ->assertSee('Internet mati total')
        ->assertSee('Pelanggan Tiket WA')
        ->assertSee('ODP dibersihkan dan koneksi pelanggan kembali normal.');
});
