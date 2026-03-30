<?php

use App\Models\User;
use App\Models\WaTicket;
use App\Models\WaTicketNote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('shows the help center and help topics for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.help.index'))
        ->assertSuccessful()
        ->assertSee('Pusat Bantuan')
        ->assertSee('PPPoE dan Pelanggan')
        ->assertSee('System Tools dan Audit');

    $this->actingAs($user)
        ->get(route('super-admin.help.topic', 'pppoe'))
        ->assertSuccessful()
        ->assertSee('PPPoE dan Pelanggan')
        ->assertSee('Invoice dapat dibuat langsung dari pelanggan PPP');
});

it('blocks non super admin users from the help center', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.help.index'))
        ->assertForbidden();
});

it('returns not found for unknown help topics', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.help.topic', 'tidak-ada'))
        ->assertNotFound();
});

it('shows public ticket progress without authentication', function () {
    $assignedTo = User::factory()->superAdmin()->create([
        'name' => 'Teknisi Self Hosted',
    ]);
    $ticket = WaTicket::factory()->create([
        'customer_name' => 'Pelanggan Tiket',
        'customer_phone' => '6281111111111',
        'title' => 'Internet lambat',
        'description' => 'Pelanggan melaporkan throughput turun drastis.',
        'status' => 'in_progress',
        'assigned_to_id' => $assignedTo->id,
        'assigned_by_id' => $assignedTo->id,
        'public_token' => 'ticket-public-token',
    ]);

    WaTicketNote::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $assignedTo->id,
        'type' => 'created',
        'meta' => 'Tiket dibuat oleh admin.',
        'note' => null,
    ]);

    WaTicketNote::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $assignedTo->id,
        'type' => 'note',
        'note' => 'Teknisi sedang melakukan pengecekan di sisi OLT.',
    ]);

    $this->get(route('ticket.public-progress', 'ticket-public-token'))
        ->assertSuccessful()
        ->assertSee('Internet lambat')
        ->assertSee('Pelanggan Tiket')
        ->assertSee('Teknisi Self Hosted')
        ->assertSee('Teknisi sedang melakukan pengecekan di sisi OLT.');
});

it('returns not found for an unknown public ticket token', function () {
    $this->get(route('ticket.public-progress', 'missing-token'))
        ->assertNotFound();
});
