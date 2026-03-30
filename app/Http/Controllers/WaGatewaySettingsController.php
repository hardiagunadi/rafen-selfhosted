<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWaGatewayTestMessageRequest;
use App\Http\Requests\StoreWaMultiSessionDeviceRequest;
use App\Http\Requests\UpdateWaGatewaySettingsRequest;
use App\Models\WaGatewaySetting;
use App\Models\WaMultiSessionDevice;
use App\Services\WaGatewayService;
use App\Services\WaMultiSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function serviceControl(Request $request, string $action, WaMultiSessionManager $manager): JsonResponse|RedirectResponse
    {
        if (! in_array($action, ['status', 'restart', 'ensure-running'], true)) {
            return $this->respondActionResult(
                $request,
                false,
                'Aksi service WhatsApp tidak valid.',
                null,
                422
            );
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

        return $this->respondActionResult(
            $request,
            (bool) ($result['success'] ?? false),
            (string) ($result['message'] ?? 'Permintaan selesai.'),
            $result['data'] ?? null,
            (bool) ($result['success'] ?? false) ? 200 : 422
        );
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

    public function testConnection(Request $request): JsonResponse|RedirectResponse
    {
        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return $this->respondActionResult(
                $request,
                false,
                'Gateway WhatsApp belum dikonfigurasi lengkap.',
                null,
                422
            );
        }

        $result = $service->testConnection();

        return $this->respondActionResult(
            $request,
            (bool) ($result['status'] ?? false),
            (string) ($result['message'] ?? 'Tes koneksi selesai.'),
            $result['data'] ?? null,
            (bool) ($result['status'] ?? false) ? 200 : 422
        );
    }

    public function sessionControl(Request $request, WaMultiSessionDevice $device, string $action): JsonResponse|RedirectResponse
    {
        if (! in_array($action, ['status', 'restart'], true)) {
            return $this->respondActionResult(
                $request,
                false,
                'Aksi sesi WhatsApp tidak valid.',
                null,
                422
            );
        }

        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return $this->respondActionResult(
                $request,
                false,
                'Gateway WhatsApp belum dikonfigurasi lengkap.',
                null,
                422
            );
        }

        $service->setSessionId($device->session_id);

        $result = match ($action) {
            'status' => $service->sessionStatus(),
            'restart' => $service->restartSession(),
        };

        $sessionData = is_array($result['data'] ?? null)
            ? $this->normalizeSessionData($result['data'])
            : ($result['data'] ?? null);

        $rawStatusValue = is_array($sessionData) ? ($sessionData['status'] ?? null) : null;
        $statusValue = is_string($rawStatusValue) ? trim($rawStatusValue) : '';
        $device->update([
            'last_status' => $statusValue !== '' ? $statusValue : $device->last_status,
            'last_seen_at' => ($result['status'] ?? false) ? now() : $device->last_seen_at,
        ]);

        return $this->respondActionResult(
            $request,
            (bool) ($result['status'] ?? false),
            (string) ($result['message'] ?? 'Permintaan sesi selesai.'),
            $sessionData,
            (bool) ($result['status'] ?? false) ? 200 : 422
        );
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

    private function respondActionResult(
        Request $request,
        bool $success,
        string $message,
        mixed $data = null,
        int $statusCode = 200
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $data,
            ], $statusCode);
        }

        return redirect()
            ->route('super-admin.settings.wa-gateway.index')
            ->with($success ? 'success' : 'error', $message);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSessionData(array $data): array
    {
        $payload = isset($data['data']) && is_array($data['data'])
            ? $data['data']
            : $data;

        $status = $payload['status'] ?? $payload['state'] ?? (is_string($data['status'] ?? null) ? $data['status'] : null);
        $qr = $payload['qr'] ?? $payload['qrCode'] ?? $payload['qr_code'] ?? $data['qr'] ?? $data['qrCode'] ?? $data['qr_code'] ?? null;
        $updatedAt = $payload['updated_at'] ?? $payload['updatedAt'] ?? $data['updated_at'] ?? $data['updatedAt'] ?? null;

        if ($status !== null) {
            $payload['status'] = $status;
        }

        if ($qr !== null) {
            $payload['qr'] = $qr;
        }

        if ($updatedAt !== null) {
            $payload['updated_at'] = $updatedAt;
        }

        return $payload;
    }
}
