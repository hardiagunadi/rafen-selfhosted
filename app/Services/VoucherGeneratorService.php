<?php

namespace App\Services;

use App\Models\HotspotProfile;
use App\Models\Voucher;
use Illuminate\Support\Collection;

class VoucherGeneratorService
{
    private const CODE_LENGTH = 8;

    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * @return Collection<int, Voucher>
     */
    public function generateBatch(HotspotProfile $profile, int $count, string $batchName): Collection
    {
        $vouchers = collect();
        $attempts = 0;
        $maxAttempts = $count * 10;

        while ($vouchers->count() < $count && $attempts < $maxAttempts) {
            $code = $this->generateCode();
            $attempts++;

            if (Voucher::query()->where('code', $code)->exists()) {
                continue;
            }

            $vouchers->push(Voucher::query()->create([
                'hotspot_profile_id' => $profile->id,
                'profile_group_id' => $profile->profile_group_id,
                'batch_name' => $batchName,
                'code' => $code,
                'status' => 'unused',
                'username' => $code,
                'password' => $code,
            ]));
        }

        return $vouchers;
    }

    private function generateCode(): string
    {
        $code = '';
        $charsetLength = strlen(self::CHARSET);

        for ($index = 0; $index < self::CODE_LENGTH; $index++) {
            $code .= self::CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $code;
    }
}
