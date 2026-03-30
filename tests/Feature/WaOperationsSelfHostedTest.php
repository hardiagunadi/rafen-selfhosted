<?php

use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\User;
use App\Models\WaBlastLog;
use App\Models\WaConversation;
use App\Models\WaKeywordRule;
use App\Models\WaMultiSessionDevice;
use App\Models\WaWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    config()->set('wa.multi_session.host', '127.0.0.1');
    config()->set('wa.multi_session.port', 3100);
    config()->set('wa.multi_session.auth_token', 'env-token');
    config()->set('wa.multi_session.master_key', 'env-master-key');

    Http::fake([
        'http://127.0.0.1:3100/api/v2/send-message' => Http::response([
            'status' => true,
            'data' => [
                'messages' => [[
                    'status' => 'queued',
                ]],
            ],
        ], 200),
        '*' => Http::response(['status' => true], 200),
    ]);
});

it('shows wa operational pages for super admin and blocks non admin users', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('super-admin.wa-blast.index'))
        ->assertSuccessful()
        ->assertSee('WA Blast Single-Tenant');

    $this->actingAs($admin)
        ->get(route('super-admin.wa-chat.index'))
        ->assertSuccessful()
        ->assertSee('Inbox Percakapan');

    $this->actingAs($admin)
        ->get(route('super-admin.wa-keyword-rules.index'))
        ->assertSuccessful()
        ->assertSee('Tambah Rule Keyword');

    $this->actingAs($user)
        ->get(route('super-admin.wa-blast.index'))
        ->assertForbidden();
});

it('previews and sends wa blast to ppp and hotspot customers', function () {
    $admin = User::factory()->superAdmin()->create();
    $profileGroup = ProfileGroup::factory()->create();
    $pppProfile = PppProfile::factory()->create([
        'profile_group_id' => $profileGroup->id,
    ]);
    $hotspotProfile = HotspotProfile::factory()->create([
        'profile_group_id' => $profileGroup->id,
    ]);
    WaMultiSessionDevice::factory()->create([
        'session_id' => 'blast-device-default',
        'is_default' => true,
        'is_active' => true,
    ]);

    PppUser::factory()->create([
        'customer_name' => 'Pelanggan PPP Blast',
        'nomor_hp' => '6281111111111',
        'ppp_profile_id' => $pppProfile->id,
        'profile_group_id' => $profileGroup->id,
        'status_akun' => 'enable',
        'status_bayar' => 'belum_bayar',
    ]);

    HotspotUser::factory()->create([
        'customer_name' => 'Pelanggan Hotspot Blast',
        'nomor_hp' => '6281222222222',
        'hotspot_profile_id' => $hotspotProfile->id,
        'profile_group_id' => $profileGroup->id,
        'status_akun' => 'enable',
        'status_bayar' => 'belum_bayar',
    ]);

    $this->actingAs($admin)
        ->getJson(route('super-admin.wa-blast.preview', [
            'type' => 'all',
            'status_akun' => 'enable',
            'status_bayar' => 'belum_bayar',
        ]))
        ->assertSuccessful()
        ->assertJsonPath('count', 2);

    $this->actingAs($admin)
        ->postJson(route('super-admin.wa-blast.send'), [
            'type' => 'all',
            'status_akun' => 'enable',
            'status_bayar' => 'belum_bayar',
            'message' => 'Info maintenance malam ini.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('success_count', 2);

    Http::assertSentCount(2);
    expect(WaBlastLog::query()->count())->toBe(2);
});

it('stores keyword rules ingests webhook messages and manages chat conversations', function () {
    $admin = User::factory()->superAdmin()->create();
    $assignee = User::factory()->create([
        'name' => 'Petugas Chat',
    ]);

    $this->actingAs($admin)
        ->post(route('super-admin.wa-keyword-rules.store'), [
            'keywords_text' => 'gangguan, internet mati',
            'reply_text' => 'Tim kami sudah menerima laporan Anda.',
            'priority' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('super-admin.wa-keyword-rules.index'))
        ->assertSessionHas('success');

    expect(WaKeywordRule::query()->count())->toBe(1);

    $this->postJson(route('wa.webhook.message'), [
        'session' => 'device-chat-1',
        'sender' => '081234567890',
        'pushName' => 'Pelanggan Chat',
        'message' => 'Halo, internet saya gangguan total',
    ])->assertSuccessful()
        ->assertJsonPath('status', true);

    $conversation = WaConversation::query()->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->contact_name)->toBe('Pelanggan Chat')
        ->and($conversation->messages()->count())->toBe(2);

    $this->actingAs($admin)
        ->getJson(route('super-admin.wa-chat.conversations'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.contact_name', 'Pelanggan Chat');

    $this->actingAs($admin)
        ->getJson(route('super-admin.wa-chat.show', $conversation))
        ->assertSuccessful()
        ->assertJsonCount(2, 'messages');

    expect($conversation->fresh()->unread_count)->toBe(0);

    $this->actingAs($admin)
        ->postJson(route('super-admin.wa-chat.reply', $conversation), [
            'message' => 'Kami sedang cek jalur distribusi.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->actingAs($admin)
        ->postJson(route('super-admin.wa-chat.assign', $conversation), [
            'assigned_to_id' => $assignee->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->actingAs($admin)
        ->postJson(route('super-admin.wa-chat.resolve', $conversation))
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($conversation->fresh()->assigned_to_id)->toBe($assignee->id)
        ->and($conversation->fresh()->status)->toBe('resolved')
        ->and($conversation->fresh()->messages()->count())->toBe(3);

    $this->actingAs($admin)
        ->deleteJson(route('super-admin.wa-chat.destroy', $conversation))
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(WaConversation::query()->count())->toBe(0);
});

it('supports compat wa webhook session status auto-reply and ingest routes', function () {
    $device = WaMultiSessionDevice::factory()->create([
        'session_id' => 'device-compat-1',
        'is_active' => true,
    ]);

    $this->postJson(route('wa.webhook.session'), [
        'session' => 'device-compat-1',
        'status' => 'connected',
    ])->assertSuccessful()
        ->assertJsonPath('status', true);

    $this->postJson(route('wa.webhook.status'), [
        'session' => 'device-compat-1',
        'sender' => '6281111111111',
        'message_id' => 'status-message-1',
        'message_status' => 'READ',
    ])->assertSuccessful()
        ->assertJsonPath('status', true);

    $this->postJson(route('wa.webhook.auto-reply'), [
        'session' => 'device-compat-1',
        'sender' => '6281111111111',
        'message' => 'Balasan otomatis dari gateway',
        'status' => 'sent',
    ])->assertSuccessful()
        ->assertJsonPath('status', true);

    $this->postJson(route('wa.webhook.ingest'), [
        'session' => 'device-compat-1',
        'sender' => '081999999999',
        'pushName' => 'Pelanggan Ingest',
        'message' => 'Halo lewat endpoint ingest',
    ])->assertSuccessful()
        ->assertJsonPath('status', true);

    expect($device->fresh()->last_status)->toBe('connected')
        ->and(WaWebhookLog::query()->where('event_type', 'session')->count())->toBe(1)
        ->and(WaWebhookLog::query()->where('event_type', 'status')->count())->toBe(1)
        ->and(WaWebhookLog::query()->where('event_type', 'auto_reply')->count())->toBe(1)
        ->and(WaWebhookLog::query()->where('event_type', 'message')->count())->toBe(1)
        ->and(WaConversation::query()->where('contact_name', 'Pelanggan Ingest')->exists())->toBeTrue();
});
