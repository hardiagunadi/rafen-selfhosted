<?php

use App\Models\HotspotProfile;
use App\Models\Voucher;
use App\Services\VoucherUsageTracker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('license.self_hosted_enabled', true);
    config()->set('license.enforce', false);
});

it('marks unused voucher as used from radacct history even when the session already stopped', function () {
    Schema::create('radacct', function (Blueprint $table): void {
        $table->id();
        $table->string('username');
        $table->dateTime('acctstarttime')->nullable();
        $table->dateTime('acctstoptime')->nullable();
    });

    $profile = HotspotProfile::factory()->create([
        'masa_aktif_value' => 2,
        'masa_aktif_unit' => 'jam',
    ]);

    $voucher = Voucher::factory()->create([
        'hotspot_profile_id' => $profile->id,
        'profile_group_id' => $profile->profile_group_id,
        'code' => 'VOU-USED-HISTORY',
        'status' => 'unused',
        'username' => 'voucher-history',
        'password' => 'voucher-history',
    ]);

    DB::table('radacct')->insert([
        'username' => 'voucher-history',
        'acctstarttime' => now()->subHours(3),
        'acctstoptime' => now()->subHours(2),
    ]);

    $count = app(VoucherUsageTracker::class)->markUsedFromRadacct();
    $voucher->refresh();

    expect($count)->toBe(1)
        ->and($voucher->status)->toBe('used')
        ->and($voucher->used_at)->not->toBeNull()
        ->and($voucher->expired_at)->not->toBeNull()
        ->and($voucher->expired_at?->equalTo($voucher->used_at->copy()->addHours(2)))->toBeTrue();
});

it('skips mark used when radacct table is unavailable', function () {
    $voucher = Voucher::factory()->create([
        'status' => 'unused',
        'username' => 'voucher-no-radacct',
    ]);

    $count = app(VoucherUsageTracker::class)->markUsedFromRadacct();
    $voucher->refresh();

    expect($count)->toBe(0)
        ->and($voucher->status)->toBe('unused')
        ->and($voucher->used_at)->toBeNull()
        ->and($voucher->expired_at)->toBeNull();
});

it('deletes expired vouchers and clears radius rows', function () {
    Schema::create('radcheck', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('username');
        $table->string('attribute');
        $table->string('op', 2)->default(':=');
        $table->text('value')->nullable();
    });

    Schema::create('radreply', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('username');
        $table->string('attribute');
        $table->string('op', 2)->default(':=');
        $table->text('value')->nullable();
    });

    $voucher = Voucher::factory()->create([
        'code' => 'VOU-EXPIRED-DELETE',
        'status' => 'used',
        'username' => 'voucher-expired',
        'password' => 'voucher-expired',
        'used_at' => now()->subDays(2),
        'expired_at' => now()->subHour(),
    ]);

    DB::table('radcheck')->insert([
        'username' => 'voucher-expired',
        'attribute' => 'Cleartext-Password',
        'op' => ':=',
        'value' => 'voucher-expired',
    ]);

    DB::table('radreply')->insert([
        'username' => 'voucher-expired',
        'attribute' => 'Mikrotik-Group',
        'op' => ':=',
        'value' => 'Hotspot',
    ]);

    $this->artisan('vouchers:expire')
        ->assertSuccessful();

    $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
    $this->assertDatabaseMissing('radcheck', ['username' => 'voucher-expired']);
    $this->assertDatabaseMissing('radreply', ['username' => 'voucher-expired']);
});

it('deletes expired vouchers even when radius tables are unavailable', function () {
    $voucher = Voucher::factory()->create([
        'status' => 'used',
        'username' => 'voucher-expired-without-radius',
        'expired_at' => now()->subMinute(),
    ]);

    $this->artisan('vouchers:expire')
        ->assertSuccessful();

    $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
});
