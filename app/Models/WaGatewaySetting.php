<?php

namespace App\Models;

use Database\Factories\WaGatewaySettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaGatewaySetting extends Model
{
    /** @use HasFactory<WaGatewaySettingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_name',
        'business_phone',
        'default_test_recipient',
        'gateway_url',
        'auth_token',
        'master_key',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => true,
            ],
        );
    }

    public function resolvedGatewayUrl(): string
    {
        $configuredUrl = trim((string) $this->gateway_url);

        if ($configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        return 'http://'.config('wa.multi_session.host', '127.0.0.1').':'.(int) config('wa.multi_session.port', 3100);
    }

    public function resolvedAuthToken(): string
    {
        $configuredToken = trim((string) $this->auth_token);

        if ($configuredToken !== '') {
            return $configuredToken;
        }

        return trim((string) config('wa.multi_session.auth_token', ''));
    }

    public function resolvedMasterKey(): string
    {
        $configuredKey = trim((string) $this->master_key);

        if ($configuredKey !== '') {
            return $configuredKey;
        }

        return trim((string) config('wa.multi_session.master_key', ''));
    }
}
