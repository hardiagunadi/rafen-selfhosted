<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkCpeDeviceRequest;
use App\Http\Requests\RebootCpeDeviceRequest;
use App\Http\Requests\UpdateCpePppoeRequest;
use App\Http\Requests\UpdateCpeWifiRequest;
use App\Models\CpeDevice;
use App\Models\RadiusAccount;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Services\GenieAcsClient;
use App\Services\RadiusReplySynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class CpeController extends Controller
{
    public function index(): View
    {
        $client = GenieAcsClient::fromConfig();
        $nbiStatus = $client->getStatus();
        $remoteDevices = [];

        if ($nbiStatus['online']) {
            try {
                $remoteDevices = $client->listDevices(200);
            } catch (Throwable $throwable) {
                $nbiStatus['message'] = 'NBI GenieACS aktif, tetapi inventory device gagal diambil: '.$throwable->getMessage();
            }
        }

        $linkedDevices = CpeDevice::query()
            ->with(['radiusAccount', 'oltOnuOptic'])
            ->latest('last_seen_at')
            ->get();

        $linkedIds = $linkedDevices
            ->pluck('genieacs_device_id')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->all();

        $unlinkedDevices = collect($remoteDevices)
            ->filter(fn (mixed $device): bool => is_array($device) && ! in_array((string) data_get($device, '_id', ''), $linkedIds, true))
            ->map(fn (array $device): array => [
                'device_id' => (string) data_get($device, '_id', ''),
                'serial_number' => $client->getParamValue($device, 'serial_number'),
                'manufacturer' => $client->getParamValue($device, 'manufacturer'),
                'model' => $client->getParamValue($device, 'model'),
                'pppoe_username' => $client->getParamValue($device, 'pppoe_username'),
                'last_inform' => data_get($device, '_lastInform'),
            ])
            ->values();

        return view('super-admin.settings.cpe', [
            'nbiStatus' => $nbiStatus,
            'linkedDevices' => $linkedDevices,
            'unlinkedDevices' => $unlinkedDevices,
            'availableRadiusAccounts' => RadiusAccount::query()
                ->where('is_active', true)
                ->where('service', 'pppoe')
                ->whereDoesntHave('cpeDevice')
                ->orderBy('username')
                ->get(),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $client = GenieAcsClient::fromConfig();
        $linkedCount = 0;

        foreach (RadiusAccount::query()->where('is_active', true)->where('service', 'pppoe')->get() as $radiusAccount) {
            try {
                $device = $client->findDeviceByUsername($radiusAccount->username);

                if (! is_array($device) || ! isset($device['_id'])) {
                    continue;
                }

                $cpeDevice = CpeDevice::query()->firstOrNew([
                    'genieacs_device_id' => (string) $device['_id'],
                ]);

                $cpeDevice->radius_account_id = $radiusAccount->id;
                $cpeDevice->updateFromGenieacs($device);
                $cpeDevice->save();
                $linkedCount++;
            } catch (Throwable) {
            }
        }

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with('success', 'Sinkronisasi CPE selesai. '.$linkedCount.' perangkat berhasil dipetakan ke akun Radius.');
    }

    public function link(LinkCpeDeviceRequest $request): RedirectResponse
    {
        $radiusAccount = RadiusAccount::query()->findOrFail((int) $request->validated('radius_account_id'));
        $deviceId = (string) $request->validated('device_id');
        $device = GenieAcsClient::fromConfig()->getDeviceInfo($deviceId);

        if ($device === []) {
            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('error', 'Device GenieACS tidak ditemukan.');
        }

        $cpeDevice = CpeDevice::query()->firstOrNew([
            'genieacs_device_id' => $deviceId,
        ]);

        $cpeDevice->radius_account_id = $radiusAccount->id;
        $cpeDevice->updateFromGenieacs($device);
        $cpeDevice->save();

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with('success', 'Device CPE berhasil dihubungkan ke akun Radius.');
    }

    public function reboot(RebootCpeDeviceRequest $request, CpeDevice $cpeDevice): RedirectResponse
    {
        $result = GenieAcsClient::fromConfig()->rebootDevice($cpeDevice->genieacs_device_id);

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Perintah reboot selesai diproses.'));
    }

    public function refresh(CpeDevice $cpeDevice): RedirectResponse
    {
        $client = GenieAcsClient::fromConfig();
        $profile = $cpeDevice->param_profile ?? 'igd';
        $params = [
            config("genieacs.params.{$profile}.manufacturer"),
            config("genieacs.params.{$profile}.model"),
            config("genieacs.params.{$profile}.serial_number"),
            config("genieacs.params.{$profile}.firmware_version"),
            config("genieacs.params.{$profile}.pppoe_username"),
            config("genieacs.params.{$profile}.wifi_ssid"),
            config("genieacs.params.{$profile}.uptime"),
        ];

        try {
            $result = $client->refreshParameters(
                $cpeDevice->genieacs_device_id,
                array_values(array_filter($params, fn (mixed $value): bool => is_string($value) && $value !== '')),
            );

            if (! ($result['success'] ?? false)) {
                return redirect()
                    ->route('super-admin.settings.cpe.index')
                    ->with('error', 'Refresh parameter CPE gagal: '.($result['message'] ?? 'Task GenieACS gagal dibuat.'));
            }

            $device = $client->getDeviceInfo($cpeDevice->genieacs_device_id);

            if ($device !== []) {
                $cpeDevice->updateFromGenieacs($device);
                $cpeDevice->save();
            }

            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('success', 'Parameter CPE berhasil diperbarui dari GenieACS.');
        } catch (Throwable $throwable) {
            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('error', 'Refresh parameter CPE gagal: '.$throwable->getMessage());
        }
    }

    public function updateWifi(UpdateCpeWifiRequest $request, CpeDevice $cpeDevice): RedirectResponse
    {
        $result = GenieAcsClient::fromConfig()->setWifi(
            $cpeDevice->genieacs_device_id,
            (string) $request->validated('ssid'),
            $request->validated('password'),
            $cpeDevice->param_profile ?? 'igd',
        );

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('error', (string) ($result['message'] ?? 'Konfigurasi WiFi gagal dikirim ke CPE.'));
        }

        $cached = $cpeDevice->cached_params ?? [];
        $wifiNetworks = $cached['wifi_networks'] ?? [];

        if ($wifiNetworks !== [] && isset($wifiNetworks[0]) && is_array($wifiNetworks[0])) {
            $wifiNetworks[0]['ssid'] = $request->validated('ssid');
        } else {
            $wifiNetworks = [[
                'index' => 1,
                'ssid' => $request->validated('ssid'),
                'enabled' => true,
            ]];
        }

        $cached['wifi_networks'] = $wifiNetworks;
        $cpeDevice->cached_params = $cached;
        $cpeDevice->save();

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', ($result['queued'] ?? false)
                ? 'Konfigurasi WiFi dikirim. Akan diterapkan saat perangkat online.'
                : 'Konfigurasi WiFi berhasil dikirim ke CPE.');
    }

    public function updatePppoe(UpdateCpePppoeRequest $request, CpeDevice $cpeDevice, RadiusReplySynchronizer $radiusReplySynchronizer): RedirectResponse
    {
        $radiusAccount = $cpeDevice->radiusAccount;

        if (! $radiusAccount) {
            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('error', 'Device CPE ini belum terhubung ke akun Radius.');
        }

        $result = GenieAcsClient::fromConfig()->setPppoeCredentials(
            $cpeDevice->genieacs_device_id,
            (string) $request->validated('username'),
            (string) $request->validated('password'),
            $cpeDevice->param_profile ?? 'igd',
        );

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('super-admin.settings.cpe.index')
                ->with('error', (string) ($result['message'] ?? 'Kredensial PPPoE gagal dikirim ke CPE.'));
        }

        $previousUsername = $radiusAccount->username;

        $radiusAccount->update([
            'username' => $request->validated('username'),
            'password' => $request->validated('password'),
        ]);

        if ($previousUsername !== $radiusAccount->username) {
            RadiusCheck::query()->where('username', $previousUsername)->delete();
            RadiusReply::query()->where('username', $previousUsername)->delete();
        }

        $freshRadiusAccount = $radiusAccount->fresh();

        if ($freshRadiusAccount) {
            $radiusReplySynchronizer->syncSingleAccount($freshRadiusAccount);
        }

        $cached = $cpeDevice->cached_params ?? [];
        $cached['pppoe_username'] = $request->validated('username');
        $cpeDevice->cached_params = $cached;
        $cpeDevice->save();

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', ($result['queued'] ?? false)
                ? 'Kredensial PPPoE dikirim. Radius lokal juga sudah disinkronkan.'
                : 'Kredensial PPPoE berhasil dikirim dan Radius lokal diperbarui.');
    }

    public function destroy(CpeDevice $cpeDevice): RedirectResponse
    {
        $cpeDevice->delete();

        return redirect()
            ->route('super-admin.settings.cpe.index')
            ->with('success', 'Link device CPE berhasil dihapus.');
    }
}
