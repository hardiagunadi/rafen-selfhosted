<?php

namespace App\Models;

use App\Services\GenieAcsClient;
use Database\Factories\CpeDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CpeDevice extends Model
{
    /** @use HasFactory<CpeDeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'radius_account_id',
        'olt_onu_optic_id',
        'genieacs_device_id',
        'param_profile',
        'serial_number',
        'manufacturer',
        'model',
        'firmware_version',
        'status',
        'last_seen_at',
        'cached_params',
        'mac_address',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'cached_params' => 'array',
        ];
    }

    public function radiusAccount(): BelongsTo
    {
        return $this->belongsTo(RadiusAccount::class);
    }

    public function oltOnuOptic(): BelongsTo
    {
        return $this->belongsTo(OltOnuOptic::class);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    public function updateFromGenieacs(array $device): void
    {
        $client = GenieAcsClient::fromConfig();
        $profile = $client->detectParamProfile($device);

        $this->genieacs_device_id = (string) ($device['_id'] ?? $this->genieacs_device_id);
        $this->param_profile = $profile;
        $this->serial_number = $client->getParamValue($device, 'serial_number') ?? $this->serial_number;
        $this->manufacturer = $client->getParamValue($device, 'manufacturer') ?? $this->manufacturer;
        $this->model = $client->getParamValue($device, 'model') ?? $this->model;
        $this->firmware_version = $client->getParamValue($device, 'firmware_version') ?? $this->firmware_version;

        $lastInform = data_get($device, '_lastInform');

        if (is_string($lastInform) && trim($lastInform) !== '') {
            try {
                $lastSeenAt = Carbon::parse($lastInform);
                $thresholdMinutes = (int) config('genieacs.online_threshold_minutes', 70);

                $this->last_seen_at = $lastSeenAt;
                $this->status = $lastSeenAt->diffInMinutes(now()) <= $thresholdMinutes ? 'online' : 'offline';
            } catch (\Throwable) {
                $this->status = $this->status ?? 'unknown';
            }
        }

        $wifiNetworks = $client->extractWifiNetworks($device);
        $wanConnections = $client->extractWanConnections($device);
        $macAddress = null;

        foreach ($wanConnections as $wanConnection) {
            $wanMac = $wanConnection['mac_address'] ?? null;

            if (is_string($wanMac) && $wanMac !== '') {
                $macAddress = $wanMac;
                break;
            }
        }

        $this->mac_address = $macAddress ?? $this->mac_address;
        $this->cached_params = [
            'profile' => $profile,
            'wifi_networks' => $wifiNetworks,
            'wan_connections' => $wanConnections,
            'pppoe_username' => $client->getParamValue($device, 'pppoe_username'),
            'uptime' => $client->getParamValue($device, 'uptime'),
        ];
    }
}
