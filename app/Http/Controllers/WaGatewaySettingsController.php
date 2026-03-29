<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWaGatewayTestMessageRequest;
use App\Http\Requests\StoreWaMultiSessionDeviceRequest;
use App\Http\Requests\UpdateWaGatewaySettingsRequest;
use App\Models\WaGatewaySetting;
use App\Models\WaMultiSessionDevice;
use App\Services\WaGatewayService;
use App\Services\WaMultiSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WaGatewaySettingsController extends Controller
{
    public function index(WaMultiSessionManager $manager): View
    {
        return view('super-admin.settings.wa-gateway', [
            'settings' => WaGatewaySetting::instance(),
            'devices' => WaMultiSessionDevice::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->orderBy('device_name')
                ->get(),
            'serviceStatus' => $manager->status(),
        ]);
    }

    public function update(UpdateWaGatewaySettingsRequest $request): RedirectResponse
    {
        $settings = WaGatewaySetting::instance();
        $payload = $request->validated();
        $payload['is_enabled'] = $request->boolean('is_enabled');

        $settings->fill($payload)->save();

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with('success', 'Pengaturan WhatsApp Gateway berhasil diperbarui.');
    }

    public function serviceControl(string $action, WaMultiSessionManager $manager): RedirectResponse
    {
        if (! in_array($action, ['status', 'restart', 'ensure-running'], true)) {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Aksi service WhatsApp tidak valid.');
        }

        $result = match ($action) {
            'status' => [
                'success' => true,
                'message' => 'Status service WhatsApp berhasil diambil.',
                'data' => $manager->status(),
            ],
            'restart' => $manager->restart(),
            'ensure-running' => $manager->ensureRunning(),
        };

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with(($result['success'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Permintaan selesai.'));
    }

    public function storeDevice(StoreWaMultiSessionDeviceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sessionId = trim((string) ($validated['session_id'] ?? ''));

        if ($sessionId === '') {
            $sessionId = 'selfhosted-'.Str::slug($validated['device_name'], '-');
        }

        if (WaMultiSessionDevice::query()->where('session_id', $sessionId)->exists()) {
            $sessionId .= '-'.Str::lower(Str::random(4));
        }

        $waNumber = trim((string) ($validated['wa_number'] ?? ''));

        if ($waNumber !== '' && str_starts_with($waNumber, '0')) {
            $waNumber = '62'.substr($waNumber, 1);
        }

        WaMultiSessionDevice::query()->create([
            'device_name' => $validated['device_name'],
            'session_id' => $sessionId,
            'wa_number' => $waNumber !== '' ? $waNumber : null,
            'is_default' => WaMultiSessionDevice::query()->count() === 0,
            'is_active' => $request->boolean('is_active', true),
            'meta' => [],
        ]);

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with('success', 'Device WhatsApp berhasil ditambahkan.');
    }

    public function setDefaultDevice(WaMultiSessionDevice $device): RedirectResponse
    {
        WaMultiSessionDevice::query()->update(['is_default' => false]);
        $device->update(['is_default' => true]);

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with('success', 'Device default berhasil diperbarui.');
    }

    public function testConnection(): RedirectResponse
    {
        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Gateway WhatsApp belum dikonfigurasi lengkap.');
        }

        $result = $service->testConnection();

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with(($result['status'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Tes koneksi selesai.'));
    }

    public function sessionControl(WaMultiSessionDevice $device, string $action): RedirectResponse
    {
        if (! in_array($action, ['status', 'restart'], true)) {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Aksi sesi WhatsApp tidak valid.');
        }

        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Gateway WhatsApp belum dikonfigurasi lengkap.');
        }

        $service->setSessionId($device->session_id);

        $result = match ($action) {
            'status' => $service->sessionStatus(),
            'restart' => $service->restartSession(),
        };

        $rawStatusValue = data_get($result, 'data.status');
        $statusValue = is_string($rawStatusValue) ? trim($rawStatusValue) : '';
        $device->update([
            'last_status' => $statusValue !== '' ? $statusValue : $device->last_status,
            'last_seen_at' => ($result['status'] ?? false) ? now() : $device->last_seen_at,
        ]);

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with(($result['status'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Permintaan sesi selesai.'));
    }

    public function sendTestMessage(SendWaGatewayTestMessageRequest $request): RedirectResponse
    {
        $settings = WaGatewaySetting::instance();
        $service = WaGatewayService::fromSettings($settings);

        if (! $service?->isConfigured()) {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Gateway WhatsApp belum dikonfigurasi lengkap.');
        }

        $validated = $request->validated();
        $device = null;

        if (($validated['device_id'] ?? null) !== null) {
            $device = WaMultiSessionDevice::query()->find((int) $validated['device_id']);
        }

        if ($device) {
            $service->setSessionId($device->session_id);
        }

        $recipientPhone = trim((string) ($validated['recipient_phone'] ?? ''));

        if ($recipientPhone === '') {
            $recipientPhone = trim((string) ($settings->default_test_recipient ?: $settings->business_phone));
        }

        if ($recipientPhone === '') {
            return redirect()
                ->route('super-admin.settings.wa-gateway.index')
                ->with('error', 'Nomor tujuan test masih kosong.');
        }

        $message = trim((string) ($validated['message'] ?? ''));

        if ($message === '') {
            $businessName = trim((string) ($settings->business_name ?: 'Rafen Self-Hosted'));
            $deviceLabel = $device?->device_name ?: 'device default';
            $message = "✅ Test WhatsApp Gateway berhasil.\nTenant: {$businessName}\nDevice: {$deviceLabel}\nWaktu: ".now()->format('d/m/Y H:i:s');
        }

        $sent = $service->sendMessage($recipientPhone, $message);

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with($sent ? 'success' : 'error', $sent
                ? 'Pesan test berhasil dikirim.'
                : 'Pesan test gagal dikirim. Periksa sesi device dan koneksi gateway.');
    }

    public function destroyDevice(WaMultiSessionDevice $device): RedirectResponse
    {
        $wasDefault = $device->is_default;
        $device->delete();

        if ($wasDefault) {
            $replacement = WaMultiSessionDevice::query()->orderBy('id')->first();

            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with('success', 'Device WhatsApp berhasil dihapus.');
    }
}
