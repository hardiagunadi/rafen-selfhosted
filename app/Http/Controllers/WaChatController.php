<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWaConversationRequest;
use App\Http\Requests\WaChatReplyRequest;
use App\Models\HotspotUser;
use App\Models\PppUser;
use App\Models\User;
use App\Models\WaChatMessage;
use App\Models\WaConversation;
use App\Services\WaGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaChatController extends Controller
{
    public function index(): View
    {
        return view('super-admin.wa-chat');
    }

    public function conversations(Request $request): JsonResponse
    {
        $query = WaConversation::query()
            ->with('assignedTo:id,name')
            ->orderByDesc('last_message_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($builder) use ($search): void {
                $builder->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $conversations = $query->limit(100)->get()->map(function (WaConversation $conversation): array {
            return [
                'id' => $conversation->id,
                'contact_phone' => $conversation->contact_phone,
                'contact_name' => $conversation->contact_name ?: $conversation->contact_phone,
                'status' => $conversation->status,
                'last_message' => $conversation->last_message,
                'last_message_at' => $conversation->last_message_at?->diffForHumans(),
                'last_message_at_raw' => $conversation->last_message_at?->toISOString(),
                'unread_count' => $conversation->unread_count,
                'assigned_to' => $conversation->assignedTo?->name,
                'assigned_to_id' => $conversation->assigned_to_id,
                'customer' => $this->resolveCustomer($conversation->contact_phone),
            ];
        });

        return response()->json([
            'data' => $conversations,
        ]);
    }

    public function show(WaConversation $waConversation): JsonResponse
    {
        $messages = $waConversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($message): array => $this->formatMessage($message));

        if ($waConversation->unread_count > 0) {
            $waConversation->update([
                'unread_count' => 0,
            ]);
        }

        return response()->json([
            'conversation' => [
                'id' => $waConversation->id,
                'contact_phone' => $waConversation->contact_phone,
                'contact_name' => $waConversation->contact_name ?: $waConversation->contact_phone,
                'status' => $waConversation->status,
                'assigned_to' => $waConversation->assignedTo?->name,
                'assigned_to_id' => $waConversation->assigned_to_id,
                'customer' => $this->resolveCustomer($waConversation->contact_phone),
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(WaChatReplyRequest $request, WaConversation $waConversation): JsonResponse
    {
        $service = WaGatewayService::fromSettings();

        if (! $service?->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'WA Gateway belum dikonfigurasi lengkap.',
            ], 422);
        }

        if ($waConversation->session_id) {
            $service->setSessionId($waConversation->session_id);
        }

        $message = trim((string) $request->validated('message'));
        $sent = $service->sendMessage($waConversation->contact_phone, $message);

        if (! $sent) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway gagal mengirim balasan.',
            ], 422);
        }

        $waConversation->messages()->create([
            'direction' => 'outbound',
            'message' => $message,
            'sender_name' => auth()->user()?->name,
            'sender_id' => auth()->id(),
            'created_at' => now(),
        ]);

        $waConversation->update([
            'last_message' => mb_substr($message, 0, 500),
            'last_message_at' => now(),
            'status' => 'open',
            'bot_paused_until' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Balasan berhasil dikirim.',
        ]);
    }

    public function markResolved(WaConversation $waConversation): JsonResponse
    {
        $waConversation->update([
            'status' => 'resolved',
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function markOpen(WaConversation $waConversation): JsonResponse
    {
        $waConversation->update([
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function assignableUsers(): JsonResponse
    {
        return response()->json(
            User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'label' => $user->name,
                ])
        );
    }

    public function assign(AssignWaConversationRequest $request, WaConversation $waConversation): JsonResponse
    {
        $waConversation->update([
            'assigned_to_id' => $request->validated('assigned_to_id'),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(WaConversation $waConversation): JsonResponse
    {
        if ($waConversation->status !== 'resolved') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya percakapan resolved yang bisa dihapus.',
            ], 422);
        }

        $waConversation->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatMessage(WaChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'message' => $message->message,
            'sender_name' => $message->sender_name,
            'created_at' => $message->created_at?->toISOString(),
            'created_at_human' => $message->created_at?->format('H:i'),
            'created_at_date' => $message->created_at?->format('d M Y'),
        ];
    }

    /**
     * @return array{id: int, name: string, type: string, url: string}|null
     */
    private function resolveCustomer(string $phone): ?array
    {
        $candidates = $this->phoneCandidates($phone);

        $pppUser = PppUser::query()
            ->whereIn('nomor_hp', $candidates)
            ->first(['id', 'customer_name']);

        if ($pppUser) {
            return [
                'id' => $pppUser->id,
                'name' => $pppUser->customer_name,
                'type' => 'ppp',
                'url' => route('super-admin.settings.ppp-users.index'),
            ];
        }

        $hotspotUser = HotspotUser::query()
            ->whereIn('nomor_hp', $candidates)
            ->first(['id', 'customer_name']);

        if ($hotspotUser) {
            return [
                'id' => $hotspotUser->id,
                'name' => $hotspotUser->customer_name,
                'type' => 'hotspot',
                'url' => route('super-admin.settings.hotspot-users.index'),
            ];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function phoneCandidates(string $phone): array
    {
        $normalizedPhone = preg_replace('/[\s\-\(\)\+]/', '', $phone) ?? '';
        $candidates = collect([$phone, $normalizedPhone]);

        if (str_starts_with($normalizedPhone, '62')) {
            $candidates->push('0'.substr($normalizedPhone, 2));
        }

        if (str_starts_with($normalizedPhone, '0')) {
            $candidates->push('62'.substr($normalizedPhone, 1));
        }

        return $candidates
            ->map(fn (string $candidate): string => trim($candidate))
            ->filter(fn (string $candidate): bool => $candidate !== '')
            ->unique()
            ->values()
            ->all();
    }
}
