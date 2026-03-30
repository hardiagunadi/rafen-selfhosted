<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpireVouchers extends Command
{
    protected $signature = 'vouchers:expire';

    protected $description = 'Delete expired vouchers and remove related RADIUS rows when available.';

    public function handle(): int
    {
        $vouchers = Voucher::query()
            ->whereIn('status', ['unused', 'used'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->get(['id', 'username']);

        if ($vouchers->isEmpty()) {
            $this->info('No expired vouchers found.');

            return self::SUCCESS;
        }

        $usernames = $vouchers->pluck('username')->filter()->values()->all();
        $ids = $vouchers->pluck('id')->all();

        if ($usernames !== []) {
            if (Schema::hasTable('radcheck')) {
                DB::table('radcheck')->whereIn('username', $usernames)->delete();
            }

            if (Schema::hasTable('radreply')) {
                DB::table('radreply')->whereIn('username', $usernames)->delete();
            }
        }

        Voucher::query()->whereIn('id', $ids)->delete();

        $this->info("Deleted {$vouchers->count()} expired voucher(s) and removed related RADIUS rows.");

        return self::SUCCESS;
    }
}
