<?php

namespace App\Console\Commands;

use App\Services\VoucherUsageTracker;
use Illuminate\Console\Command;

class MarkVouchersUsed extends Command
{
    protected $signature = 'vouchers:mark-used';

    protected $description = 'Deteksi voucher yang sudah digunakan berdasarkan sesi aktif di radacct.';

    public function handle(VoucherUsageTracker $tracker): int
    {
        $count = $tracker->markUsedFromRadacct();

        $this->info("Marked {$count} voucher(s) as used.");

        return self::SUCCESS;
    }
}
