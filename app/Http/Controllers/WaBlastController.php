<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWaBlastRequest;
use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\WaBlastLog;
use App\Models\WaGatewaySetting;
use App\Services\WaGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WaBlastController extends Controller
{
    public function index(): View
    {
        return view('super-admin.wa-blast', [
            'settings' => WaGatewaySetting::instance(),
            'pppProfiles' => PppProfile::query()->orderBy('name')->get(),
            'hotspotProfiles' => HotspotProfile::query()->orderBy('name')->get(),
            'recentLogs' => WaBlastLog::query()
                ->with('sentBy:id,name')
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $recipients = $this->buildRecipients($request);

        return response()->json([
            'count' => $recipients->count(),
            'recipients' => $recipients->map(fn (array $recipient): array => [
                'name' => $recipient['name'],
                'phone' => $recipient['phone'],
                'type' => $recipient['type'],
            ])->values(),
        ]);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->string('q'));
        $type = (string) $request->string('type', 'all');

        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'results' => [],
            ]);
        }

        $results = collect();

        if (in_array($type, ['ppp', 'all'], true)) {
            PppUser::query()
                ->whereNotNull('nomor_hp')
                ->where('nomor_hp', '!=', '')
                ->where(function ($query) use ($keyword): void {
                    $query->where('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_id', 'like', "%{$keyword}%")
                        ->orWhere('username', 'like', "%{$keyword}%")
                        ->orWhere('nomor_hp', 'like', "%{$keyword}%");
                })
                ->orderBy('customer_name')
                ->limit(12)
                ->get(['id', 'customer_name', 'customer_id', 'username', 'nomor_hp'])
                ->each(function (PppUser $user) use ($results): void {
                    $label = trim((string) ($user->customer_name ?: $user->username ?: $user->customer_id ?: 'Pelanggan PPP'));
                    $results->push([
                        'id' => 'ppp:'.$user->id,
                        'text' => sprintf('PPPoE · %s (%s)', $label, $user->nomor_hp),
                    ]);
                });
        }

        if (in_array($type, ['hotspot', 'all'], true)) {
            HotspotUser::query()
                ->whereNotNull('nomor_hp')
                ->where('nomor_hp', '!=', '')
                ->where(function ($query) use ($keyword): void {
                    $query->where('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_id', 'like', "%{$keyword}%")
                        ->orWhere('username', 'like', "%{$keyword}%")
                        ->orWhere('nomor_hp', 'like', "%{$keyword}%");
                })
                ->orderBy('customer_name')
                ->limit(12)
                ->get(['id', 'customer_name', 'customer_id', 'username', 'nomor_hp'])
                ->each(function (HotspotUser $user) use ($results): void {
                    $label = trim((string) ($user->customer_name ?: $user->username ?: $user->customer_id ?: 'Pelanggan Hotspot'));
                    $results->push([
                        'id' => 'hotspot:'.$user->id,
                        'text' => sprintf('Hotspot · %s (%s)', $label, $user->nomor_hp),
                    ]);
                });
        }

        return response()->json([
            'results' => $results->values(),
        ]);
    }

    public function send(SendWaBlastRequest $request): JsonResponse
    {
        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'WA Gateway belum dikonfigurasi lengkap.',
            ], 422);
        }

        $message = trim((string) $request->validated('message'));
        $recipients = $this->buildRecipients($request);

        if ($recipients->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada penerima yang cocok dengan filter.',
            ], 422);
        }

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($recipients as $recipient) {
            $normalizedPhone = $service->normalizePhone($recipient['phone']);
            $isValidPhone = $service->isValidPhone($normalizedPhone);
            $sent = $isValidPhone ? $service->sendMessage($recipient['phone'], $message) : false;
            $status = $sent ? 'sent' : 'failed';
            $reason = $isValidPhone ? ($sent ? null : 'Gateway gagal mengirim pesan.') : 'Nomor tujuan tidak valid.';

            if ($sent) {
                $successCount++;
            } else {
                $failedCount++;
            }

            WaBlastLog::query()->create([
                'sent_by_id' => auth()->id(),
                'sent_by_name' => auth()->user()?->name,
                'event' => 'blast',
                'target_type' => $recipient['type'],
                'target_id' => $recipient['id'],
                'phone' => $recipient['phone'],
                'phone_normalized' => $normalizedPhone !== '' ? $normalizedPhone : null,
                'status' => $status,
                'reason' => $reason,
                'customer_name' => $recipient['name'],
                'message' => $message,
                'created_at' => now(),
            ]);

            $results[] = [
                'phone' => $recipient['phone'],
                'name' => $recipient['name'],
                'status' => $sent,
                'reason' => $reason,
            ];
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Pesan terkirim ke {$successCount} penerima. Gagal/skip: {$failedCount}.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ]);
    }

    /**
     * @return Collection<int, array{id: int, key: string, type: string, name: string, phone: string}>
     */
    private function buildRecipients(Request $request): Collection
    {
        [$selectedPppIds, $selectedHotspotIds] = $this->resolveRecipientKeys((array) $request->input('recipient_keys', []));
        $hasSpecificRecipients = $selectedPppIds !== [] || $selectedHotspotIds !== [];
        $type = (string) $request->input('type', 'all');
        $statusAkun = trim((string) $request->input('status_akun', ''));
        $statusBayar = trim((string) $request->input('status_bayar', ''));
        $recipients = collect();

        if (in_array($type, ['ppp', 'all'], true) && (! $hasSpecificRecipients || $selectedPppIds !== [])) {
            $query = PppUser::query()
                ->whereNotNull('nomor_hp')
                ->where('nomor_hp', '!=', '');

            if ($statusAkun !== '') {
                $query->where('status_akun', $statusAkun);
            }

            if ($statusBayar !== '') {
                $query->where('status_bayar', $statusBayar);
            }

            if ($request->filled('ppp_profile_id')) {
                $query->where('ppp_profile_id', (int) $request->input('ppp_profile_id'));
            }

            if ($selectedPppIds !== []) {
                $query->whereIn('id', $selectedPppIds);
            }

            $query->orderBy('customer_name')
                ->get(['id', 'customer_name', 'nomor_hp'])
                ->each(function (PppUser $user) use ($recipients): void {
                    $recipients->push([
                        'id' => $user->id,
                        'key' => 'ppp:'.$user->id,
                        'type' => 'ppp',
                        'name' => (string) ($user->customer_name ?: 'Pelanggan PPP'),
                        'phone' => (string) $user->nomor_hp,
                    ]);
                });
        }

        if (in_array($type, ['hotspot', 'all'], true) && (! $hasSpecificRecipients || $selectedHotspotIds !== [])) {
            $query = HotspotUser::query()
                ->whereNotNull('nomor_hp')
                ->where('nomor_hp', '!=', '');

            if ($statusAkun !== '') {
                $query->where('status_akun', $statusAkun);
            }

            if ($statusBayar !== '') {
                $query->where('status_bayar', $statusBayar);
            }

            if ($request->filled('hotspot_profile_id')) {
                $query->where('hotspot_profile_id', (int) $request->input('hotspot_profile_id'));
            }

            if ($selectedHotspotIds !== []) {
                $query->whereIn('id', $selectedHotspotIds);
            }

            $query->orderBy('customer_name')
                ->get(['id', 'customer_name', 'nomor_hp'])
                ->each(function (HotspotUser $user) use ($recipients): void {
                    $recipients->push([
                        'id' => $user->id,
                        'key' => 'hotspot:'.$user->id,
                        'type' => 'hotspot',
                        'name' => (string) ($user->customer_name ?: 'Pelanggan Hotspot'),
                        'phone' => (string) $user->nomor_hp,
                    ]);
                });
        }

        return $recipients
            ->unique(fn (array $recipient): string => $recipient['key'])
            ->values();
    }

    /**
     * @param  array<int, mixed>  $recipientKeys
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function resolveRecipientKeys(array $recipientKeys): array
    {
        $selectedPppIds = [];
        $selectedHotspotIds = [];

        foreach ($recipientKeys as $recipientKey) {
            $rawRecipientKey = trim((string) $recipientKey);

            if ($rawRecipientKey === '' || ! str_contains($rawRecipientKey, ':')) {
                continue;
            }

            [$type, $id] = explode(':', $rawRecipientKey, 2);
            $numericId = (int) $id;

            if ($numericId < 1) {
                continue;
            }

            if ($type === 'ppp') {
                $selectedPppIds[] = $numericId;
            }

            if ($type === 'hotspot') {
                $selectedHotspotIds[] = $numericId;
            }
        }

        return [
            array_values(array_unique($selectedPppIds)),
            array_values(array_unique($selectedHotspotIds)),
        ];
    }
}
