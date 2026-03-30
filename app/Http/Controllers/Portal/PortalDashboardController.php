<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalChangePasswordRequest;
use App\Http\Requests\PortalStoreTicketRequest;
use App\Http\Requests\PortalUpdateWifiRequest;
use App\Models\ActivityLog;
use App\Models\CpeDevice;
use App\Models\Invoice;
use App\Models\PppUser;
use App\Models\RadiusAccount;
use App\Models\WaConversation;
use App\Models\WaTicket;
use App\Services\GenieAcsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $pppUser = $this->getPppUser($request);
        $pppUser->load('profile');
        $linkedCpeDevice = $this->resolveLinkedCpeDevice($pppUser);

        $latestInvoice = Invoice::query()
            ->where('ppp_user_id', $pppUser->id)
            ->orderByRaw("CASE WHEN status = 'unpaid' THEN 0 ELSE 1 END")
            ->orderByDesc('due_date')
            ->first();

        if ($latestInvoice instanceof Invoice && empty($latestInvoice->payment_token)) {
            $latestInvoice->update(['payment_token' => Invoice::generatePaymentToken()]);
            $latestInvoice->refresh();
        }

        return view('portal.dashboard', [
            'pppUser' => $pppUser,
            'latestInvoice' => $latestInvoice,
            'linkedCpeDevice' => $linkedCpeDevice,
        ]);
    }

    public function invoices(Request $request): View
    {
        $pppUser = $this->getPppUser($request);

        $invoices = Invoice::query()
            ->where('ppp_user_id', $pppUser->id)
            ->orderByDesc('due_date')
            ->paginate(15);

        foreach ($invoices as $invoice) {
            if (empty($invoice->payment_token)) {
                $invoice->update(['payment_token' => Invoice::generatePaymentToken()]);
            }
        }

        return view('portal.invoices', [
            'pppUser' => $pppUser,
            'invoices' => $invoices,
        ]);
    }

    public function account(Request $request): View
    {
        $pppUser = $this->getPppUser($request);
        $pppUser->load('profile');

        return view('portal.account', [
            'pppUser' => $pppUser,
        ]);
    }

    public function changePassword(PortalChangePasswordRequest $request): JsonResponse
    {
        $pppUser = $this->getPppUser($request);
        $validated = $request->validated();
        $storedPassword = (string) $pppUser->password_clientarea;

        $valid = false;

        try {
            $valid = Hash::check($validated['current_password'], $storedPassword);
        } catch (\Throwable) {
        }

        if (! $valid) {
            $valid = hash_equals($storedPassword, $validated['current_password']);
        }

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $pppUser->update([
            'password_clientarea' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password portal berhasil diubah.',
        ]);
    }

    public function getTraffic(Request $request): JsonResponse
    {
        $pppUser = $this->getPppUser($request);

        $radiusAccount = RadiusAccount::query()
            ->where('service', 'pppoe')
            ->where('username', $pppUser->username)
            ->orderByDesc('is_active')
            ->latest('updated_at')
            ->first();

        if (! $radiusAccount instanceof RadiusAccount || ! $radiusAccount->is_active) {
            return response()->json([
                'is_active' => false,
                'username' => $pppUser->username,
            ]);
        }

        return response()->json([
            'is_active' => true,
            'username' => $radiusAccount->username,
            'bytes_in' => (int) $radiusAccount->bytes_in,
            'bytes_out' => (int) $radiusAccount->bytes_out,
            'sampled_at' => optional($radiusAccount->updated_at)->getTimestampMs(),
            'uptime' => $radiusAccount->uptime,
            'ipv4_address' => $radiusAccount->ipv4_address,
            'server_name' => $radiusAccount->server_name,
        ]);
    }

    public function updateWifi(PortalUpdateWifiRequest $request): JsonResponse
    {
        $pppUser = $this->getPppUser($request);
        $cpeDevice = $this->resolveLinkedCpeDevice($pppUser);

        if (! $cpeDevice instanceof CpeDevice || blank($cpeDevice->genieacs_device_id)) {
            return response()->json([
                'success' => false,
                'no_device' => true,
                'message' => 'Perangkat modem Anda belum terhubung ke sistem. Silakan buat tiket bantuan.',
            ], 422);
        }

        $client = GenieAcsClient::fromConfig();

        if (! $client->isConfigured()) {
            return response()->json([
                'success' => false,
                'no_genieacs' => true,
                'message' => 'Fitur ganti WiFi belum tersedia di server ini. Silakan buat tiket bantuan.',
            ], 422);
        }

        $validated = $request->validated();
        $result = $client->setWifi(
            $cpeDevice->genieacs_device_id,
            (string) $validated['ssid'],
            $validated['password'] ?? null,
            $cpeDevice->param_profile ?? 'igd',
        );

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Konfigurasi WiFi gagal dikirim ke modem.'),
            ], 422);
        }

        $cached = $cpeDevice->cached_params ?? [];
        $wifiNetworks = $cached['wifi_networks'] ?? [];

        if ($wifiNetworks !== [] && isset($wifiNetworks[0]) && is_array($wifiNetworks[0])) {
            $wifiNetworks[0]['ssid'] = $validated['ssid'];
        } else {
            $wifiNetworks = [[
                'index' => 1,
                'ssid' => $validated['ssid'],
                'enabled' => true,
            ]];
        }

        $cached['wifi_networks'] = $wifiNetworks;
        $cpeDevice->cached_params = $cached;
        $cpeDevice->save();

        $this->recordPortalActivity($request, 'portal_wifi_updated', 'CpeDevice', $cpeDevice->id, $cpeDevice->serial_number ?? $pppUser->username, [
            'ppp_user_id' => $pppUser->id,
            'ssid' => $validated['ssid'],
        ]);

        return response()->json([
            'success' => true,
            'message' => ($result['queued'] ?? false)
                ? 'Pengaturan WiFi dikirim. Akan diterapkan saat modem online.'
                : 'Pengaturan WiFi berhasil diperbarui.',
        ]);
    }

    public function storeTicket(PortalStoreTicketRequest $request): JsonResponse
    {
        $pppUser = $this->getPppUser($request);
        $validated = $request->validated();
        $contactPhone = $this->resolvePortalContactPhone($pppUser);

        $conversation = WaConversation::query()->firstOrCreate(
            ['contact_phone' => $contactPhone],
            [
                'contact_name' => $pppUser->customer_name ?: $pppUser->username,
                'status' => 'open',
                'last_message_at' => now(),
            ],
        );

        $conversation->messages()->create([
            'direction' => 'inbound',
            'message' => $validated['message'],
            'sender_name' => $pppUser->customer_name ?: $pppUser->username,
            'created_at' => now(),
        ]);

        $conversation->updateFromIncoming($validated['message']);

        $ticket = WaTicket::query()->create([
            'customer_name' => $pppUser->customer_name,
            'customer_phone' => $pppUser->nomor_hp,
            'customer_type' => 'ppp',
            'customer_id' => $pppUser->id,
            'title' => $validated['subject'],
            'description' => $validated['message'],
            'type' => $validated['type'],
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $ticket->notes()->create([
            'type' => 'created',
            'meta' => 'Tiket dibuat dari portal pelanggan.',
            'note' => $validated['message'],
        ]);

        $this->recordPortalActivity($request, 'portal_ticket_created', 'WaTicket', $ticket->id, $ticket->title, [
            'ppp_user_id' => $pppUser->id,
            'type' => $ticket->type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiket bantuan berhasil dikirim.',
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'customer_name' => $ticket->customer_name,
                'public_url' => $ticket->publicUrl(),
            ],
        ]);
    }

    private function getPppUser(Request $request): PppUser
    {
        return $request->attributes->get('portal_ppp_user');
    }

    private function resolveLinkedCpeDevice(PppUser $pppUser): ?CpeDevice
    {
        return CpeDevice::query()
            ->with('radiusAccount')
            ->whereHas('radiusAccount', function ($query) use ($pppUser): void {
                $query->where('service', 'pppoe')
                    ->where('username', $pppUser->username);
            })
            ->latest('updated_at')
            ->first();
    }

    private function resolvePortalContactPhone(PppUser $pppUser): string
    {
        $contactPhone = trim((string) $pppUser->nomor_hp);

        if ($contactPhone !== '') {
            return $contactPhone;
        }

        return 'portal-'.$pppUser->customer_id;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function recordPortalActivity(Request $request, string $action, string $subjectType, int $subjectId, string $subjectLabel, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
