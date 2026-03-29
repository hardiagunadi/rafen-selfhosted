<?php

namespace App\Services;

use App\Models\RadiusAccount;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;

class RadiusReplySynchronizer
{
    public function sync(): int
    {
        $accounts = RadiusAccount::query()->get();
        $activeUsernames = $accounts
            ->where('is_active', true)
            ->pluck('username')
            ->all();

        foreach ($accounts as $account) {
            if (! $account->is_active) {
                RadiusCheck::query()->where('username', $account->username)->delete();
                RadiusReply::query()->where('username', $account->username)->delete();

                continue;
            }

            $this->syncSingleAccount($account);
        }

        if ($activeUsernames !== []) {
            RadiusCheck::query()->whereNotIn('username', $activeUsernames)->delete();
            RadiusReply::query()->whereNotIn('username', $activeUsernames)->delete();
        } else {
            RadiusCheck::query()->delete();
            RadiusReply::query()->delete();
        }

        return count($activeUsernames);
    }

    public function syncSingleAccount(RadiusAccount $account): void
    {
        RadiusCheck::query()->updateOrCreate(
            [
                'username' => $account->username,
                'attribute' => 'Cleartext-Password',
            ],
            [
                'radius_account_id' => $account->id,
                'op' => ':=',
                'value' => $account->password,
            ],
        );

        $expectedReplyAttributes = collect();

        if ($account->service === 'pppoe' && $account->ipv4_address) {
            $this->upsertReply($account, 'Framed-IP-Address', $account->ipv4_address);
            $expectedReplyAttributes->push('Framed-IP-Address');
        }

        if ($account->rate_limit) {
            $this->upsertReply($account, 'Mikrotik-Rate-Limit', $account->rate_limit);
            $expectedReplyAttributes->push('Mikrotik-Rate-Limit');
        }

        if ($account->profile) {
            $attribute = $account->service === 'hotspot' ? 'Mikrotik-Group' : 'Framed-Pool';
            $this->upsertReply($account, $attribute, $account->profile);
            $expectedReplyAttributes->push($attribute);
        }

        RadiusReply::query()
            ->where('username', $account->username)
            ->when(
                $expectedReplyAttributes->isNotEmpty(),
                fn ($query) => $query->whereNotIn('attribute', $expectedReplyAttributes->all()),
            )
            ->when(
                $expectedReplyAttributes->isEmpty(),
                fn ($query) => $query,
            )
            ->delete();
    }

    private function upsertReply(RadiusAccount $account, string $attribute, string $value): void
    {
        RadiusReply::query()->updateOrCreate(
            [
                'username' => $account->username,
                'attribute' => $attribute,
            ],
            [
                'radius_account_id' => $account->id,
                'op' => ':=',
                'value' => $value,
            ],
        );
    }

    /**
     * @return array{checks: int, replies: int}
     */
    public function stats(): array
    {
        return [
            'checks' => RadiusCheck::query()->count(),
            'replies' => RadiusReply::query()->count(),
        ];
    }
}
