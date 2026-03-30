<?php

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
    Storage::fake('local');
});

it('shows activity logs and system tools pages for a super admin', function () {
    $user = User::factory()->superAdmin()->create();

    ActivityLog::factory()->create([
        'user_id' => $user->id,
        'action' => 'backup_created',
        'subject_type' => 'Database',
        'subject_label' => 'backup_demo.json.gz',
    ]);

    $this->actingAs($user)
        ->get(route('super-admin.logs.activity'))
        ->assertSuccessful()
        ->assertSee('Log Aktivitas')
        ->assertSee('backup_demo.json.gz');

    $this->actingAs($user)
        ->get(route('super-admin.tools.backup'))
        ->assertSuccessful()
        ->assertSee('Backup Database')
        ->assertSee('File Backup Tersimpan')
        ->assertSee('Ekspor Transaksi');

    $this->actingAs($user)
        ->get(route('super-admin.tools.export-transactions'))
        ->assertSuccessful()
        ->assertSee('Export Transaksi')
        ->assertSee('Download CSV');
});

it('blocks non super admin users from logs and system tools pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('super-admin.logs.activity'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('super-admin.tools.backup'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('super-admin.tools.export-transactions'))
        ->assertForbidden();
});

it('creates downloads deletes backups and exports invoice transactions', function () {
    $user = User::factory()->superAdmin()->create();
    $profileGroup = ProfileGroup::factory()->create();
    $profile = PppProfile::factory()->create([
        'name' => 'Paket Export',
        'profile_group_id' => $profileGroup->id,
    ]);
    $pppUser = PppUser::factory()->create([
        'customer_id' => '000000040001',
        'customer_name' => 'Pelanggan Export',
        'ppp_profile_id' => $profile->id,
        'profile_group_id' => $profileGroup->id,
    ]);

    Invoice::factory()->create([
        'ppp_user_id' => $pppUser->id,
        'ppp_profile_id' => $profile->id,
        'customer_id' => $pppUser->customer_id,
        'customer_name' => $pppUser->customer_name,
        'paket_langganan' => $profile->name,
        'status' => 'paid',
        'payment_method' => 'cash',
        'created_at' => now(),
    ]);

    $createResponse = $this->actingAs($user)
        ->post(route('super-admin.tools.backup.create'));

    $createResponse->assertSuccessful()
        ->assertJsonPath('status', 'Backup berhasil dibuat.');

    $filename = $createResponse->json('file');

    expect($filename)->toBeString();

    Storage::disk('local')->assertExists('backups/'.$filename);

    $this->actingAs($user)
        ->get(route('super-admin.tools.backup.download', ['file' => $filename]))
        ->assertSuccessful();

    $this->actingAs($user)
        ->delete(route('super-admin.tools.backup.delete'), ['file' => $filename])
        ->assertSuccessful()
        ->assertJsonPath('status', 'File backup dihapus.');

    Storage::disk('local')->assertMissing('backups/'.$filename);

    $exportResponse = $this->actingAs($user)
        ->get(route('super-admin.tools.export-transactions.download', [
            'status' => 'paid',
        ]));

    $exportResponse->assertSuccessful();

    $content = (string) $exportResponse->getContent();

    expect($content)->toContain('invoice_number')
        ->toContain('Pelanggan Export')
        ->toContain('paid');

    expect(ActivityLog::query()->where('action', 'backup_created')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'backup_deleted')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'transactions_exported')->exists())->toBeTrue();
});

it('restores a backup snapshot file', function () {
    $user = User::factory()->superAdmin()->create();

    $snapshot = [
        'created_at' => now()->toIso8601String(),
        'driver' => 'sqlite',
        'tables' => [
            'activity_logs' => [
                [
                    'id' => 999,
                    'user_id' => $user->id,
                    'action' => 'restored',
                    'subject_type' => 'Database',
                    'subject_id' => 0,
                    'subject_label' => 'manual-backup',
                    'properties' => json_encode(['source' => 'restore']),
                    'ip_address' => '127.0.0.1',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'backup.json.gz',
        gzencode(json_encode($snapshot) ?: '{}') ?: ''
    );

    $this->actingAs($user)
        ->post(route('super-admin.tools.backup.restore'), ['file' => $file])
        ->assertSuccessful()
        ->assertJsonPath('status', 'Database berhasil direstore.');

    expect(ActivityLog::query()->where('action', 'restored')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('action', 'backup_restored')->exists())->toBeTrue();
});
