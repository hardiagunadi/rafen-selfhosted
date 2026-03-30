<?php

namespace App\Services;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VoucherUsageTracker
{
    public function markUsedFromRadacct(): int
    {
        if (! Schema::hasTable('radacct')) {
            return 0;
        }

        $unusedVouchers = Voucher::query()
            ->where('status', 'unused')
            ->whereNotNull('username')
            ->with('hotspotProfile')
            ->get();

        if ($unusedVouchers->isEmpty()) {
            return 0;
        }

        $usernames = $unusedVouchers->pluck('username')->filter()->unique()->values()->all();

        if ($usernames === []) {
            return 0;
        }

        $startTimes = DB::table('radacct')
            ->select('username', DB::raw('MIN(acctstarttime) as first_start'))
            ->whereIn('username', $usernames)
            ->whereNotNull('acctstarttime')
            ->groupBy('username')
            ->get()
            ->keyBy('username');

        $updated = 0;
        $now = Carbon::now();

        foreach ($unusedVouchers as $voucher) {
            if (! isset($startTimes[$voucher->username])) {
                continue;
            }

            $firstStart = $startTimes[$voucher->username]->first_start ?? null;
            $usedAt = $firstStart ? Carbon::parse($firstStart) : $now;
            $expiredAt = $voucher->hotspotProfile?->computeExpiredAt($usedAt);

            $voucher->update([
                'status' => 'used',
                'used_at' => $usedAt,
                'expired_at' => $expiredAt,
            ]);

            $updated++;
        }

        return $updated;
    }
}
